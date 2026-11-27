<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A kid's working grade ("niveau de travail"): the grade the sheets follow,
 * which a parent can set above or below the school grade. Empty = school grade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kids', function (Blueprint $table): void {
            $table->unsignedTinyInteger('working_grade')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('kids', function (Blueprint $table): void {
            $table->dropColumn('working_grade');
        });
    }
};
