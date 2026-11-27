<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KidPin;
use App\Modules\Calendar\CalendarStore;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use App\Modules\Homework\KidPinStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportJsonDataTest extends TestCase
{
    public function test_the_old_json_files_move_into_the_database_and_running_it_again_changes_nothing(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-27 10:00', HomeworkModule::TIMEZONE));
        $sheet = [['question' => '2 + 2', 'answer' => '4']];
        Storage::disk('local')->put('homework.json', json_encode([
            'days' => [
                '2026-11-25' => ['theo' => [
                    ['id' => 't1', 'template' => 'division', 'points' => 15, 'status' => 'approved', 'seed' => 4611686018427387904, 'worksheet' => $sheet, 'number' => 6],
                    ['id' => 't2', 'template' => 'reading', 'points' => 10, 'status' => 'done', 'seed' => 2],
                ]],
                '2026-11-26' => ['arthur' => [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'missed', 'seed' => 3, 'number' => 7]]],
                '2026-08-01' => ['theo' => [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'approved', 'seed' => 9]]],
            ],
            'ledger' => [
                ['kid' => 'theo', 'points' => 15, 'reason' => 'Division · 2026-11-25', 'at' => '2026-11-25T18:00:00-05:00', 'taskId' => 't1', 'rewardId' => null],
                ['kid' => 'theo', 'points' => -25, 'reason' => 'Choose dessert', 'at' => '2026-11-25T19:00:00-05:00', 'taskId' => null, 'rewardId' => 'dessert'],
                ['kid' => 'arthur', 'points' => 40, 'reason' => 'Saved', 'at' => '2026-11-20T10:00:00-05:00'],
            ],
            'words' => ['arthur' => ['papillon', 'magicien']],
            'sheets' => 7,
            'seen' => ['theo' => 1],
        ]));
        Storage::disk('local')->put('homework-pins.json', json_encode(['theo' => Hash::make('1234')]));
        Storage::disk('local')->put('calendars.json', json_encode([['id' => 'family', 'name' => 'Family', 'color' => '#34C759', 'url' => 'https://example.test/secret.ics', 'visible' => true]]));

        foreach ([1, 2] as $run) {
            $this->artisan('hub:import-json')->assertSuccessful()->doesntExpectOutputToContain('secret.ics');
        }

        $store = app(HomeworkStore::class);
        $this->assertSame(-10, $store->balance('theo'));
        $this->assertSame(40, $store->balance('arthur'));
        $this->assertCount(3, $store->load()['ledger']);
        $this->assertSame(['t1', 't2'], array_column($store->tasks('theo', '2026-11-25'), 'id'));
        $this->assertSame(4611686018427387904, $store->tasks('theo', '2026-11-25')[0]['seed']);
        $this->assertSame($sheet, $store->tasks('theo', '2026-11-25')[0]['worksheet']);
        $this->assertNull($store->tasks('theo', '2026-08-01')); // older than 60 days, dropped as before
        $this->assertSame(['papillon', 'magicien'], $store->words('arthur'));
        $this->assertSame(7, $store->load()['sheets']);
        $this->assertSame(['theo' => 1], $store->load()['seen']);
        $this->assertTrue(app(KidPinStore::class)->matches('theo', '1234'));
        $this->assertSame(1, KidPin::query()->count());
        $this->assertSame('https://example.test/secret.ics', app(CalendarStore::class)->find('family')['url']);

        // The next printed sheet continues the numbering.
        $store->update(function (array &$data): void {
            $data['days']['2026-11-27']['theo'] = [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'done', 'seed' => 5, 'worksheet' => [['question' => '1 + 1', 'answer' => '2']]]];
        });
        $store->numberSheets();
        $this->assertSame(8, $store->tasks('theo', '2026-11-27')[0]['number']);

        // The JSON files stay as a backup.
        Storage::disk('local')->assertExists(['homework.json', 'homework-pins.json', 'calendars.json']);
    }
}
