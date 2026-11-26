<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;

/**
 * An adult checks a task: approve adds its points to the kid's total, redo sends
 * it back to the kid with no points.
 */
class ReviewTask
{
    public function __construct(private readonly HomeworkStore $store) {}

    /** A printed worksheet can be approved directly from its signed task link. */
    public function handle(string $kid, string $date, string $taskId, bool $approve, ?int $printedSeed = null): bool
    {
        return $this->store->synchronized(function () use ($kid, $date, $taskId, $approve, $printedSeed): bool {
            $data = $this->store->load();
            $tasks = $data['days'][$date][$kid] ?? [];

            foreach ($tasks as $i => $task) {
                $canReview = $printedSeed === null
                    ? in_array($task['status'], ['todo', 'done'], true)
                    : (int) $task['seed'] === $printedSeed && isset($task['worksheet']) && in_array($task['status'], ['todo', 'done'], true);

                if ($task['id'] !== $taskId || ! $canReview) {
                    continue;
                }

                $now = now(HomeworkModule::TIMEZONE)->toIso8601String();
                if ($approve) {
                    $tasks[$i]['status'] = 'approved';
                    $tasks[$i]['doneAt'] ??= $now;
                    $tasks[$i]['approvedAt'] = $now;
                    $data['ledger'][] = [
                        'kid' => $kid,
                        'points' => (int) $task['points'],
                        'reason' => HomeworkModule::TEMPLATES[$task['template']]['name'].' · '.$date,
                        'at' => $now,
                        'taskId' => $taskId,
                        'rewardId' => null,
                    ];
                } else {
                    $tasks[$i]['status'] = 'todo';
                    $tasks[$i]['doneAt'] = null;
                }

                $data['days'][$date][$kid] = $tasks;
                $this->store->save($data);

                return true;
            }

            return false;
        });
    }
}
