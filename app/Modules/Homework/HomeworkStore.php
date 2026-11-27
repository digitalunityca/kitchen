<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Models\HomeworkTask;
use App\Models\HubSetting;
use App\Models\Kid;
use App\Models\KidWordList;
use App\Models\LedgerEntry;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Homework state in the hub's database: each day's tasks per kid
 * (homework_tasks), the points ledger (homework_ledger, every + and − with its
 * reason), each kid's word list for the writing sheets (homework_words), and two
 * counters in hub_settings (the sheet numbers, each kid's "seen" position).
 * Only homework rows of the tasks table (HomeworkTask); chores have their own code.
 * load()/save() give the whole state as one array, the shape the homework code
 * has always used, with kids named by their slug ("theo"); the tables point at the
 * kids table by kid_id. Days older than KEEP_DAYS are dropped on save.
 *
 * @phpstan-type Task array{id: string, template: string, points: int, status: string, seed: int, doneAt?: string|null, approvedAt?: string|null}
 * @phpstan-type Entry array{kid: string, points: int, reason: string, at: string, taskId?: string|null}
 */
class HomeworkStore
{
    public const KEEP_DAYS = 60;

    private const SHEETS_KEY = 'homework.sheets';

    private const SEEN_KEY = 'homework.seen.';

    /** @return array{days: array<string, array<string, list<array<string, mixed>>>>, ledger: list<array<string, mixed>>, words: array<string, list<string>>, sheets: int, seen: array<string, int>} */
    public function load(): array
    {
        $slugs = Kid::slugs();
        $days = [];
        foreach (HomeworkTask::query()->orderBy('date')->orderBy('kid_id')->orderBy('position')->get() as $task) {
            $days[$task->date->format('Y-m-d')][$slugs[$task->kid_id]][] = $task->data;
        }
        foreach ($days as &$kids) {
            ksort($kids);
        }
        unset($kids);

        $seen = [];
        foreach (HubSetting::query()->where('key', 'like', self::SEEN_KEY.'%')->get() as $setting) {
            $seen[substr($setting->key, strlen(self::SEEN_KEY))] = (int) $setting->value;
        }

        return [
            'days' => $days,
            'ledger' => LedgerEntry::query()->orderBy('id')->get()->map(fn (LedgerEntry $e): array => self::entry($e, $slugs))->all(),
            'words' => KidWordList::query()->get()->mapWithKeys(fn (KidWordList $w): array => [$slugs[$w->kid_id] => $w->words])->all(),
            'sheets' => (int) HubSetting::get(self::SHEETS_KEY, '0'),
            'seen' => $seen,
        ];
    }

    /** @param  array{days: array<string, mixed>, ledger: list<array<string, mixed>>, words: array<string, list<string>>, sheets?: int, seen?: array<string, int>}  $data */
    public function save(array $data): void
    {
        $cutoff = now(HomeworkModule::TIMEZONE)->subDays(self::KEEP_DAYS)->toDateString();
        $data['days'] = array_filter($data['days'], fn (string $date): bool => $date >= $cutoff, ARRAY_FILTER_USE_KEY);

        DB::transaction(function () use ($data): void {
            HomeworkTask::query()->delete();
            foreach ($data['days'] as $date => $kids) {
                foreach ((array) $kids as $kid => $tasks) {
                    $this->insertTasks((string) $kid, (string) $date, array_values((array) $tasks));
                }
            }

            // The ledger only grows: append the new lines, unless an older line changed.
            $slugs = Kid::slugs();
            $stored = LedgerEntry::query()->orderBy('id')->get()->map(fn (LedgerEntry $e): array => self::entry($e, $slugs))->all();
            $ledger = array_map(fn (array $e): array => self::entry($e), array_values($data['ledger']));
            if (array_slice($ledger, 0, count($stored)) !== $stored) {
                LedgerEntry::query()->delete();
                $stored = [];
            }
            foreach (array_slice($ledger, count($stored)) as $entry) {
                LedgerEntry::query()->create(self::row($entry));
            }

            $ids = array_flip($slugs);
            KidWordList::query()->whereNotIn('kid_id', array_map(fn (int|string $kid): int => $ids[(string) $kid] ?? 0, array_keys($data['words'])))->delete();
            foreach ($data['words'] as $kid => $words) {
                KidWordList::query()->updateOrCreate(['kid_id' => Kid::idFor((string) $kid)], ['words' => array_values($words)]);
            }

            if (isset($data['sheets'])) {
                HubSetting::put(self::SHEETS_KEY, (string) (int) $data['sheets']);
            }
            foreach ($data['seen'] ?? [] as $kid => $position) {
                HubSetting::put(self::SEEN_KEY.$kid, (string) (int) $position);
            }
        });
    }

    /** @param  list<array<string, mixed>>  $tasks */
    private function insertTasks(string $kid, string $date, array $tasks): void
    {
        $kidId = Kid::idFor($kid);
        foreach ($tasks as $position => $task) {
            HomeworkTask::query()->create([
                'kid_id' => $kidId, 'date' => $date, 'task_id' => (string) $task['id'], 'position' => $position,
                'template' => (string) $task['template'], 'status' => (string) $task['status'],
                'number' => isset($task['number']) ? (int) $task['number'] : null, 'data' => $task,
            ]);
        }
    }

    /**
     * A ledger line in the shape the homework code uses.
     *
     * The time is an ISO 8601 string in the household's time zone (stored as UTC).
     *
     * @param  LedgerEntry|array<string, mixed>  $e
     * @param  array<int, string>  $slugs  kid id → slug, for a database row
     * @return array{kid: string, points: int, reason: string, at: string, taskId: string|null, rewardId: string|null, taskRef: int|null}
     */
    private static function entry(LedgerEntry|array $e, array $slugs = []): array
    {
        if ($e instanceof LedgerEntry) {
            return ['kid' => $slugs[$e->kid_id], 'points' => (int) $e->points, 'reason' => $e->reason,
                'at' => $e->at->setTimezone(HomeworkModule::TIMEZONE)->toIso8601String(), 'taskId' => $e->task_id, 'rewardId' => $e->reward_id, 'taskRef' => $e->task_ref];
        }

        return [
            'kid' => (string) $e['kid'], 'points' => (int) $e['points'], 'reason' => (string) $e['reason'],
            'at' => CarbonImmutable::parse((string) $e['at'], HomeworkModule::TIMEZONE)->setTimezone(HomeworkModule::TIMEZONE)->toIso8601String(),
            'taskId' => isset($e['taskId']) && $e['taskId'] !== '' ? (string) $e['taskId'] : null,
            'rewardId' => isset($e['rewardId']) && $e['rewardId'] !== '' ? (string) $e['rewardId'] : null,
            'taskRef' => isset($e['taskRef']) ? (int) $e['taskRef'] : null,
        ];
    }

    /** @param  array{kid: string, points: int, reason: string, at: string, taskId: string|null, rewardId: string|null, taskRef: int|null}  $entry */
    private static function row(array $entry): array
    {
        return ['kid_id' => Kid::idFor($entry['kid']), 'points' => $entry['points'], 'reason' => mb_substr($entry['reason'], 0, 200),
            'at' => CarbonImmutable::parse($entry['at'], HomeworkModule::TIMEZONE)->utc(), 'task_id' => $entry['taskId'], 'reward_id' => $entry['rewardId'], 'task_ref' => $entry['taskRef']];
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

    /** @return list<array<string, mixed>>|null null when that day has no tasks */
    public function tasks(string $kid, string $date): ?array
    {
        $tasks = HomeworkTask::query()->whereHas('kid', fn ($q) => $q->where('slug', $kid))->whereDate('date', $date)->orderBy('position')->get();

        return $tasks->isEmpty() ? null : $tasks->map(fn (HomeworkTask $t): array => $t->data)->all();
    }

    /** @param  list<array<string, mixed>>  $tasks */
    public function putTasks(string $kid, string $date, array $tasks): void
    {
        DB::transaction(function () use ($kid, $date, $tasks): void {
            HomeworkTask::query()->where('kid_id', Kid::idFor($kid))->whereDate('date', $date)->delete();
            $this->insertTasks($kid, $date, array_values($tasks));
        });
    }

    public function balance(string $kid): int
    {
        return (int) LedgerEntry::query()->whereHas('kid', fn ($q) => $q->where('slug', $kid))->sum('points');
    }

    /** @return list<array<string, mixed>> newest first */
    public function history(string $kid, int $limit = 10): array
    {
        $slugs = Kid::slugs();

        return LedgerEntry::query()->whereHas('kid', fn ($q) => $q->where('slug', $kid))->orderByDesc('id')->limit($limit)->get()
            ->map(fn (LedgerEntry $e): array => self::entry($e, $slugs))->all();
    }

    /** A ledger line: + for an approved sheet (taskId) or chore (taskRef, the task row), − for a reward bought (rewardId). */
    public function addPoints(string $kid, int $points, string $reason, ?string $taskId = null, ?string $rewardId = null, ?int $taskRef = null): void
    {
        LedgerEntry::query()->create(self::row(self::entry([
            'kid' => $kid, 'points' => $points, 'reason' => $reason,
            'at' => now(HomeworkModule::TIMEZONE)->toIso8601String(), 'taskId' => $taskId, 'rewardId' => $rewardId, 'taskRef' => $taskRef,
        ])));
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

    /** The kid opened their area: approvals up to now no longer show as a badge on their button. */
    public function markSeen(string $kid): void
    {
        HubSetting::put(self::SEEN_KEY.$kid, (string) LedgerEntry::query()->count());
    }

    /** Points from sheets and chores approved since the kid last opened their area (the "+15" on their button). */
    public function unseenPoints(string $kid): int
    {
        $seen = HubSetting::get(self::SEEN_KEY.$kid);
        if ($seen === null) {
            $this->markSeen($kid); // first look: start counting from now

            return 0;
        }

        // Ledger lines after the first $seen ones (positions, as the events cursor counts them).
        $after = LedgerEntry::query()->orderBy('id')->skip((int) $seen)->take(PHP_INT_MAX)->get();

        $kidId = (int) array_search($kid, Kid::slugs(), true);

        return (int) $after->filter(fn (LedgerEntry $e): bool => $e->kid_id === $kidId && ($e->task_id !== null || $e->task_ref !== null))->sum('points');
    }

    /** "#0042" */
    public static function sheetLabel(int $number): string
    {
        return '#'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /** @return list<string> */
    public function words(string $kid): array
    {
        $words = KidWordList::query()->whereHas('kid', fn ($q) => $q->where('slug', $kid))->first()?->words;

        return is_array($words) && $words !== [] ? array_values($words) : HomeworkModule::DEFAULT_WORDS;
    }

    /** @param  list<string>  $words */
    public function putWords(string $kid, array $words): void
    {
        KidWordList::query()->updateOrCreate(['kid_id' => Kid::idFor($kid)], ['words' => array_values($words)]);
    }
}
