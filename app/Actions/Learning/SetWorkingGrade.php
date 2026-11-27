<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseVariant;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use Illuminate\Support\Facades\DB;

/**
 * A parent sets the grade a kid's sheets follow ("niveau de travail"). Going up:
 * - skills the new grade only reuses → mastered;
 * - skills of the old grade that the new grade builds on → mastered (no step back);
 * - the new grade's skills start at level 1, or 2 when the kid already worked on
 *   them at the old grade.
 * Going down keeps the progress; the levels are kept within what the grade offers.
 */
class SetWorkingGrade
{
    public function __construct(private readonly Prerequisites $prerequisites) {}

    public function handle(Kid $kid, int $grade): void
    {
        $grade = max(1, min(6, $grade));
        $old = $kid->workGrade();

        DB::transaction(function () use ($kid, $grade, $old): void {
            $kid->update(['working_grade' => $grade === $kid->grade ? null : $grade]);
            if ($grade <= $old) {
                return;
            }

            $variants = ExerciseVariant::query()->with('skill.grades')->where('active', true)
                ->where('grades_from', '<=', $grade)->where('grades_to', '>=', $grade)->get()->groupBy('skill_id');
            $progress = KidSkillProgress::query()->where('kid_id', $kid->id)->get()->keyBy('skill_id');
            $expect = fn ($levels, int $g): ?string => $levels->first()->skill->grades->firstWhere('grade', $g)?->expectation;

            $learning = $variants->filter(fn ($levels): bool => in_array($expect($levels, $grade), ['apprend', 'maitrise'], true))->keys();
            $builtOn = array_flip($this->prerequisites->below($learning));

            foreach ($variants as $skillId => $levels) {
                $top = (int) $levels->max('level');
                $now = $expect($levels, $grade);
                $before = $expect($levels, $old);
                $p = $progress->get($skillId) ?? new KidSkillProgress(['kid_id' => $kid->id, 'skill_id' => $skillId, 'level' => 1, 'status' => 'nouveau']);
                if ($p->status === 'a_revoir') {
                    continue; // a parent's "À revoir" stays
                }

                if ($now === 'reinvestit' || (isset($builtOn[$skillId]) && in_array($before, ['apprend', 'maitrise', 'reinvestit'], true))) {
                    $p->fill(['status' => 'maitrise', 'level' => $top, 'next_review_on' => null, 'stuck_since' => null]);
                } elseif ($p->status === 'nouveau' || ! $p->exists) {
                    $start = in_array($before, ['apprend', 'maitrise'], true) ? 2 : 1;
                    $p->fill(['level' => min($top, max((int) $p->level, $start)), 'status' => $start > 1 ? 'en_cours' : 'nouveau']);
                }
                $p->save();
            }
        });
    }
}
