<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A kind of worksheet (addition, multiplication…), seeded from
 * HomeworkModule::TEMPLATES. Later steps of docs/LEARNING_PLAN.md link them to
 * skills and difficulty levels; for now the templates in code still drive the sheets.
 */
class ExerciseType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['points' => 'integer', 'grades_from' => 'integer', 'grades_to' => 'integer', 'printable' => 'boolean', 'active' => 'boolean'];
    }

    public static function idFor(string $slug): ?int
    {
        $id = static::query()->where('slug', $slug)->value('id');

        return $id === null ? null : (int) $id;
    }
}
