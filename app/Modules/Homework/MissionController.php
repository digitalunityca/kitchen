<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Missions\ParentMissions;
use App\Http\Controllers\Controller;
use App\Models\Kid;
use App\Models\Task;
use App\Models\TaskType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings › Récompenses d'un parent (settings PIN): give a mission (what to do,
 * points, maybe a date) to one or more kids, give bonus points right away, and
 * see or take back the open missions.
 */
class MissionController extends Controller
{
    public function index(): View
    {
        $kids = Kid::query()->orderBy('position')->get();

        return view('modules.homework.missions', [
            'kids' => $kids,
            'open' => Task::query()->where('task_type_id', TaskType::idFor(TaskType::PARENT_MISSION))->whereIn('status', ['todo', 'done'])
                ->orderBy('id')->get()->map(fn (Task $m): array => [
                    'id' => $m->id, 'kid' => $kids->firstWhere('id', $m->kid_id)?->name, 'title' => (string) ($m->data['title'] ?? ''),
                    'points' => $m->points, 'due' => $m->data['due'] ?? null, 'waiting' => $m->status === 'done',
                ]),
        ]);
    }

    public function store(Request $request, ParentMissions $missions): RedirectResponse
    {
        $data = $request->validate([
            'kids' => ['required', 'array', 'min:1'],
            'kids.*' => ['integer', Rule::exists('kids', 'id')],
            'title' => ['required', 'string', 'max:80'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'due' => ['nullable', 'date'],
        ]);
        $missions->create(array_map('intval', $data['kids']), trim($data['title']), (int) $data['points'], $data['due'] ?? null);

        return redirect()->route('modules.homework.missions')->with('status', __('Mission given.'));
    }

    public function bonus(Request $request, ParentMissions $missions): RedirectResponse
    {
        $data = $request->validate([
            'kid' => ['required', 'integer', Rule::exists('kids', 'id')],
            'reason' => ['required', 'string', 'max:80'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $missions->bonus((int) $data['kid'], trim($data['reason']), (int) $data['points']);

        return redirect()->route('modules.homework.missions')->with('status', __('Bonus given.'));
    }

    public function cancel(int $task, ParentMissions $missions): RedirectResponse
    {
        $missions->cancel($task);

        return redirect()->route('modules.homework.missions')->with('status', __('Mission removed.'));
    }
}
