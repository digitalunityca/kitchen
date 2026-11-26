<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;

/** The kid taps "I'm done": the task waits for an adult to check it. */
class MarkTaskDone
{
    public function __construct(private readonly HomeworkStore $store) {}

    /** @return bool false when the task doesn't exist or isn't waiting to be done */
    public function handle(string $kid, string $date, string $taskId): bool
    {
        $tasks = $this->store->tasks($kid, $date) ?? [];

        foreach ($tasks as $i => $task) {
            if ($task['id'] === $taskId && $task['status'] === 'todo') {
                $tasks[$i]['status'] = 'done';
                $tasks[$i]['doneAt'] = now(HomeworkModule::TIMEZONE)->toIso8601String();
                $this->store->putTasks($kid, $date, $tasks);

                return true;
            }
        }

        return false;
    }
}
