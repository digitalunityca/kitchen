<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a grade expects of a skill: learn it with the teacher (apprend, →), do it
 * alone by June (maitrise, ★) or reuse it (reinvestit), and the months it is
 * worked on (our estimate; empty = upkeep only).
 *
 * @property int $skill_id
 * @property int $grade
 * @property string $expectation
 * @property int|null $window_start
 * @property int|null $window_end
 */
class SkillGrade extends Model
{
    public const SYMBOLS = ['apprend' => '→', 'maitrise' => '★', 'reinvestit' => '↺'];

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['grade' => 'integer', 'window_start' => 'integer', 'window_end' => 'integer'];
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /** Is this month (1–12) inside the window? Windows run across New Year (e.g. 11 → 3). */
    public function covers(int $month): bool
    {
        if ($this->window_start === null || $this->window_end === null) {
            return false;
        }

        return $this->window_start <= $this->window_end
            ? $month >= $this->window_start && $month <= $this->window_end
            : $month >= $this->window_start || $month <= $this->window_end;
    }
}
