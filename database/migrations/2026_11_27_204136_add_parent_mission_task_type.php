<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Parent missions and bonuses: a task type for "something a parent asks for"
 * (a mission the kid marks done, then a parent scores) and for points given
 * right away for something already done (a bonus, saved as an approved task).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('task_types')->insertOrIgnore([
            'slug' => 'parent_mission', 'name_fr' => 'Mission d’un parent', 'needs_approval' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('task_types')->where('slug', 'parent_mission')->delete();
    }
};
