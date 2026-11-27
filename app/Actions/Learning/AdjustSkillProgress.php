<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseVariant;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use Carbon\CarbonImmutable;

/**
 * A parent moves a kid on one skill by hand (Settings › Progrès): one level up,
 * one level down, or "Maîtrisé" (known: upkeep only, no reviews).
 */
class AdjustSkillProgress
{
    public const ACTIONS = ['up', 'down', 'master'];

    public function handle(Kid $kid, int $skillId, string $action, CarbonImmutable $today): KidSkillProgress
    {
        $grade = $kid->workGrade();
        $top = (int) (ExerciseVariant::query()->where('skill_id', $skillId)->where('active', true)
            ->where('grades_from', '<=', $grade)->where('grades_to', '>=', $grade)->max('level') ?? 1);
        $p = KidSkillProgress::query()->firstOrNew(['kid_id' => $kid->id, 'skill_id' => $skillId], ['level' => 1, 'status' => 'nouveau']);
        $p->bien_streak = 0;
        $p->revoir_streak = 0;
        $p->stuck_since = null;

        match ($action) {
            'up' => $p->fill(['level' => min($top, (int) $p->level + 1), 'status' => 'en_cours']),
            'down' => $p->fill(['level' => max(1, (int) $p->level - 1), 'status' => 'en_cours', 'next_review_on' => null]),
            'master' => $p->fill(['level' => $top, 'status' => 'maitrise', 'mastered_on' => $today, 'review_step' => 2, 'next_review_on' => null]),
        };
        $p->save();

        return $p;
    }
}
