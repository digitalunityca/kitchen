<?php

use App\Models\Kid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kids become rows of their own (seeded from config/dashboard.php, the config is
 * only the seed from now on). The homework tables point at them with kid_id
 * foreign keys instead of repeating the "theo" slug, and the ledger time becomes
 * a real date-time (UTC). Each table is rebuilt with its rows carried over, so
 * this works on a database that already has data, on SQLite and MySQL alike.
 */
return new class extends Migration
{
    private const TZ = 'America/Toronto';

    public function up(): void
    {
        Schema::create('kids', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->string('badge', 16);
            $table->string('color', 9);
            $table->unsignedTinyInteger('grade');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Kid::seedFromConfig();
        $this->addKidsFoundInData();
        $ids = DB::table('kids')->pluck('id', 'slug')->all();

        $this->rebuild('homework_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id');
            $table->date('date');
            $table->string('task_id', 20);
            $table->unsignedInteger('position');
            $table->string('template', 40);
            $table->string('status', 20);
            $table->unsignedInteger('number')->nullable();
            $table->json('data');
            $table->unique(['kid_id', 'date', 'task_id'], 'homework_tasks_kid_date_task_unique');
            $table->index(['date', 'kid_id', 'position'], 'homework_tasks_date_kid_position_index');
            $table->foreign('kid_id', 'homework_tasks_kid_id_foreign')->references('id')->on('kids')->restrictOnDelete();
        }, fn (object $r): array => ['id' => $r->id, 'kid_id' => $ids[$r->kid], 'date' => $r->date, 'task_id' => $r->task_id, 'position' => $r->position,
            'template' => $r->template, 'status' => $r->status, 'number' => $r->number, 'data' => $r->data]);

        $this->rebuild('homework_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id');
            $table->integer('points');
            $table->string('reason', 200);
            $table->dateTime('at');
            $table->string('task_id', 20)->nullable();
            $table->string('reward_id', 40)->nullable();
            $table->index(['kid_id', 'at'], 'homework_ledger_kid_at_index');
            $table->foreign('kid_id', 'homework_ledger_kid_id_foreign')->references('id')->on('kids')->restrictOnDelete();
        }, fn (object $r): array => ['id' => $r->id, 'kid_id' => $ids[$r->kid], 'points' => $r->points, 'reason' => $r->reason,
            'at' => CarbonImmutable::parse($r->at, self::TZ)->utc()->format('Y-m-d H:i:s'), 'task_id' => $r->task_id, 'reward_id' => $r->reward_id]);

        $this->rebuild('homework_words', function (Blueprint $table): void {
            $table->foreignId('kid_id')->primary();
            $table->json('words');
            $table->foreign('kid_id', 'homework_words_kid_id_foreign')->references('id')->on('kids')->restrictOnDelete();
        }, fn (object $r): array => ['kid_id' => $ids[$r->kid], 'words' => $r->words]);

        $this->rebuild('kid_pins', function (Blueprint $table): void {
            $table->foreignId('kid_id')->primary();
            $table->string('hash');
            $table->foreign('kid_id', 'kid_pins_kid_id_foreign')->references('id')->on('kids')->restrictOnDelete();
        }, fn (object $r): array => ['kid_id' => $ids[$r->kid], 'hash' => $r->hash]);
    }

    public function down(): void
    {
        $slugs = DB::table('kids')->pluck('slug', 'id')->all();

        $this->rebuild('homework_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('kid', 40);
            $table->date('date');
            $table->string('task_id', 20);
            $table->unsignedInteger('position');
            $table->string('template', 40);
            $table->string('status', 20);
            $table->unsignedInteger('number')->nullable();
            $table->json('data');
            $table->unique(['kid', 'date', 'task_id'], 'homework_tasks_kid_date_task_id_unique');
            $table->index(['date', 'kid', 'position'], 'homework_tasks_date_kid_position_index');
        }, fn (object $r): array => ['id' => $r->id, 'kid' => $slugs[$r->kid_id], 'date' => $r->date, 'task_id' => $r->task_id, 'position' => $r->position,
            'template' => $r->template, 'status' => $r->status, 'number' => $r->number, 'data' => $r->data]);

        $this->rebuild('homework_ledger', function (Blueprint $table): void {
            $table->id();
            $table->string('kid', 40)->index('homework_ledger_kid_index');
            $table->integer('points');
            $table->string('reason', 200);
            $table->string('at', 40);
            $table->string('task_id', 20)->nullable();
            $table->string('reward_id', 40)->nullable();
        }, fn (object $r): array => ['id' => $r->id, 'kid' => $slugs[$r->kid_id], 'points' => $r->points, 'reason' => $r->reason,
            'at' => CarbonImmutable::parse($r->at, 'UTC')->setTimezone(self::TZ)->toIso8601String(), 'task_id' => $r->task_id, 'reward_id' => $r->reward_id]);

        $this->rebuild('homework_words', fn (Blueprint $table) => [$table->string('kid', 40)->primary(), $table->json('words')],
            fn (object $r): array => ['kid' => $slugs[$r->kid_id], 'words' => $r->words]);

        $this->rebuild('kid_pins', fn (Blueprint $table) => [$table->string('kid', 40)->primary(), $table->string('hash')],
            fn (object $r): array => ['kid' => $slugs[$r->kid_id], 'hash' => $r->hash]);

        Schema::dropIfExists('kids');
    }

    /** Rows for kids missing from the config (so no foreign key is left dangling). */
    private function addKidsFoundInData(): void
    {
        $slugs = collect(['homework_tasks', 'homework_ledger', 'homework_words', 'kid_pins'])
            ->flatMap(fn (string $table) => DB::table($table)->distinct()->pluck('kid'))
            ->unique()->diff(DB::table('kids')->pluck('slug'));

        foreach ($slugs->values() as $i => $slug) {
            DB::table('kids')->insert(['slug' => $slug, 'name' => ucfirst($slug), 'badge' => mb_strtoupper(mb_substr($slug, 0, 1)), 'color' => '#5AC8FA', 'grade' => 2,
                'position' => 100 + $i, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Read the rows, recreate the table in its new shape and put the rows back
     * (household data: a few hundred rows at most). On SQLite this runs inside
     * the migration's transaction; on MySQL it runs on the new, empty tables.
     */
    private function rebuild(string $table, Closure $schema, Closure $row): void
    {
        $rows = DB::table($table)->get()->map(fn (object $r): array => $row($r));
        Schema::drop($table);
        Schema::create($table, $schema);
        foreach ($rows->chunk(500) as $chunk) {
            DB::table($table)->insert($chunk->values()->all());
        }
    }
};
