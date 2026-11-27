<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Learning\AdjustSkillProgress;
use App\Actions\Learning\SetWorkingGrade;
use App\Actions\Learning\UpdateSkillProgress;
use App\Http\Controllers\Controller;
use App\Models\ExerciseVariant;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use App\Models\SkillGrade;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings › Progrès: for each kid, the skills their sheets practise (level,
 * status, last score, next review) and what their working grade covers this
 * month. A parent can change the working grade and move a skill by hand.
 */
class ProgressController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay();
        $month = (int) $today->format('n');

        $kids = Kid::query()->orderBy('position')->get()->map(function (Kid $kid) use ($month): array {
            $grade = $kid->workGrade();
            $tops = ExerciseVariant::query()->where('active', true)->where('grades_from', '<=', $grade)->where('grades_to', '>=', $grade)
                ->groupBy('skill_id')->selectRaw('skill_id, max(level) as top')->pluck('top', 'skill_id');
            $rows = KidSkillProgress::query()->with('skill.grades')->where('kid_id', $kid->id)->get()
                ->filter(fn (KidSkillProgress $p): bool => $tops->has($p->skill_id))
                ->map(function (KidSkillProgress $p) use ($grade, $tops): array {
                    $expected = $p->skill->grades->firstWhere('grade', $grade);

                    return [
                        'subject' => $p->skill->subject, 'domain' => $p->skill->domain, 'name' => $p->skill->name_fr, 'code' => $p->skill->code,
                        'skill' => $p->skill_id, 'expectation' => $expected?->expectation, 'window' => $expected !== null ? [$expected->window_start, $expected->window_end] : null,
                        'level' => $p->level, 'top' => (int) $tops[$p->skill_id], 'status' => $p->status, 'score' => $p->last_score,
                        'attempts' => $p->attempts, 'seen' => $p->last_seen_on, 'review' => $p->next_review_on, 'stuck' => $p->stuck_since !== null,
                    ];
                })
                ->sortBy(fn (array $r): string => $r['subject'].'|'.$r['domain'].'|'.$r['code'])->values();

            $thisMonth = SkillGrade::query()->with('skill')->where('grade', $grade)->whereIn('expectation', ['apprend', 'maitrise'])->get()
                ->filter(fn (SkillGrade $g): bool => $g->skill->active && $g->covers($month))
                ->map(fn (SkillGrade $g): array => ['name' => $g->skill->name_fr, 'subject' => $g->skill->subject, 'expectation' => $g->expectation, 'practised' => $tops->has($g->skill_id)])
                ->sortBy(fn (array $r): string => $r['subject'].'|'.$r['name'])->values();

            return ['kid' => $kid, 'grade' => $grade, 'rows' => $rows, 'thisMonth' => $thisMonth];
        });

        return view('modules.homework.progress', ['kids' => $kids, 'today' => $today, 'reviewDays' => UpdateSkillProgress::REVIEW_DAYS]);
    }

    /** POST progress/{kid}/grade (grade 1–6): the grade the kid's sheets follow. */
    public function grade(Request $request, string $kid, SetWorkingGrade $set): RedirectResponse
    {
        $model = Kid::query()->where('slug', $kid)->firstOrFail();
        $grade = (int) $request->validate(['grade' => ['required', 'integer', 'between:1,6']])['grade'];
        $set->handle($model, $grade);

        return redirect()->route('modules.homework.progress')->with('status', __(':name now works at :grade grade.', ['name' => $model->name, 'grade' => $grade]));
    }

    /** POST progress/{kid}/skills/{skill} (action up|down|master): move a kid on one skill. */
    public function adjust(Request $request, string $kid, int $skill, AdjustSkillProgress $adjust): RedirectResponse
    {
        $model = Kid::query()->where('slug', $kid)->firstOrFail();
        $action = (string) $request->validate(['action' => ['required', Rule::in(AdjustSkillProgress::ACTIONS)]])['action'];
        $adjust->handle($model, $skill, $action, CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay());

        return redirect()->route('modules.homework.progress')->with('status', __('Saved.'));
    }
}
