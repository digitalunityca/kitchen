<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Homework state in storage/app/private/homework.json: each day's tasks per kid,
 * the points ledger (every + with its reason) and each kid's word list for
 * the writing sheets. Days older than KEEP_DAYS are dropped on save.
 *
 * @phpstan-type Task array{id: string, template: string, points: int, status: string, seed: int, doneAt?: string|null, approvedAt?: string|null}
 * @phpstan-type Entry array{kid: string, points: int, reason: string, at: string, taskId?: string|null}
 */
class HomeworkStore
{
    private const FILE = 'homework.json';

    public const KEEP_DAYS = 60;

    public function __construct(private readonly Filesystem $disk) {}

    /** @return array{days: array<string, array<string, list<array<string, mixed>>>>, ledger: list<array<string, mixed>>, words: array<string, list<string>>, sheets: int} */
    public function load(): array
    {
        $data = $this->disk->exists(self::FILE) ? json_decode((string) $this->disk->get(self::FILE), true) : null;
        $data = is_array($data) ? $data : [];

        return [
            'days' => (array) ($data['days'] ?? []),
            'ledger' => array_values((array) ($data['ledger'] ?? [])),
            'words' => (array) ($data['words'] ?? []),
            'sheets' => (int) ($data['sheets'] ?? 0),
        ];
    }

    /** @param  array{days: array<string, mixed>, ledger: list<array<string, mixed>>, words: array<string, list<string>>, sheets?: int}  $data */
    public function save(array $data): void
    {
        $cutoff = now(HomeworkModule::TIMEZONE)->subDays(self::KEEP_DAYS)->toDateString();
        $data['days'] = array_filter($data['days'], fn (string $date): bool => $date >= $cutoff, ARRAY_FILTER_USE_KEY);
        ksort($data['days']);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $temporary = self::FILE.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            if (! $this->disk->put($temporary, $json) || ! $this->disk->move($temporary, self::FILE)) {
                throw new RuntimeException('Could not save homework.');
            }
        } finally {
            $this->disk->delete($temporary);
        }
    }

    public function synchronized(Closure $operation): mixed
    {
        return Cache::lock('homework:store', 10)->block(5, $operation);
    }

    public function update(Closure $change): void
    {
        $this->synchronized(function () use ($change): void {
            $data = $this->load();
            $change($data);
            $this->save($data);
        });
    }

    /** @return list<array<string, mixed>>|null null when that day was never generated */
    public function tasks(string $kid, string $date): ?array
    {
        $tasks = $this->load()['days'][$date][$kid] ?? null;

        return is_array($tasks) ? array_values($tasks) : null;
    }

    /** @param  list<array<string, mixed>>  $tasks */
    public function putTasks(string $kid, string $date, array $tasks): void
    {
        $data = $this->load();
        $data['days'][$date][$kid] = array_values($tasks);
        $this->save($data);
    }

    public function balance(string $kid): int
    {
        return (int) collect($this->load()['ledger'])->where('kid', $kid)->sum('points');
    }

    /** @return list<array<string, mixed>> newest first */
    public function history(string $kid, int $limit = 10): array
    {
        return collect($this->load()['ledger'])->where('kid', $kid)->reverse()->take($limit)->values()->all();
    }

    /** A ledger line: + for an approved task (taskId), − for a reward bought (rewardId). */
    public function addPoints(string $kid, int $points, string $reason, ?string $taskId = null, ?string $rewardId = null): void
    {
        $data = $this->load();
        $data['ledger'][] = ['kid' => $kid, 'points' => $points, 'reason' => $reason, 'at' => now(HomeworkModule::TIMEZONE)->toIso8601String(), 'taskId' => $taskId, 'rewardId' => $rewardId];
        $this->save($data);
    }

    /**
     * The kid's numbers: tasks done and points earned this week (Monday start) and
     * in total, and points spent in the store.
     *
     * @return array{weekTasks: int, weekPoints: int, totalTasks: int, earned: int, spent: int}
     */
    public function stats(string $kid): array
    {
        $weekStart = now(HomeworkModule::TIMEZONE)->startOfWeek()->toDateString();
        $entries = collect($this->load()['ledger'])->where('kid', $kid);
        $tasks = $entries->filter(fn (array $e): bool => ! empty($e['taskId']));
        $week = $tasks->filter(fn (array $e): bool => substr((string) $e['at'], 0, 10) >= $weekStart);

        return [
            'weekTasks' => $week->count(),
            'weekPoints' => (int) $week->sum('points'),
            'totalTasks' => $tasks->count(),
            'earned' => (int) $entries->where('points', '>', 0)->sum('points'),
            'spent' => (int) -$entries->where('points', '<', 0)->sum('points'),
        ];
    }

    /**
     * Give every printed worksheet (a task with a generated sheet) that has no number
     * yet the next household-wide sheet number, oldest day first. The number is printed
     * on the paper and shown in History, so a parent can match the two.
     */
    public function numberSheets(): void
    {
        $this->update(function (array &$data): void {
            ksort($data['days']);
            foreach ($data['days'] as $date => $kids) {
                foreach ($kids as $kid => $tasks) {
                    foreach ($tasks as $i => $task) {
                        if (isset($task['worksheet']) && ! isset($task['number'])) {
                            $data['days'][$date][$kid][$i]['number'] = ++$data['sheets'];
                        }
                    }
                }
            }
        });
    }

    /** "#0042" */
    public static function sheetLabel(int $number): string
    {
        return '#'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /** @return list<string> */
    public function words(string $kid): array
    {
        $words = $this->load()['words'][$kid] ?? null;

        return is_array($words) && $words !== [] ? array_values($words) : HomeworkModule::DEFAULT_WORDS;
    }

    /** @param  list<string>  $words */
    public function putWords(string $kid, array $words): void
    {
        $data = $this->load();
        $data['words'][$kid] = array_values($words);
        $this->save($data);
    }
}
