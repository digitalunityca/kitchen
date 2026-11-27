<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Actions\Learning\UpdateSkillProgress;
use App\Models\Kid;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;

/**
 * An adult checks a task: approve adds its points to the kid's total, redo sends
 * it back to the kid with no points. An approval carries the parent's score:
 * "bien" and "erreurs" (a few mistakes) give the full points, "revoir" (to go
 * over again) half, rounded up. The score will steer the next sheets' level.
 */
class ReviewTask
{
    public const SCORES = ['bien', 'erreurs', 'revoir'];

    public function __construct(private readonly HomeworkStore $store, private readonly UpdateSkillProgress $progress) {}

    /** Points a sheet earns with that score. */
    public static function pointsFor(int $points, string $score): int
    {
        return $score === 'revoir' ? (int) ceil($points / 2) : $points;
    }

    /** A printed worksheet can be approved directly from its signed task link. */
    public function handle(string $kid, string $date, string $taskId, bool $approve, ?int $printedSeed = null, string $score = 'bien'): bool
    {
        $score = in_array($score, self::SCORES, true) ? $score : 'bien';

        return $this->store->synchronized(function () use ($kid, $date, $taskId, $approve, $printedSeed, $score): bool {
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
                    $tasks[$i]['score'] = $score;
                    $data['ledger'][] = [
                        'kid' => $kid,
                        'points' => self::pointsFor((int) $task['points'], $score),
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

                // The score moves the kid on the sheet's skill (next level, review, step back).
                if ($approve && isset($task['skillId'])) {
                    $profile = Kid::query()->where('slug', $kid)->firstOrFail();
                    $this->progress->handle($profile->id, (int) $task['skillId'], $profile->grade, $score, now(HomeworkModule::TIMEZONE)->startOfDay());
                }

                return true;
            }

            return false;
        });
    }
}
