<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Learning\UpdateSkillProgress;
use App\Http\Controllers\Controller;
use App\Models\ExerciseVariant;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use App\Models\SkillGrade;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Settings › Progrès: for each kid, the skills their sheets practise (level,
 * status, last score, next review) and what their grade works on this month.
 */
class ProgressController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay();
        $month = (int) $today->format('n');

        $kids = Kid::query()->orderBy('position')->get()->map(function (Kid $kid) use ($month): array {
            $tops = ExerciseVariant::query()->where('active', true)->where('grades_from', '<=', $kid->grade)->where('grades_to', '>=', $kid->grade)
                ->groupBy('skill_id')->selectRaw('skill_id, max(level) as top')->pluck('top', 'skill_id');
            $rows = KidSkillProgress::query()->with('skill.grades')->where('kid_id', $kid->id)->get()
                ->filter(fn (KidSkillProgress $p): bool => $tops->has($p->skill_id))
                ->map(function (KidSkillProgress $p) use ($kid, $tops): array {
                    $grade = $p->skill->grades->firstWhere('grade', $kid->grade);

                    return [
                        'subject' => $p->skill->subject, 'domain' => $p->skill->domain, 'name' => $p->skill->name_fr, 'code' => $p->skill->code,
                        'expectation' => $grade?->expectation, 'window' => $grade !== null ? [$grade->window_start, $grade->window_end] : null,
                        'level' => $p->level, 'top' => (int) $tops[$p->skill_id], 'status' => $p->status, 'score' => $p->last_score,
                        'attempts' => $p->attempts, 'seen' => $p->last_seen_on, 'review' => $p->next_review_on, 'stuck' => $p->stuck_since !== null,
                    ];
                })
                ->sortBy(fn (array $r): string => $r['subject'].'|'.$r['domain'].'|'.$r['code'])->values();

            $thisMonth = SkillGrade::query()->with('skill')->where('grade', $kid->grade)->whereIn('expectation', ['apprend', 'maitrise'])->get()
                ->filter(fn (SkillGrade $g): bool => $g->skill->active && $g->covers($month))
                ->map(fn (SkillGrade $g): array => ['name' => $g->skill->name_fr, 'subject' => $g->skill->subject, 'expectation' => $g->expectation, 'practised' => $tops->has($g->skill_id)])
                ->sortBy(fn (array $r): string => $r['subject'].'|'.$r['name'])->values();

            return ['kid' => $kid, 'rows' => $rows, 'thisMonth' => $thisMonth];
        });

        return view('modules.homework.progress', ['kids' => $kids, 'today' => $today, 'reviewDays' => UpdateSkillProgress::REVIEW_DAYS]);
    }
}
