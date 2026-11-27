<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Kid;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies the hub's data from the old SQLite file into the current database
 * (MySQL): homework tasks, the points ledger, word lists, kid PIN hashes,
 * calendars and the saved settings. Reads either the old slug-based tables or
 * the kid_id ones. Replaces what the target has, keeps the row ids, then
 * compares both sides (counts, totals and a digest of every row) and fails if
 * anything differs. Prints numbers only, never PINs, hashes or calendar links.
 */
class CopySqliteToMysql extends Command
{
    protected $signature = 'hub:copy-sqlite-to-mysql {--from= : SQLite file (default: storage/app/database.sqlite)}';

    protected $description = 'Copy all hub data from the SQLite file into the current database and verify it';

    private const TZ = 'America/Toronto';

    private const SOURCE = 'hub_sqlite_source';

    public function handle(): int
    {
        $path = (string) ($this->option('from') ?: storage_path('app/database.sqlite'));
        if (! is_file($path)) {
            $this->error('No SQLite file at the given path.');

            return self::FAILURE;
        }
        if (DB::connection()->getDriverName() === 'sqlite' && realpath((string) DB::connection()->getDatabaseName()) === realpath($path)) {
            $this->error('The source and the target are the same database.');

            return self::FAILURE;
        }
        if (! Schema::hasTable('kids')) {
            $this->error('Run php artisan migrate on the target first.');

            return self::FAILURE;
        }

        config(['database.connections.'.self::SOURCE => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => false]]);
        $source = DB::connection(self::SOURCE);
        $rows = $this->read($source);

        DB::transaction(function () use ($source, $rows): void {
            $this->copyKids($source, $rows);
            $ids = DB::table('kids')->pluck('id', 'slug')->all();

            foreach (['homework_tasks', 'homework_ledger', 'homework_words', 'kid_pins', 'calendars', 'hub_settings'] as $table) {
                DB::table($table)->delete();
            }
            $this->insert('homework_tasks', array_map(fn (array $t): array => ['kid_id' => $ids[$t['kid']]] + array_diff_key($t, ['kid' => 1]), $rows['tasks']));
            $this->insert('homework_ledger', array_map(fn (array $e): array => ['kid_id' => $ids[$e['kid']]] + array_diff_key($e, ['kid' => 1]), $rows['ledger']));
            $this->insert('homework_words', array_map(fn (array $w): array => ['kid_id' => $ids[$w['kid']], 'words' => $w['words']], $rows['words']));
            $this->insert('kid_pins', array_map(fn (array $p): array => ['kid_id' => $ids[$p['kid']], 'hash' => $p['hash']], $rows['pins']));
            $this->insert('calendars', $rows['calendars']);
            $this->insert('hub_settings', $rows['settings']);
        });

        $before = $this->summary($rows);
        $after = $this->summary($this->read(DB::connection()));
        $this->table(['Check', 'SQLite', DB::connection()->getDriverName() === 'mysql' ? 'MySQL' : 'Target', ''],
            array_map(fn (string $check): array => [$check, $before[$check], $after[$check], $before[$check] === $after[$check] ? '✓' : '✗'], array_keys($before)));

        if ($before !== $after) {
            $this->error('The copy does not match the SQLite data.');

            return self::FAILURE;
        }
        $this->info('All rows copied and checked.');

        return self::SUCCESS;
    }

    /**
     * Every row in one shape, whichever schema the database has: kids by slug,
     * ledger times in UTC, JSON as stored.
     *
     * @return array{kids: list<array<string, mixed>>, tasks: list<array<string, mixed>>, ledger: list<array<string, mixed>>, words: list<array<string, mixed>>, pins: list<array<string, mixed>>, calendars: list<array<string, mixed>>, settings: list<array<string, mixed>>}
     */
    private function read(Connection $db): array
    {
        $linked = $db->getSchemaBuilder()->hasColumn('homework_tasks', 'kid_id');
        $slugs = $linked ? $db->table('kids')->pluck('slug', 'id')->all() : [];
        $kid = fn (object $r): string => $linked ? $slugs[$r->kid_id] : (string) $r->kid;
        $all = fn (string $table, string $order) => $db->table($table)->orderBy($order)->get();

        return [
            'kids' => $linked ? $all('kids', 'id')->map(fn (object $k): array => ['slug' => $k->slug, 'name' => $k->name, 'badge' => $k->badge, 'color' => $k->color, 'grade' => (int) $k->grade, 'position' => (int) $k->position])->all() : [],
            'tasks' => $all('homework_tasks', 'id')->map(fn (object $t): array => ['id' => (int) $t->id, 'kid' => $kid($t), 'date' => substr((string) $t->date, 0, 10), 'task_id' => $t->task_id,
                'position' => (int) $t->position, 'template' => $t->template, 'status' => $t->status, 'number' => $t->number === null ? null : (int) $t->number, 'data' => (string) $t->data])->all(),
            'ledger' => $all('homework_ledger', 'id')->map(fn (object $e): array => ['id' => (int) $e->id, 'kid' => $kid($e), 'points' => (int) $e->points, 'reason' => $e->reason,
                'at' => $linked ? CarbonImmutable::parse((string) $e->at, 'UTC')->format('Y-m-d H:i:s') : CarbonImmutable::parse((string) $e->at, self::TZ)->utc()->format('Y-m-d H:i:s'),
                'task_id' => $e->task_id, 'reward_id' => $e->reward_id])->all(),
            'words' => $all('homework_words', $linked ? 'kid_id' : 'kid')->map(fn (object $w): array => ['kid' => $kid($w), 'words' => (string) $w->words])->all(),
            'pins' => $all('kid_pins', $linked ? 'kid_id' : 'kid')->map(fn (object $p): array => ['kid' => $kid($p), 'hash' => $p->hash])->all(),
            'calendars' => $all('calendars', 'position')->map(fn (object $c): array => ['id' => $c->id, 'position' => (int) $c->position, 'data' => (string) $c->data])->all(),
            'settings' => $all('hub_settings', 'key')->map(fn (object $s): array => ['key' => $s->key, 'value' => (string) $s->value])->all(),
        ];
    }

    /** Kids from the source (if it has them), the config, and any slug found in the data. */
    private function copyKids(Connection $source, array $rows): void
    {
        foreach ($rows['kids'] as $kid) {
            Kid::query()->updateOrCreate(['slug' => $kid['slug']], array_diff_key($kid, ['slug' => 1]));
        }
        Kid::seedFromConfig();

        $found = collect([$rows['tasks'], $rows['ledger'], $rows['words'], $rows['pins']])->flatten(1)->pluck('kid')->unique();
        foreach ($found->diff(Kid::query()->pluck('slug'))->values() as $i => $slug) {
            Kid::query()->create(['slug' => $slug, 'name' => ucfirst((string) $slug), 'badge' => mb_strtoupper(mb_substr((string) $slug, 0, 1)), 'color' => '#5AC8FA', 'grade' => 2, 'position' => 100 + $i]);
        }
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    /**
     * What both sides must agree on. Digests cover every field of every row (JSON
     * compared by content, since MySQL reorders keys), so they show a difference
     * without showing any value.
     *
     * @return array<string, string>
     */
    private function summary(array $rows): array
    {
        $digest = fn (array $list): string => substr(hash('sha256', json_encode($list, JSON_UNESCAPED_UNICODE)), 0, 12);
        $json = fn (array $row, string $field): array => [$field => self::canonical(json_decode((string) $row[$field], true))] + $row;
        $tasks = collect($rows['tasks']);
        $ledger = collect($rows['ledger']);
        $sheets = collect($rows['settings'])->firstWhere('key', 'homework.sheets');

        return [
            'Tasks' => (string) $tasks->count(),
            'Tasks by status' => $tasks->countBy('status')->sortKeys()->map(fn (int $n, string $s): string => "{$s} {$n}")->implode(', '),
            'Highest sheet number' => (string) ($tasks->max('number') ?? 0),
            'Sheet counter' => (string) ($sheets['value'] ?? '0'),
            'Task rows digest' => $digest(array_map(fn (array $t): array => $json($t, 'data'), $rows['tasks'])),
            'Ledger lines' => (string) $ledger->count(),
            'Points per kid' => $ledger->groupBy('kid')->sortKeys()->map(fn ($e, string $k): string => $k.' '.$e->count().' lines / '.$e->sum('points').' pts')->implode(', '),
            'Ledger rows digest' => $digest($rows['ledger']),
            'Kid PIN hashes' => count($rows['pins']).' ('.$digest(collect($rows['pins'])->sortBy('kid')->values()->all()).')',
            'Word lists' => count($rows['words']).' ('.$digest(array_map(fn (array $w): array => $json($w, 'words'), collect($rows['words'])->sortBy('kid')->values()->all())).')',
            'Calendars' => count($rows['calendars']).' ('.$digest(array_map(fn (array $c): array => $json($c, 'data'), $rows['calendars'])).')',
            'Settings' => count($rows['settings']).' ('.$digest($rows['settings']).')',
        ];
    }

    /** JSON content with object keys sorted, so equal documents compare equal. */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
