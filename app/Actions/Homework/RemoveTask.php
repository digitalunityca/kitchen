<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkStore;

/** Take a task off a day's list with no points ("not done"), as long as it isn't approved yet. */
class RemoveTask
{
    public function __construct(private readonly HomeworkStore $store) {}

    public function handle(string $kid, string $date, string $taskId): bool
    {
        $tasks = $this->store->tasks($kid, $date) ?? [];
        $kept = array_values(array_filter($tasks, fn (array $t): bool => ! ($t['id'] === $taskId && $t['status'] !== 'approved')));

        if (count($kept) === count($tasks)) {
            return false;
        }

        $this->store->putTasks($kid, $date, $kept);

        return true;
    }
}
