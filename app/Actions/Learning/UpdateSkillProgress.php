<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseVariant;
use App\Models\KidSkillProgress;
use Carbon\CarbonInterface;

/**
 * A parent scored a sheet: move the kid on that skill.
 * - Bien: two in a row → next level; at the grade's top level → mastered, with
 *   reviews at +7 then +21 days. A good review keeps it mastered.
 * - Quelques erreurs: same level (a mastered skill is reviewed again in 7 days).
 * - À revoir: one level down (from level 1 the next sheet practises the
 *   prerequisite); twice in a row marks the kid as stuck (a note in History).
 */
class UpdateSkillProgress
{
    public const REVIEW_DAYS = [1 => 7, 2 => 21];

    public function handle(int $kidId, int $skillId, int $grade, string $score, CarbonInterface $day): KidSkillProgress
    {
        $top = (int) (ExerciseVariant::query()->where('skill_id', $skillId)->where('active', true)
            ->where('grades_from', '<=', $grade)->where('grades_to', '>=', $grade)->max('level') ?? 1);
        $p = KidSkillProgress::query()->firstOrNew(['kid_id' => $kidId, 'skill_id' => $skillId], ['level' => 1, 'status' => 'nouveau']);
        $p->attempts = (int) $p->attempts + 1;
        $p->last_score = $score;
        $p->last_seen_on = $day;
        $mastered = $p->status === 'maitrise';

        if ($score === 'bien') {
            $p->revoir_streak = 0;
            $p->stuck_since = null;
            if ($mastered) {
                // The +7 review passed → one more at +21; after that, upkeep only.
                if ((int) $p->review_step === 1) {
                    $p->review_step = 2;
                    $p->next_review_on = $day->copy()->addDays(self::REVIEW_DAYS[2]);
                } else {
                    $p->next_review_on = null;
                }
            } elseif ((int) $p->bien_streak + 1 >= 2) {
                $p->bien_streak = 0;
                if ((int) $p->level < $top) {
                    $p->level = (int) $p->level + 1;
                    $p->status = 'en_cours';
                } else {
                    $p->status = 'maitrise';
                    $p->mastered_on = $day;
                    $p->review_step = 1;
                    $p->next_review_on = $day->copy()->addDays(self::REVIEW_DAYS[1]);
                }
            } else {
                $p->bien_streak = (int) $p->bien_streak + 1;
                $p->status = 'en_cours';
            }
        } elseif ($score === 'erreurs') {
            $p->bien_streak = 0;
            $p->revoir_streak = 0;
            if ($mastered) {
                $p->review_step = 1;
                $p->next_review_on = $day->copy()->addDays(self::REVIEW_DAYS[1]);
            } else {
                $p->status = 'en_cours';
            }
        } else {
            $p->bien_streak = 0;
            $p->revoir_streak = (int) $p->revoir_streak + 1;
            $p->level = max(1, (int) $p->level - 1);
            $p->status = 'a_revoir';
            $p->review_step = 0;
            $p->next_review_on = null;
            if ($p->revoir_streak >= 2 && $p->stuck_since === null) {
                $p->stuck_since = $day;
            }
        }

        $p->save();

        return $p;
    }
}
