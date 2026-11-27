<?php

declare(strict_types=1);

namespace App\Actions\HomeHelp;

use App\Models\Chore;
use App\Models\Task;
use App\Models\TaskType;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonInterface;

/**
 * The kid says "J'ai fini": the chore waits for a parent's check. Refused when
 * the chore isn't theirs today or was already done as many times as it counts.
 */
class MarkChoreDone
{
    public function __construct(private readonly HomeworkStore $store) {}

    public function handle(int $kidId, int $choreId, CarbonInterface $day): bool
    {
        return $this->store->synchronized(function () use ($kidId, $choreId, $day): bool {
            $chore = Chore::query()->for($kidId, $day)->whereKey($choreId)->first();
            if ($chore === null) {
                return false;
            }

            $today = Task::query()->where('task_type_id', TaskType::idFor(TaskType::HOME_HELP))
                ->where('kid_id', $kidId)->whereDate('date', $day->toDateString());
            if ((clone $today)->where('chore_id', $chore->id)->whereIn('status', ['done', 'approved'])->count() >= $chore->per_day) {
                return false;
            }

            $number = (int) (clone $today)->count() + 1;
            Task::query()->create([
                'kid_id' => $kidId, 'task_type_id' => TaskType::idFor(TaskType::HOME_HELP), 'chore_id' => $chore->id,
                'date' => $day->toDateString(), 'task_id' => 'c'.$number, 'position' => $number, 'status' => 'done',
                'points' => $chore->points, 'done_at' => now(),
                'data' => ['name' => $chore->name_fr, 'emoji' => $chore->emoji, 'points' => $chore->points],
            ]);

            return true;
        });
    }
}
