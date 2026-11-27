<?php

use App\Modules\Homework\HomeworkModule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Homework sheets become one kind of task among others (step 1 of
 * docs/LEARNING_PLAN.md):
 * - task_types: homework, home_help.
 * - exercise_types: the worksheet templates, seeded from HomeworkModule::TEMPLATES.
 * - homework_tasks becomes tasks, with its type, exercise type or chore, points,
 *   the parent's score (bien / erreurs / revoir) and the done/approved times.
 * - chores (Aider à la maison) and which kid sees which.
 * - Ledger lines can point at a chore task (task_ref).
 * Existing rows are carried over.
 */
return new class extends Migration
{
    private const CHORES = [
        ['mettre-la-table', 'Mettre la table', '🍽️', 5],
        ['desservir-la-table', 'Desservir la table', '🧽', 5],
        ['faire-son-lit', 'Faire son lit', '🛏️', 5],
        ['preparer-son-sac', 'Préparer son sac d’école', '🎒', 5],
        ['ranger-sa-chambre', 'Ranger sa chambre', '🧸', 10],
        ['vider-le-lave-vaisselle', 'Vider le lave-vaisselle', '🍴', 10],
        ['sortir-le-recyclage', 'Sortir le recyclage', '♻️', 10],
    ];

    private const NAMES_FR = ['Addition' => 'Addition', 'Multiplication' => 'Multiplication', 'Division' => 'Division', 'Writing' => 'Écriture', 'Reading' => 'Lecture'];

    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name_fr', 80);
            $table->boolean('needs_approval')->default(true);
            $table->timestamps();
        });
        DB::table('task_types')->insert([
            ['slug' => 'homework', 'name_fr' => 'Devoirs', 'needs_approval' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'home_help', 'name_fr' => 'Aider à la maison', 'needs_approval' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('exercise_types', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name_fr', 80);
            $table->string('emoji', 16);
            $table->string('subject', 20);
            $table->string('generator', 40);
            $table->unsignedSmallInteger('points');
            $table->unsignedTinyInteger('grades_from');
            $table->unsignedTinyInteger('grades_to');
            $table->boolean('printable');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        $position = 0;
        foreach (HomeworkModule::TEMPLATES as $slug => $t) {
            DB::table('exercise_types')->insert([
                'slug' => $slug, 'name_fr' => self::NAMES_FR[$t['name']] ?? $t['name'], 'emoji' => $t['emoji'],
                'subject' => in_array($slug, ['reading', 'writing'], true) ? 'francais' : 'math',
                'generator' => match ($slug) {
                    'addition' => 'addition_columns', 'multiplication' => 'multiplication_grid', default => 'ai_packet'
                },
                'points' => $t['points'], 'grades_from' => $t['grades'][0], 'grades_to' => $t['grades'][1], 'printable' => $t['printable'],
                'position' => $position++, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('chores', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name_fr', 80);
            $table->string('emoji', 16);
            $table->unsignedSmallInteger('points');
            $table->unsignedTinyInteger('per_day')->default(1);
            $table->string('days', 10)->default('all'); // all | school | weekend
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('chore_kid', function (Blueprint $table): void {
            $table->foreignId('chore_id')->constrained('chores')->cascadeOnDelete();
            $table->foreignId('kid_id')->constrained('kids')->cascadeOnDelete();
            $table->primary(['chore_id', 'kid_id']);
        });
        $kids = DB::table('kids')->pluck('id');
        foreach (self::CHORES as $i => [$slug, $name, $emoji, $points]) {
            $id = DB::table('chores')->insertGetId(['slug' => $slug, 'name_fr' => $name, 'emoji' => $emoji, 'points' => $points, 'position' => $i, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('chore_kid')->insert($kids->map(fn ($kid): array => ['chore_id' => $id, 'kid_id' => $kid])->all());
        }

        $homework = (int) DB::table('task_types')->where('slug', 'homework')->value('id');
        $exercises = DB::table('exercise_types')->pluck('id', 'slug')->all();
        $rows = DB::table('homework_tasks')->orderBy('id')->get()->map(function (object $r) use ($homework, $exercises): array {
            $data = json_decode((string) $r->data, true) ?: [];

            return [
                'id' => $r->id, 'kid_id' => $r->kid_id, 'task_type_id' => $homework, 'exercise_type_id' => $exercises[$r->template] ?? null, 'chore_id' => null,
                'date' => $r->date, 'task_id' => $r->task_id, 'position' => $r->position, 'template' => $r->template, 'status' => $r->status,
                'number' => $r->number, 'points' => (int) ($data['points'] ?? 0), 'score' => $data['score'] ?? null,
                'done_at' => self::utc($data['doneAt'] ?? null), 'approved_at' => self::utc($data['approvedAt'] ?? null), 'data' => $r->data,
            ];
        });
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id')->constrained('kids')->restrictOnDelete();
            $table->foreignId('task_type_id')->constrained('task_types')->restrictOnDelete();
            $table->foreignId('exercise_type_id')->nullable()->constrained('exercise_types')->restrictOnDelete();
            $table->foreignId('chore_id')->nullable()->constrained('chores')->restrictOnDelete();
            $table->date('date');
            $table->string('task_id', 20);
            $table->unsignedInteger('position');
            $table->string('template', 40)->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('number')->nullable();
            $table->unsignedSmallInteger('points');
            $table->string('score', 10)->nullable();
            $table->dateTime('done_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->json('data');
            $table->unique(['kid_id', 'date', 'task_id']);
            $table->index(['task_type_id', 'date', 'kid_id', 'position']);
        });
        foreach ($rows->chunk(200) as $chunk) {
            DB::table('tasks')->insert($chunk->values()->all());
        }
        Schema::drop('homework_tasks');

        $ledger = DB::table('homework_ledger')->orderBy('id')->get()->map(fn (object $r): array => (array) $r + ['task_ref' => null]);
        Schema::drop('homework_ledger');
        Schema::create('homework_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id')->constrained('kids')->restrictOnDelete();
            $table->integer('points');
            $table->string('reason', 200);
            $table->dateTime('at');
            $table->string('task_id', 20)->nullable();
            $table->string('reward_id', 40)->nullable();
            $table->foreignId('task_ref')->nullable()->constrained('tasks')->nullOnDelete();
            $table->index(['kid_id', 'at']);
        });
        foreach ($ledger->chunk(200) as $chunk) {
            DB::table('homework_ledger')->insert($chunk->values()->all());
        }
    }

    public function down(): void
    {
        $ledger = DB::table('homework_ledger')->orderBy('id')->get()->map(fn (object $r): array => array_diff_key((array) $r, ['task_ref' => 1]));
        Schema::drop('homework_ledger');
        Schema::create('homework_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id');
            $table->integer('points');
            $table->string('reason', 200);
            $table->dateTime('at');
            $table->string('task_id', 20)->nullable();
            $table->string('reward_id', 40)->nullable();
            $table->index(['kid_id', 'at'], 'homework_ledger_kid_at_index');
            $table->foreign('kid_id', 'homework_ledger_kid_id_foreign')->references('id')->on('kids')->restrictOnDelete();
        });
        foreach ($ledger->chunk(200) as $chunk) {
            DB::table('homework_ledger')->insert($chunk->values()->all());
        }

        $homework = (int) DB::table('task_types')->where('slug', 'homework')->value('id');
        $tasks = DB::table('tasks')->where('task_type_id', $homework)->orderBy('id')->get()->map(fn (object $r): array => [
            'id' => $r->id, 'kid_id' => $r->kid_id, 'date' => $r->date, 'task_id' => $r->task_id, 'position' => $r->position,
            'template' => (string) $r->template, 'status' => $r->status, 'number' => $r->number, 'data' => $r->data,
        ]);
        Schema::create('homework_tasks', function (Blueprint $table): void {
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
        });
        foreach ($tasks->chunk(200) as $chunk) {
            DB::table('homework_tasks')->insert($chunk->values()->all());
        }

        foreach (['tasks', 'chore_kid', 'chores', 'exercise_types', 'task_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private static function utc(?string $time): ?string
    {
        return $time ? CarbonImmutable::parse($time, 'America/Toronto')->utc()->format('Y-m-d H:i:s') : null;
    }
};
