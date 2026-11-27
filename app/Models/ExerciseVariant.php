<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One worksheet type practising one skill at one level, for some grades:
 * number ranges for the sheets made on the hub, a focus text for the AI sheets
 * (database/data/exercise_variants.json).
 *
 * @property int $id
 * @property int $exercise_type_id
 * @property int $skill_id
 * @property int $level
 * @property int $grades_from
 * @property int $grades_to
 * @property string $label_fr
 * @property array<string, mixed> $params
 */
class ExerciseVariant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['params' => 'array', 'level' => 'integer', 'grades_from' => 'integer', 'grades_to' => 'integer', 'skill_id' => 'integer', 'exercise_type_id' => 'integer', 'active' => 'boolean'];
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /** @return BelongsTo<ExerciseType, $this> */
    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }
}
