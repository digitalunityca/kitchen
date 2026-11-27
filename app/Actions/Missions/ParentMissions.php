<?php

declare(strict_types=1);

namespace App\Actions\Missions;

use App\Actions\Homework\ReviewTask;
use App\Models\Kid;
use App\Models\Task;
use App\Models\TaskType;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rewards from a parent:
 * - a mission: something to do ("Aider à ranger le garage"), worth some points,
 *   maybe by a date. The kid taps "J'ai fini", a parent scores it like a sheet
 *   (Bien / Quelques erreurs: full points, À revoir: half) or says it wasn't done;
 * - a bonus: points given right away for something already done.
 * Both are tasks of type parent_mission; the points go to the ledger with a
 * link to the task (task_ref), so the dashboard celebrates them.
 */
class ParentMissions
{
    public function __construct(private readonly HomeworkStore $store) {}

    /** @param  list<int>  $kidIds */
    public function create(array $kidIds, string $title, int $points, ?string $due): int
    {
        $created = 0;
        foreach (Kid::query()->whereKey($kidIds)->get() as $kid) {
            $this->task($kid->id, $title, $points, 'todo', ['due' => $due]);
            $created++;
        }

        return $created;
    }

    public function bonus(int $kidId, string $reason, int $points): void
    {
        $this->store->synchronized(function () use ($kidId, $reason, $points): void {
            $task = $this->task($kidId, $reason, $points, 'approved', ['bonus' => true]);
            $task->update(['approved_at' => now(), 'done_at' => now()]);
            $this->store->addPoints(Kid::query()->findOrFail($kidId)->slug, $points, $reason, taskRef: $task->id);
        });
    }

    /** The kid says "J'ai fini". */
    public function done(int $kidId, int $taskId): bool
    {
        return $this->query($kidId)->whereKey($taskId)->where('status', 'todo')->update(['status' => 'done', 'done_at' => now()]) === 1;
    }

    /** A parent's decision: bien | erreurs | revoir (points) or missed (none; the mission stays open). */
    public function review(int $kidId, int $taskId, string $decision): bool
    {
        return $this->store->synchronized(function () use ($kidId, $taskId, $decision): bool {
            $task = $this->query($kidId)->whereKey($taskId)->where('status', 'done')->first();
            if ($task === null) {
                return false;
            }
            if ($decision === 'missed') {
                $task->update(['status' => 'todo', 'done_at' => null]);

                return true;
            }

            $score = in_array($decision, ReviewTask::SCORES, true) ? $decision : 'bien';
            $points = ReviewTask::pointsFor($task->points, $score);
            $task->update(['status' => 'approved', 'score' => $score, 'approved_at' => now()]);
            $this->store->addPoints(Kid::query()->findOrFail($kidId)->slug, $points, (string) ($task->data['title'] ?? ''), taskRef: $task->id);

            return true;
        });
    }

    /** A parent takes back a mission that wasn't started (no points were given). */
    public function cancel(int $taskId): bool
    {
        return Task::query()->where('task_type_id', TaskType::idFor(TaskType::PARENT_MISSION))->whereKey($taskId)
            ->whereIn('status', ['todo', 'done'])->update(['status' => 'cancelled']) === 1;
    }

    /** @return Collection<int, Task> the kid's open missions (to do or waiting for a parent), oldest first */
    public function open(int $kidId): Collection
    {
        return $this->query($kidId)->whereIn('status', ['todo', 'done'])->orderBy('id')->get();
    }

    private function query(int $kidId): Builder
    {
        return Task::query()->where('task_type_id', TaskType::idFor(TaskType::PARENT_MISSION))->where('kid_id', $kidId);
    }

    /** @param  array<string, mixed>  $extra */
    private function task(int $kidId, string $title, int $points, string $status, array $extra): Task
    {
        $today = CarbonImmutable::now(HomeworkModule::TIMEZONE)->toDateString();
        $task = Task::query()->create([
            'kid_id' => $kidId, 'task_type_id' => TaskType::idFor(TaskType::PARENT_MISSION), 'date' => $today,
            'task_id' => 'm'.uniqid(), 'position' => 0, 'status' => $status, 'points' => $points,
            'data' => ['title' => $title, 'points' => $points] + array_filter($extra, fn ($v) => $v !== null),
        ]);
        $task->update(['task_id' => 'm'.$task->id]);

        return $task;
    }
}
