<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Kid;
use App\Models\LedgerEntry;
use App\Models\Task;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeHelpTest extends TestCase
{
    private const SHEET = '/modules/homework/kids/theo/sheet';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821']);
        // Wednesday 25 November 2026, after school.
        $this->travelTo(CarbonImmutable::parse('2026-11-25 16:00', HomeworkModule::TIMEZONE));
    }

    public function test_kids_see_todays_chores_mark_them_done_and_a_parent_approves_them_for_points(): void
    {
        $setTable = Chore::query()->where('slug', 'mettre-la-table')->value('id');
        $this->post('/modules/homework/kids/theo/chores/'.$setTable.'/done')->assertUnauthorized();
        $this->signIn('theo');

        $this->get(self::SHEET)->assertSee('data-go="chores"', false)->assertSee('Help at home');
        $this->get(self::SHEET.'?screen=chores')->assertOk()
            ->assertSeeInOrder(['Mettre la table', 'Desservir la table', 'Faire son lit', 'Préparer son sac d’école', 'Ranger sa chambre', 'Vider le lave-vaisselle', 'Sortir le recyclage'])
            ->assertSee('+5 points');

        // "I'm done": waiting for a parent, and it counts once a day.
        $this->post('/modules/homework/kids/theo/chores/'.$setTable.'/done')->assertOk()
            ->assertSee('Thanks for helping! A parent will check it.')->assertSee('Waiting for a parent');
        $this->post('/modules/homework/kids/theo/chores/'.$setTable.'/done')->assertStatus(422)->assertJson(['error' => 'This one is already done for today.']);
        $this->assertSame(0, app(HomeworkStore::class)->balance('theo'));
        $this->get(self::SHEET)->assertSee('1 task is waiting for a parent check');

        // History: the chore is in "To check"; approving needs the parent PIN and pays once.
        $task = Task::query()->where('chore_id', $setTable)->value('id');
        $check = '/modules/homework/kids/theo/chores/check/'.$task;
        $this->get(self::SHEET.'?screen=history')->assertSee('data-filter="check"', false)->assertSee('Mettre la table')->assertSee($check, false);
        $this->post($check, ['decision' => 'approve'])->assertForbidden()->assertJson(['pin' => true]);
        $this->post($check, ['pin' => '4821', 'decision' => 'approve'])->assertOk()->assertSee('Approved · +5');
        $this->post($check, ['pin' => '4821', 'decision' => 'approve'])->assertOk();
        $this->assertSame(5, app(HomeworkStore::class)->balance('theo'));
        $this->assertSame($task, (int) LedgerEntry::query()->value('task_ref'));

        // A celebration on the dashboard, like approved sheets.
        $this->getJson('/modules/homework/events?after=0')->assertOk()
            ->assertJsonPath('events', [['kind' => 'chore', 'kid' => 'theo', 'points' => 5, 'name' => 'Mettre la table', 'balance' => 5]]);

        // "Not done": no points, and the kid can do it again.
        $bed = Chore::query()->where('slug', 'faire-son-lit')->value('id');
        $this->post('/modules/homework/kids/theo/chores/'.$bed.'/done')->assertOk();
        $this->post('/modules/homework/kids/theo/chores/check/'.Task::query()->where('chore_id', $bed)->value('id'), ['pin' => '4821', 'decision' => 'missed'])->assertOk();
        $this->assertSame(5, app(HomeworkStore::class)->balance('theo'));
        $this->post('/modules/homework/kids/theo/chores/'.$bed.'/done')->assertOk();

        // Arthur's chores are his own.
        $this->post('/modules/homework/kids/arthur/chores/check/'.$task, ['pin' => '4821', 'decision' => 'approve']);
        $this->assertSame(0, app(HomeworkStore::class)->balance('arthur'));
    }

    public function test_chore_points_count_toward_rewards(): void
    {
        $this->signIn('theo');
        $chore = Chore::query()->where('slug', 'ranger-sa-chambre')->value('id');
        $this->post('/modules/homework/kids/theo/chores/'.$chore.'/done')->assertOk();
        $this->get(self::SHEET.'?screen=rewards')->assertSee('+10 points are waiting for a parent check.');
    }

    public function test_parents_manage_chores_in_settings(): void
    {
        $this->get('/modules/homework/chores')->assertRedirect();
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/settings')->assertSee('Help at home');
        $this->get('/modules/homework/chores')->assertOk()->assertSee('Mettre la table')->assertSee('Add a chore');

        $theo = Kid::idFor('theo');
        $this->post('/modules/homework/chores', ['name' => 'Arroser les plantes', 'emoji' => '🪴', 'points' => 3, 'per_day' => 2, 'days' => 'weekend', 'kids' => [$theo], 'active' => 1])
            ->assertRedirect('/modules/homework/chores');
        $plants = Chore::query()->where('slug', 'arroser-les-plantes')->firstOrFail();
        $this->assertSame([3, 2, 'weekend', [$theo]], [$plants->points, $plants->per_day, $plants->days, $plants->kids()->pluck('kids.id')->all()]);

        // Turned off, or not for this kid: not on their list. Weekend chores wait for the weekend.
        $table = Chore::query()->where('slug', 'mettre-la-table')->firstOrFail();
        $this->post('/modules/homework/chores/'.$table->id, ['name' => 'Mettre la table', 'emoji' => '🍽️', 'points' => 5, 'per_day' => 1, 'days' => 'all', 'kids' => [$theo]])->assertRedirect();
        $this->assertFalse($table->fresh()->active);
        $this->post('/modules/homework/chores', ['name' => '', 'emoji' => '⭐', 'points' => 0, 'per_day' => 1, 'days' => 'never'])->assertSessionHasErrors(['name', 'points', 'days']);

        $this->signIn('theo');
        $this->get(self::SHEET.'?screen=chores')->assertDontSee('Mettre la table')->assertDontSee('Arroser les plantes')->assertSee('Faire son lit');
        $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', HomeworkModule::TIMEZONE));
        $this->signIn('theo');
        $this->get(self::SHEET.'?screen=chores')->assertSee('Arroser les plantes')->assertSee('2 more times today');
    }

    private function signIn(string $kid): void
    {
        $this->post("/modules/homework/kids/{$kid}/unlock", ['pin' => '4821'])->assertOk();
    }
}
