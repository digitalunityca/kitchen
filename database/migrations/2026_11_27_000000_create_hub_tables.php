<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The hub's saved data (was JSON files in storage/app/private until Nov 2026):
 * homework sheets and points, word lists, kid PINs (hashed), calendars, and a few
 * counters (sheet numbers, "seen" positions for the badges).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('kid', 40);
            $table->date('date');
            $table->string('task_id', 20);
            $table->unsignedInteger('position');
            $table->string('template', 40);
            $table->string('status', 20);
            $table->unsignedInteger('number')->nullable();
            $table->json('data');
            $table->unique(['kid', 'date', 'task_id']);
            $table->index(['date', 'kid', 'position']);
        });

        Schema::create('homework_ledger', function (Blueprint $table): void {
            $table->id();
            $table->string('kid', 40)->index();
            $table->integer('points');
            $table->string('reason', 200);
            $table->string('at', 40);
            $table->string('task_id', 20)->nullable();
            $table->string('reward_id', 40)->nullable();
        });

        Schema::create('homework_words', function (Blueprint $table): void {
            $table->string('kid', 40)->primary();
            $table->json('words');
        });

        Schema::create('kid_pins', function (Blueprint $table): void {
            $table->string('kid', 40)->primary();
            $table->string('hash');
        });

        Schema::create('calendars', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->unsignedInteger('position');
            $table->json('data');
        });

        Schema::create('hub_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->text('value');
        });
    }

    public function down(): void
    {
        foreach (['hub_settings', 'calendars', 'kid_pins', 'homework_words', 'homework_ledger', 'homework_tasks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
