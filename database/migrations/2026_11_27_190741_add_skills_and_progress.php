<?php

use App\Actions\Learning\SeedKidProgress;
use App\Actions\Learning\SyncCurriculum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Steps 3–5 of docs/LEARNING_PLAN.md: the skills from the Quebec "Progression des
 * apprentissages" (database/data/skills.json), the worksheet levels that practise
 * them (database/data/exercise_variants.json), and each kid's progress per skill.
 * Tasks remember the skill and level they were made for. The catalogue is loaded
 * and the kids' starting levels are seeded from the sheets already approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('subject', 20);
            $table->string('domain', 80);
            $table->string('name_fr', 200);
            $table->string('source', 20)->default('pda');
            $table->string('source_ref', 120)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('skill_prerequisites', function (Blueprint $table): void {
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('skills')->cascadeOnDelete();
            $table->string('strength', 10)->default('hard');
            $table->primary(['skill_id', 'prerequisite_id']);
        });

        Schema::create('skill_grades', function (Blueprint $table): void {
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->string('expectation', 12); // apprend (→) | maitrise (★) | reinvestit
            $table->unsignedTinyInteger('window_start')->nullable(); // month, 9 = September
            $table->unsignedTinyInteger('window_end')->nullable();
            $table->primary(['skill_id', 'grade']);
        });

        Schema::create('exercise_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_type_id')->constrained('exercise_types')->restrictOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->unsignedTinyInteger('grades_from');
            $table->unsignedTinyInteger('grades_to');
            $table->string('label_fr', 120);
            $table->json('params');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['exercise_type_id', 'skill_id', 'level']);
        });

        Schema::create('kid_skill_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kid_id')->constrained('kids')->restrictOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->restrictOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status', 12)->default('nouveau'); // nouveau | en_cours | maitrise | a_revoir
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedTinyInteger('bien_streak')->default(0);
            $table->unsignedTinyInteger('revoir_streak')->default(0);
            $table->unsignedTinyInteger('review_step')->default(0);
            $table->string('last_score', 10)->nullable();
            $table->date('last_seen_on')->nullable();
            $table->date('next_review_on')->nullable();
            $table->date('mastered_on')->nullable();
            $table->date('stuck_since')->nullable();
            $table->timestamps();
            $table->unique(['kid_id', 'skill_id']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->foreignId('exercise_variant_id')->nullable()->constrained('exercise_variants')->nullOnDelete();
            $table->unsignedTinyInteger('level')->nullable();
        });

        app(SyncCurriculum::class)->handle();
        app(SeedKidProgress::class)->handle();
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('exercise_variant_id');
            $table->dropConstrainedForeignId('skill_id');
            $table->dropColumn('level');
        });
        foreach (['kid_skill_progress', 'exercise_variants', 'skill_grades', 'skill_prerequisites', 'skills'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
