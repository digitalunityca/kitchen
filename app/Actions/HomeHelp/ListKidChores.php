<?php

declare(strict_types=1);

namespace App\Actions\HomeHelp;

use App\Models\Chore;
use App\Models\Task;
use App\Models\TaskType;
use Carbon\CarbonInterface;

/**
 * The chores a kid sees today, with how many times each was done: waiting for a
 * parent, approved, and how many more times it counts today.
 */
class ListKidChores
{
    /** @return list<array{id: int, name: string, emoji: string, points: int, perDay: int, waiting: int, approved: int, left: int}> */
    public function handle(int $kidId, CarbonInterface $day): array
    {
        $done = Task::query()->where('task_type_id', TaskType::idFor(TaskType::HOME_HELP))
            ->where('kid_id', $kidId)->whereDate('date', $day->toDateString())->whereIn('status', ['done', 'approved'])
            ->get(['chore_id', 'status'])->groupBy('chore_id');

        return Chore::query()->for($kidId, $day)->get()->map(function (Chore $chore) use ($done): array {
            $tasks = $done->get($chore->id, collect());
            $waiting = $tasks->where('status', 'done')->count();
            $approved = $tasks->where('status', 'approved')->count();

            return [
                'id' => $chore->id, 'name' => $chore->name_fr, 'emoji' => $chore->emoji, 'points' => $chore->points, 'perDay' => $chore->per_day,
                'waiting' => $waiting, 'approved' => $approved, 'left' => max(0, $chore->per_day - $waiting - $approved),
            ];
        })->all();
    }
}
