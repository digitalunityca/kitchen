<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where a kid is on a skill: the level of their next sheet, nouveau / en_cours /
 * maitrise / a_revoir, the parent's last score, and the next review date.
 *
 * @property int $id
 * @property int $kid_id
 * @property int $skill_id
 * @property int $level
 * @property string $status
 * @property int $attempts
 * @property int $bien_streak
 * @property int $revoir_streak
 * @property int $review_step
 * @property string|null $last_score
 * @property CarbonImmutable|null $last_seen_on
 * @property CarbonImmutable|null $next_review_on
 * @property CarbonImmutable|null $mastered_on
 * @property CarbonImmutable|null $stuck_since
 */
class KidSkillProgress extends Model
{
    protected $table = 'kid_skill_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kid_id' => 'integer', 'skill_id' => 'integer', 'level' => 'integer', 'attempts' => 'integer', 'bien_streak' => 'integer',
            'revoir_streak' => 'integer', 'review_step' => 'integer',
            'last_seen_on' => 'immutable_date', 'next_review_on' => 'immutable_date', 'mastered_on' => 'immutable_date', 'stuck_since' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
