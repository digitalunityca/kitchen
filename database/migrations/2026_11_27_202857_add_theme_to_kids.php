<?php

use App\Modules\Homework\HomeworkModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A background theme for each kid's area (soccer, gaming…). Starts from the
 * "theme" of each kid in config/dashboard.php; empty = plain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kids', function (Blueprint $table): void {
            $table->string('theme', 20)->nullable()->after('color');
        });
        foreach ((array) config('dashboard.modules.'.HomeworkModule::class.'.kids', []) as $kid) {
            if (! empty($kid['theme'])) {
                DB::table('kids')->where('slug', Str::slug((string) $kid['name']))->update(['theme' => (string) $kid['theme']]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('kids', function (Blueprint $table): void {
            $table->dropColumn('theme');
        });
    }
};
