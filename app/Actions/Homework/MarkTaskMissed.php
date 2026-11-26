<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;

/**
 * A parent marks a sheet "Not done": no points, it stays in History as not done,
 * and it no longer counts toward the day's limit or blocks the evening rewards.
 */
class MarkTaskMissed
{
    public function __construct(private readonly HomeworkStore $store) {}

    public function handle(string $kid, string $date, string $taskId): bool
    {
        return $this->store->synchronized(function () use ($kid, $date, $taskId): bool {
            $data = $this->store->load();
            foreach ($data['days'][$date][$kid] ?? [] as $i => $task) {
                if ($task['id'] === $taskId && in_array($task['status'], ['todo', 'done'], true)) {
                    $data['days'][$date][$kid][$i]['status'] = 'missed';
                    $data['days'][$date][$kid][$i]['checkedAt'] = now(HomeworkModule::TIMEZONE)->toIso8601String();
                    $this->store->save($data);

                    return true;
                }
            }

            return false;
        });
    }
}
