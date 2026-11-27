<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Kid;
use App\Modules\Calendar\CalendarStore;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use App\Modules\Homework\KidPinStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CopySqliteToMysqlTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-27 10:00', HomeworkModule::TIMEZONE));
        $this->file = tempnam(sys_get_temp_dir(), 'hub-source-').'.sqlite';
        touch($this->file);
        config(['database.connections.old_hub' => ['driver' => 'sqlite', 'database' => $this->file, 'prefix' => '', 'foreign_key_constraints' => false]]);
        $this->oldSchema();
    }

    protected function tearDown(): void
    {
        DB::purge('old_hub');
        DB::purge('hub_sqlite_source');
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_every_row_moves_over_with_kid_ids_and_utc_times_and_running_it_again_changes_nothing(): void
    {
        $old = DB::connection('old_hub');
        $sheet = json_encode(['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'approved', 'seed' => 4611686018427387904, 'number' => 12, 'worksheet' => [['top' => 12, 'bottom' => 30, 'answer' => '42']]]);
        $old->table('homework_tasks')->insert([
            ['id' => 5, 'kid' => 'theo', 'date' => '2026-11-26', 'task_id' => 't1', 'position' => 0, 'template' => 'addition', 'status' => 'approved', 'number' => 12, 'data' => $sheet],
            ['id' => 9, 'kid' => 'arthur', 'date' => '2026-11-26', 'task_id' => 't1', 'position' => 0, 'template' => 'reading', 'status' => 'todo', 'number' => null, 'data' => '{"id":"t1","template":"reading","points":10,"status":"todo","seed":2}'],
        ]);
        $old->table('homework_ledger')->insert([
            ['id' => 1, 'kid' => 'theo', 'points' => 10, 'reason' => 'Addition · 2026-11-26', 'at' => '2026-11-26T15:06:51-05:00', 'task_id' => 't1', 'reward_id' => null],
            ['id' => 2, 'kid' => 'theo', 'points' => -25, 'reason' => 'Choose dessert', 'at' => '2026-11-26T19:00:00-05:00', 'task_id' => null, 'reward_id' => 'dessert'],
            ['id' => 3, 'kid' => 'arthur', 'points' => 40, 'reason' => 'Saved', 'at' => '2026-11-20T10:00:00-05:00', 'task_id' => null, 'reward_id' => null],
        ]);
        $old->table('homework_words')->insert(['kid' => 'arthur', 'words' => '["papillon","magicien"]']);
        $old->table('kid_pins')->insert(['kid' => 'theo', 'hash' => Hash::make('1234')]);
        $old->table('calendars')->insert(['id' => 'family', 'position' => 0, 'data' => json_encode(['id' => 'family', 'name' => 'Family', 'color' => '#34C759', 'url' => 'https://example.test/secret.ics', 'visible' => true])]);
        $old->table('hub_settings')->insert([['key' => 'homework.sheets', 'value' => '12'], ['key' => 'homework.seen.theo', 'value' => '2']]);

        foreach ([1, 2] as $run) {
            $this->artisan('hub:copy-sqlite-to-mysql', ['--from' => $this->file])
                ->expectsOutputToContain('All rows copied and checked.')
                ->doesntExpectOutputToContain('secret.ics')
                ->assertSuccessful();
        }

        $store = app(HomeworkStore::class);
        $this->assertSame(-15, $store->balance('theo'));
        $this->assertSame(40, $store->balance('arthur'));
        $this->assertSame('2026-11-26T15:06:51-05:00', $store->load()['ledger'][0]['at']);
        $this->assertSame('2026-11-26 20:06:51', DB::table('homework_ledger')->where('id', 1)->value('at'));
        $this->assertSame(Kid::idFor('theo'), DB::table('homework_tasks')->where('id', 5)->value('kid_id'));
        $this->assertSame(4611686018427387904, $store->tasks('theo', '2026-11-26')[0]['seed']);
        $this->assertSame(['papillon', 'magicien'], $store->words('arthur'));
        $this->assertSame(12, $store->load()['sheets']);
        $this->assertTrue(app(KidPinStore::class)->matches('theo', '1234'));
        $this->assertSame('https://example.test/secret.ics', app(CalendarStore::class)->all()[0]['url']);
    }

    public function test_it_stops_when_the_sqlite_file_is_missing(): void
    {
        $this->artisan('hub:copy-sqlite-to-mysql', ['--from' => $this->file.'.missing'])->assertFailed();
    }

    /** The tables as they were before the kids table (slug in every row, ledger time as text). */
    private function oldSchema(): void
    {
        $schema = DB::connection('old_hub')->getSchemaBuilder();
        $schema->create('homework_tasks', function (Blueprint $t): void {
            $t->id();
            $t->string('kid', 40);
            $t->date('date');
            $t->string('task_id', 20);
            $t->unsignedInteger('position');
            $t->string('template', 40);
            $t->string('status', 20);
            $t->unsignedInteger('number')->nullable();
            $t->json('data');
        });
        $schema->create('homework_ledger', function (Blueprint $t): void {
            $t->id();
            $t->string('kid', 40);
            $t->integer('points');
            $t->string('reason', 200);
            $t->string('at', 40);
            $t->string('task_id', 20)->nullable();
            $t->string('reward_id', 40)->nullable();
        });
        $schema->create('homework_words', fn (Blueprint $t) => [$t->string('kid', 40)->primary(), $t->json('words')]);
        $schema->create('kid_pins', fn (Blueprint $t) => [$t->string('kid', 40)->primary(), $t->string('hash')]);
        $schema->create('calendars', fn (Blueprint $t) => [$t->string('id', 64)->primary(), $t->unsignedInteger('position'), $t->json('data')]);
        $schema->create('hub_settings', fn (Blueprint $t) => [$t->string('key', 100)->primary(), $t->text('value')]);
    }
}
