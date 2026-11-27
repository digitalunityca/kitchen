<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseVariant;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use App\Models\Skill;
use App\Models\Task;
use App\Models\TaskType;
use App\Modules\Homework\HomeworkModule;
use Carbon\CarbonImmutable;

/**
 * Starting levels for kids with no progress yet, for every skill their grade's
 * sheets can practise:
 * - a skill the grade only reuses (★ in an earlier year): mastered, top level, upkeep;
 * - a skill of this year: level 1, or further into the year when it continues
 *   last year's → (★ this year);
 * - then the sheets already approved before levels existed: each old sheet
 *   matched the level that has the same numbers, so the kid starts at least there.
 */
class SeedKidProgress
{
    /** template => grade => [skill code, level]: the levels with the number ranges the hub used per grade before. */
    public const OLD_SHEETS = [
        'addition' => [1 => ['MATH.OPS.A.2c', 2], 2 => ['MATH.OPS.A.4a', 3], 3 => ['MATH.OPS.A.4b', 1], 4 => ['MATH.OPS.A.4b', 2], 5 => ['MATH.OPS.A.4b', 3], 6 => ['MATH.OPS.A.4b', 4]],
        'multiplication' => [1 => ['MATH.SOP.A.3a', 1], 2 => ['MATH.SOP.A.3a', 2], 3 => ['MATH.OPS.A.7a', 1], 4 => ['MATH.OPS.A.7b', 1], 5 => ['MATH.OPS.A.7b', 2], 6 => ['MATH.OPS.A.7b', 3]],
    ];

    /** @return int progress rows created */
    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay();
        $created = 0;

        foreach (Kid::query()->get() as $kid) {
            if (KidSkillProgress::query()->where('kid_id', $kid->id)->exists()) {
                continue;
            }

            $variants = ExerciseVariant::query()->with('skill.grades')->where('active', true)
                ->where('grades_from', '<=', $kid->grade)->where('grades_to', '>=', $kid->grade)->get()->groupBy('skill_id');
            $rows = [];
            foreach ($variants as $skillId => $levels) {
                $grades = $levels->first()->skill->grades->keyBy('grade');
                $now = $grades->get($kid->grade)?->expectation;
                $before = $grades->get($kid->grade - 1)?->expectation;
                $top = (int) $levels->max('level');
                $rows[$skillId] = match (true) {
                    $now === 'reinvestit' => ['level' => $top, 'status' => 'maitrise'],
                    $now === 'maitrise' && $before === 'apprend' => ['level' => min($top, 1 + (int) floor($this->yearDone($today) * $top)), 'status' => 'en_cours'],
                    default => ['level' => 1, 'status' => 'nouveau'],
                };
            }

            $codes = Skill::query()->pluck('id', 'code');
            foreach (self::OLD_SHEETS as $template => $byGrade) {
                [$code, $level] = $byGrade[$kid->grade] ?? [null, null];
                $skillId = $code !== null ? $codes[$code] ?? null : null;
                if ($skillId === null || ! isset($rows[$skillId])) {
                    continue;
                }
                $approved = Task::query()->where('task_type_id', TaskType::idFor(TaskType::HOMEWORK))->where('kid_id', $kid->id)
                    ->where('template', $template)->where('status', 'approved');
                $count = (clone $approved)->count();
                if ($count === 0) {
                    continue;
                }
                $rows[$skillId]['attempts'] = $count;
                $rows[$skillId]['last_seen_on'] = (clone $approved)->max('date');
                if ($rows[$skillId]['status'] !== 'maitrise') {
                    $rows[$skillId]['level'] = max($rows[$skillId]['level'], $level);
                    $rows[$skillId]['status'] = 'en_cours';
                }
            }

            foreach ($rows as $skillId => $row) {
                KidSkillProgress::query()->create(['kid_id' => $kid->id, 'skill_id' => $skillId] + $row);
                $created++;
            }
        }

        return $created;
    }

    /** How much of the school year (September to June) is behind us, 0 to 1. */
    private function yearDone(CarbonImmutable $today): float
    {
        $month = (int) $today->format('n');
        $index = $month >= 9 ? $month - 9 : ($month <= 6 ? $month + 3 : 10);

        return min(1, $index / 10);
    }
}
