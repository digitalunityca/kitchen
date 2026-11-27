<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Kid;
use App\Models\Task;
use App\Models\TaskType;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentMissionsTest extends TestCase
{
    private const SHEET = '/modules/homework/kids/theo/sheet';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821']);
        $this->travelTo(CarbonImmutable::parse('2026-11-25 16:00', HomeworkModule::TIMEZONE));
    }

    public function test_a_parent_gives_a_mission_the_kid_finishes_it_and_the_parent_scores_it(): void
    {
        // Given in Settings, behind the settings PIN, to both kids.
        $this->post('/modules/homework/missions', ['kids' => [Kid::idFor('theo')], 'title' => 'Aider à ranger le garage', 'points' => 20])->assertRedirect();
        $this->assertSame(0, Task::query()->count());
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/settings')->assertSee('Rewards from a parent');
        $this->post('/modules/homework/missions', ['kids' => [Kid::idFor('theo'), Kid::idFor('arthur')], 'title' => 'Aider à ranger le garage', 'points' => 20, 'due' => '2026-11-29'])
            ->assertRedirect('/modules/homework/missions');
        $this->get('/modules/homework/missions')->assertSee('Aider à ranger le garage')->assertSee('by 29 November');
        $this->post('/settings/lock');

        // The kid sees it on home and taps "I'm done".
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '4821'])->assertOk()
            ->assertSee('1 mission from a parent')->assertSee('Aider à ranger le garage')->assertSee('data-mission-done=', false);
        $mission = Task::query()->where('kid_id', Kid::idFor('theo'))->value('id');
        $this->post("/modules/homework/kids/theo/missions/{$mission}/done")->assertOk()->assertSee('Waiting for a parent');
        $this->get(self::SHEET.'?screen=rewards')->assertSee('+20 points are waiting for a parent check.');

        // A parent scores it in History with the PIN: "À revoir" gives half.
        $check = "/modules/homework/kids/theo/missions/check/{$mission}";
        $this->get(self::SHEET.'?screen=history')->assertSee('Mission from a parent')->assertSee($check, false);
        $this->post($check, ['decision' => 'revoir'])->assertForbidden();
        $this->post($check, ['pin' => '4821', 'decision' => 'revoir'])->assertOk()->assertSee('<span class="kz-score-tag">· Go over again</span> · +10', false);
        $this->post($check, ['pin' => '4821', 'decision' => 'bien'])->assertOk();
        $this->assertSame(10, app(HomeworkStore::class)->balance('theo'));
        $this->getJson('/modules/homework/events?after=0')->assertJsonPath('events.0.kind', 'bonus')->assertJsonPath('events.0.name', 'Aider à ranger le garage');

        // "Not done" keeps it open; a parent can take back an open mission.
        $arthur = Task::query()->where('kid_id', Kid::idFor('arthur'))->value('id');
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->post("/modules/homework/missions/{$arthur}/cancel")->assertRedirect();
        $this->assertSame('cancelled', Task::query()->find($arthur)->status);
    }

    public function test_a_parent_gives_bonus_points_right_away(): void
    {
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '4821']);
        $this->get(self::SHEET.'?screen=history')->assertSee('data-parent-bonus="1"', false)->assertSee('Reward from a parent');

        $this->post('/modules/homework/kids/theo/bonus', ['reason' => 'A aidé grand-maman', 'points' => 10])->assertForbidden();
        $this->post('/modules/homework/kids/theo/bonus', ['pin' => '4821', 'reason' => '', 'points' => 10])->assertStatus(422);
        $this->post('/modules/homework/kids/theo/bonus', ['pin' => '4821', 'reason' => 'A aidé grand-maman', 'points' => 10])->assertOk()
            ->assertSee('Bonus from a parent')->assertSee('A aidé grand-maman');
        $this->assertSame(10, app(HomeworkStore::class)->balance('theo'));

        // From Settings too.
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->post('/modules/homework/missions/bonus', ['kid' => Kid::idFor('arthur'), 'reason' => 'Belle surprise', 'points' => 5])->assertRedirect();
        $this->assertSame(5, app(HomeworkStore::class)->balance('arthur'));
        $this->assertSame(2, Task::query()->where('task_type_id', TaskType::idFor(TaskType::PARENT_MISSION))->where('status', 'approved')->count());
        $this->getJson('/modules/homework/events?after=0')->assertJsonPath('events.1.kind', 'bonus');
    }
}
