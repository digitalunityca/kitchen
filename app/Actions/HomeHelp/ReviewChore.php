<?php

declare(strict_types=1);

namespace App\Actions\HomeHelp;

use App\Models\Kid;
use App\Models\Task;
use App\Models\TaskType;
use App\Modules\Homework\HomeworkStore;

/**
 * A parent checks a chore the kid marked done: approve adds its points (a
 * ledger line pointing at the task), "not done" gives none. A chore is paid once.
 */
class ReviewChore
{
    public function __construct(private readonly HomeworkStore $store) {}

    public function handle(int $kidId, int $taskId, bool $approve): bool
    {
        return $this->store->synchronized(function () use ($kidId, $taskId, $approve): bool {
            $task = Task::query()->where('task_type_id', TaskType::idFor(TaskType::HOME_HELP))
                ->where('kid_id', $kidId)->whereKey($taskId)->where('status', 'done')->first();
            if ($task === null) {
                return false;
            }

            if ($approve) {
                $task->update(['status' => 'approved', 'approved_at' => now()]);
                $this->store->addPoints(Kid::query()->findOrFail($kidId)->slug, $task->points, (string) ($task->data['name'] ?? ''), taskRef: $task->id);
            } else {
                $task->update(['status' => 'missed']);
            }

            return true;
        });
    }
}
