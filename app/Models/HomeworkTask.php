<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The homework sheets among the tasks (task type "homework"). Queries only see
 * homework, and a new row gets its type, exercise type, points, score and times
 * from the task data, so the homework code keeps working with plain arrays.
 */
class HomeworkTask extends Task
{
    protected static function booted(): void
    {
        static::addGlobalScope('homework', fn (Builder $query) => $query->where('task_type_id', TaskType::idFor(TaskType::HOMEWORK)));

        static::creating(function (HomeworkTask $task): void {
            $data = (array) $task->data;
            $task->task_type_id = TaskType::idFor(TaskType::HOMEWORK);
            $task->exercise_type_id ??= ExerciseType::idFor((string) $task->template);
            $task->points = (int) ($data['points'] ?? 0);
            $task->score = $data['score'] ?? null;
            $task->done_at = self::utc($data['doneAt'] ?? null);
            $task->approved_at = self::utc($data['approvedAt'] ?? null);
        });
    }

    private static function utc(?string $time): ?CarbonImmutable
    {
        return $time ? CarbonImmutable::parse($time)->utc() : null;
    }
}
