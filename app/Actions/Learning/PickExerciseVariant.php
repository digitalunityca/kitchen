<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseVariant;
use App\Models\KidSkillProgress;
use App\Models\Task;
use App\Models\TaskType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Which skill and level the next sheet of a type practises, for one kid:
 * 0. the prerequisite of a skill the kid had to go over again at its first level;
 * 1. a review that is due, or a skill marked "À revoir";
 * 2. a skill of this month's window for the kid's grade, not yet mastered
 *    (★ before →, lowest level first);
 * 3. upkeep of mastered or reused skills, the one seen longest ago first;
 * 4. anything else this sheet type offers the grade.
 * A skill under one the kid already masters (or works on at level 2+) never
 * counts as 2: it is known, upkeep only. July and August skip 2. Variety: a skill already on today's sheets goes last,
 * and the same skill and level is skipped within 3 school days (reviews excepted).
 */
class PickExerciseVariant
{
    public const VARIETY_SCHOOL_DAYS = 3;

    /**
     * @param  list<int>  $takenToday  skills already on today's sheets (this batch included)
     * @return array{variant: ExerciseVariant, level: int, reason: string}|null null when the type has no levels for this grade
     */
    public function handle(int $kidId, int $grade, string $template, CarbonInterface $day, array $takenToday = []): ?array
    {
        $variants = ExerciseVariant::query()->with('skill.grades', 'skill.prerequisites')
            ->whereHas('exerciseType', fn ($q) => $q->where('slug', $template))
            ->where('active', true)->where('grades_from', '<=', $grade)->where('grades_to', '>=', $grade)
            ->orderBy('level')->get();
        if ($variants->isEmpty()) {
            return null;
        }

        $bySkill = $variants->groupBy('skill_id');
        $progress = KidSkillProgress::query()->where('kid_id', $kidId)->get()->keyBy('skill_id');
        // Skills under one the kid already masters, or works on at level 2 or more, are known: upkeep only.
        $known = array_flip(app(Prerequisites::class)->below(
            $progress->filter(fn (KidSkillProgress $p): bool => $p->status === 'maitrise' || ($p->status === 'en_cours' && $p->level >= 2))->keys()
        ));
        $recent = $this->recent($kidId, $day);
        $month = (int) $day->format('n');
        $schoolMonth = ! in_array($month, [7, 8], true);

        $candidates = [];
        foreach ($bySkill as $skillId => $levels) {
            $skill = $levels->first()->skill;
            $p = $progress->get($skillId);
            $grades = $skill->grades->firstWhere('grade', $grade);
            $level = $this->level($p?->level, $levels);
            $status = $p?->status ?? 'nouveau';

            $priority = match (true) {
                $status === 'a_revoir', $status === 'maitrise' && $p?->next_review_on !== null && $p->next_review_on->lte($day) => 1,
                $schoolMonth && $grades !== null && $grades->expectation !== 'reinvestit' && $grades->covers($month) && $status !== 'maitrise' && ! isset($known[$skillId]) => 2,
                $status === 'maitrise' || $grades?->expectation === 'reinvestit' || isset($known[$skillId]) => 3,
                default => 4,
            };
            $candidates[$skillId] = [
                'skill' => $skillId, 'level' => $level, 'priority' => $priority,
                'order' => [$grades?->expectation === 'maitrise' ? 0 : 1, $priority === 3 ? (string) ($p?->last_seen_on?->format('Y-m-d') ?? '0') : $level],
            ];

            // Stuck at the first level: practise its prerequisite (when this sheet type has it).
            if ($status === 'a_revoir' && ($p?->level ?? 1) === 1) {
                foreach ($skill->prerequisites as $pre) {
                    if ($bySkill->has($pre->id)) {
                        $candidates[$pre->id] = ['skill' => $pre->id, 'level' => $this->level($progress->get($pre->id)?->level ?? PHP_INT_MAX, $bySkill[$pre->id]), 'priority' => 0, 'order' => [0, 0]];
                    }
                }
            }
        }

        usort($candidates, fn (array $a, array $b): int => [in_array($a['skill'], $takenToday, true), $a['priority'], $a['order']]
            <=> [in_array($b['skill'], $takenToday, true), $b['priority'], $b['order']]);
        $fresh = array_values(array_filter($candidates, fn (array $c): bool => $c['priority'] <= 1 || ! in_array($c['skill'].':'.$c['level'], $recent, true)));
        $choice = ($fresh ?: $candidates)[0];

        return [
            'variant' => $bySkill[$choice['skill']]->firstWhere('level', $choice['level']),
            'level' => $choice['level'],
            'reason' => ['prerequisite', 'review', 'month', 'upkeep', 'other'][$choice['priority']],
        ];
    }

    /** The kid's level, kept within the levels this grade has (and to one that exists). */
    private function level(?int $wanted, Collection $levels): int
    {
        $available = $levels->pluck('level')->sort()->values();
        $wanted ??= $available->first();

        return $available->filter(fn (int $l): bool => $l <= $wanted)->last() ?? $available->first();
    }

    /** @return list<string> "skill:level" of the kid's sheets in the last school days */
    private function recent(int $kidId, CarbonInterface $day): array
    {
        $since = $day->copy();
        for ($n = 0; $n < self::VARIETY_SCHOOL_DAYS; $n++) {
            do {
                $since = $since->subDay();
            } while ($since->isWeekend());
        }

        return Task::query()->where('task_type_id', TaskType::idFor(TaskType::HOMEWORK))->where('kid_id', $kidId)
            ->whereNotNull('skill_id')->whereDate('date', '>=', $since->toDateString())->whereDate('date', '<=', $day->toDateString())
            ->get(['skill_id', 'level'])->map(fn (Task $t): string => $t->skill_id.':'.$t->level)->unique()->values()->all();
    }
}
