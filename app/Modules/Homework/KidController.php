<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Access\AttemptSettingsUnlock;
use App\Actions\Homework\AttemptKidUnlock;
use App\Actions\Homework\MarkTaskMissed;
use App\Actions\Homework\PrintHomework;
use App\Actions\Homework\RedeemReward;
use App\Actions\Homework\ReviewTask;
use App\Dashboard\ModuleRegistry;
use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireSettingsPin;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The kid's own homework area, inside the overlay: a PIN screen, then the kid's
 * home (points, today's progress, four tiles) and the Homework, Rewards, History
 * and News screens. Every screen but the PIN needs that kid signed in (KidSession);
 * approving homework and buying rewards also need the parent PIN, every time.
 */
class KidController extends Controller
{
    public const SCREENS = ['home', 'homework', 'rewards', 'history', 'news'];

    /** How far back "To check" and History look. */
    private const HISTORY_DAYS = 14;

    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly HomeworkStore $store,
        private readonly KidPinStore $pins,
        private readonly KidSession $session,
        private readonly AccessSettings $access,
    ) {}

    /** GET …/kids/{kid}/sheet?screen=home: the screen, or the PIN screen when the kid isn't signed in. */
    public function screen(Request $request, string $kid): Response
    {
        $profile = $this->kidOrFail($kid);
        if (! $this->signedIn($request, $profile['id'])) {
            return $this->render('pin', $profile, ['hasPin' => $this->pins->has($profile['id'])]);
        }

        $screen = in_array($request->query('screen'), self::SCREENS, true) ? (string) $request->query('screen') : 'home';

        return $this->render($screen, $profile, $this->screenData($screen, $profile));
    }

    /** POST …/kids/{kid}/unlock (pin): the kid's PIN, or the parent PIN, opens the kid's home. */
    public function unlock(Request $request, string $kid, AttemptKidUnlock $attemptKidUnlock): Response|JsonResponse
    {
        $profile = $this->kidOrFail($kid);
        $pin = $request->input('pin');
        $result = $attemptKidUnlock->handle($profile['id'], AccessGate::clientIp($request), is_string($pin) && strlen($pin) <= 12 ? $pin : '');

        if (! $result['ok']) {
            return response()->json(
                ['error' => $result['lockedFor'] ? "Too many tries. Wait {$result['lockedFor']} seconds." : 'That’s not the right PIN.', 'wait' => $result['lockedFor']],
                $result['lockedFor'] ? 429 : 403,
            );
        }

        $this->session->start($request, $profile['id']);

        return $this->render('home', $profile, $this->screenData('home', $profile));
    }

    /** POST …/kids/{kid}/lock: ✕, Home or 5 minutes idle sign the kid out. */
    public function lock(Request $request, string $kid): Response
    {
        $this->kidOrFail($kid);
        $this->session->end($request);

        return response()->noContent();
    }

    /** POST …/kids/{kid}/print (counts[template]=n): Print all. Worksheets are generated only now. */
    public function print(Request $request, string $kid, PrintHomework $printHomework): JsonResponse
    {
        $profile = $this->kidOrFail($kid);
        if (! $this->session->active($request, $profile['id'])) {
            return response()->json(['locked' => true], 401);
        }

        $counts = array_map('intval', array_filter((array) $request->input('counts', []), 'is_numeric'));

        try {
            $sheets = $printHomework->handle($profile, $this->today(), $counts);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['ok' => true, 'sheets' => $sheets]);
    }

    /** POST …/kids/{kid}/check/{date}/{task} (decision=approve|missed, pin): a parent checks a printed sheet. */
    public function check(Request $request, string $kid, string $date, string $task, ReviewTask $reviewTask, MarkTaskMissed $markTaskMissed): Response|JsonResponse
    {
        $profile = $this->kidOrFail($kid);
        if ($denied = $this->guard($request, $profile['id'])) {
            return $denied;
        }

        if ($request->input('decision') === 'missed') {
            $markTaskMissed->handle($profile['id'], $date, $task);
        } else {
            $reviewTask->handle($profile['id'], $date, $task, approve: true);
        }

        return $this->render('history', $profile, $this->screenData('history', $profile));
    }

    /** GET …/kids/{kid}/photo: the kid's face for the PIN screen and avatars. */
    public function photo(string $kid): BinaryFileResponse
    {
        $profile = $this->kidOrFail($kid);
        $path = HomeworkModule::photoPath($profile['id']);
        abort_if($path === null, 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** GET …/kids/{kid}/preview/{date}/{task}: the printed worksheet, for the signed-in kid or an unlocked parent. */
    public function preview(Request $request, string $kid, string $date, string $task): Response
    {
        $profile = $this->kidOrFail($kid);
        $parent = (int) $request->session()->get(RequireSettingsPin::SESSION_KEY, 0) >= now()->timestamp;
        abort_unless($parent || $this->session->active($request, $profile['id']), 403);

        $found = collect($this->store->tasks($profile['id'], $date) ?? [])->firstWhere('id', $task);
        abort_unless(isset($found['worksheet']), 404);

        return response()->view('modules.homework.generated', [
            'kid' => $profile,
            'date' => $date,
            'tasks' => [$found],
            'answers' => false,
            'preview' => true,
        ])->header('Cache-Control', 'no-store');
    }

    /** POST …/kids/{kid}/rewards/{reward} (pin): a parent confirms buying a reward. */
    public function redeem(Request $request, string $kid, string $reward, RedeemReward $redeemReward): Response|JsonResponse
    {
        $profile = $this->kidOrFail($kid);
        if ($denied = $this->guard($request, $profile['id'])) {
            return $denied;
        }

        try {
            $this->store->synchronized(fn () => $redeemReward->handle($profile['id'], $reward, $this->today()));
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        return $this->render('rewards', $profile, $this->screenData('rewards', $profile) + ['bought' => collect(HomeworkModule::REWARDS)->firstWhere('id', $reward)['name'] ?? '']);
    }

    /**
     * The kid must be signed in, and a parent must type the parent PIN (no
     * remembered unlock, so a kid can't approve their own work). Null when allowed.
     */
    private function guard(Request $request, string $kid): ?JsonResponse
    {
        if (! $this->session->active($request, $kid)) {
            return response()->json(['locked' => true], 401);
        }
        if (! $this->access->pinEnabled()) {
            return null;
        }

        $pin = $request->input('pin');
        if (! is_string($pin) || $pin === '') {
            return response()->json(['pin' => true], 403);
        }

        $result = app(AttemptSettingsUnlock::class)->handle(AccessGate::clientIp($request), strlen($pin) <= 12 ? $pin : '');
        if (! $result['ok']) {
            return response()->json(['pin' => true, 'error' => $result['lockedFor'] ? "Too many tries. Wait {$result['lockedFor']} seconds." : 'Wrong parent PIN.'], 403);
        }

        return null;
    }

    private function signedIn(Request $request, string $kid): bool
    {
        // Nothing to protect with: no kid PIN and no parent PIN.
        if (! $this->pins->has($kid) && ! $this->access->pinEnabled()) {
            $this->session->start($request, $kid);

            return true;
        }

        return $this->session->active($request, $kid);
    }

    /**
     * @param  array{id: string, name: string, badge: string, color: string, grade: int}  $kid
     * @return array<string, mixed>
     */
    private function screenData(string $screen, array $kid): array
    {
        $today = $this->today();
        $tasks = $this->store->tasks($kid['id'], $today->toDateString()) ?? [];
        $data = ['balance' => $this->store->balance($kid['id'])];

        return $data + match ($screen) {
            'home' => [
                'schoolDay' => ! $today->isWeekend(),
                'todayTotal' => collect($tasks)->where('status', '!=', 'missed')->count(),
                'todayChecked' => collect($tasks)->where('status', 'approved')->count(),
                'toCheck' => count($this->toCheck($kid['id'])),
            ],
            'homework' => $this->homeworkData($kid, $today, $tasks),
            'rewards' => $this->rewardsData($kid['id'], $tasks, $today, $data['balance']) + [
                'homeworkFirst' => ! $today->isWeekend() && RedeemReward::homeworkOpen($tasks),
                'waiting' => (int) collect($this->toCheck($kid['id']))->sum('points'),
            ],
            'history' => $this->historyData($kid) + ['stats' => $this->store->stats($kid['id'])],
            default => [],
        };
    }

    /**
     * @param  array{id: string, grade: int}  $kid
     * @param  list<array<string, mixed>>  $tasks
     * @return array<string, mixed>
     */
    private function homeworkData(array $kid, CarbonImmutable $today, array $tasks): array
    {
        $picked = collect($tasks)->where('status', '!=', 'missed')->countBy('template')->all();
        $templates = HomeworkModule::templatesFor($kid['grade']);

        return [
            'schoolDay' => ! $today->isWeekend(),
            'readingMinutes' => $kid['grade'] <= 3 ? 15 : 20,
            'choices' => array_values(array_map(fn (array $t, string $key): array => $t + [
                'key' => $key,
                'already' => $picked[$key] ?? 0,
                'left' => max(0, $t['max'] - ($picked[$key] ?? 0)),
            ], $templates, array_keys($templates))),
        ];
    }

    /**
     * Tasks a parent still has to check, newest day first: printed ones, and any
     * picked earlier that were never checked.
     *
     * @return list<array<string, mixed>>
     */
    private function toCheck(string $kid): array
    {
        $cutoff = $this->today()->subDays(self::HISTORY_DAYS)->toDateString();
        $days = $this->store->load()['days'];
        krsort($days);
        $list = [];

        foreach ($days as $date => $kids) {
            if ($date < $cutoff) {
                continue;
            }
            foreach ($kids[$kid] ?? [] as $task) {
                if (in_array($task['status'] ?? '', ['todo', 'done'], true)) {
                    $list[] = $task + ['date' => (string) $date, 'name' => HomeworkModule::TEMPLATES[$task['template']]['name'] ?? $task['template']];
                }
            }
        }

        return $list;
    }

    /**
     * History as one list, newest day first: each day's sheets (to check, done, not done)
     * and the rewards bought that day, with the day's points total.
     *
     * @param  array{id: string}  $kid
     * @return array{days: list<array{date: string, points: int, rows: list<array<string, mixed>>}>, counts: array<string, int>, filter: string}
     */
    private function historyData(array $kid): array
    {
        $this->store->numberSheets();
        $data = $this->store->load();
        $cutoff = $this->today()->subDays(self::HISTORY_DAYS)->toDateString();
        $days = [];

        foreach ($data['days'] as $date => $kids) {
            if ($date < $cutoff) {
                continue;
            }
            foreach ($kids[$kid['id']] ?? [] as $task) {
                $status = match ($task['status']) {
                    'approved' => 'done',
                    'missed' => 'missed',
                    default => 'check',
                };
                $days[$date][] = [
                    'kind' => 'sheet',
                    'id' => $task['id'],
                    'template' => $task['template'],
                    'name' => HomeworkModule::TEMPLATES[$task['template']]['name'] ?? $task['template'],
                    'points' => (int) $task['points'],
                    'status' => $status,
                    'number' => isset($task['number']) ? HomeworkStore::sheetLabel((int) $task['number']) : null,
                    'printed' => $task['printedAt'] ?? null,
                    'time' => $task['approvedAt'] ?? $task['checkedAt'] ?? $task['printedAt'] ?? $task['doneAt'] ?? null,
                    'preview' => isset($task['worksheet']),
                ];
            }
        }

        $totals = [];
        foreach ($data['ledger'] as $entry) {
            $date = substr((string) $entry['at'], 0, 10);
            if ($entry['kid'] !== $kid['id'] || $date < $cutoff) {
                continue;
            }
            $totals[$date] = ($totals[$date] ?? 0) + (int) $entry['points'];
            if (! empty($entry['rewardId'])) {
                $days[$date][] = ['kind' => 'reward', 'name' => $entry['reason'], 'points' => (int) $entry['points'], 'status' => 'reward', 'time' => $entry['at']];
            }
        }

        krsort($days);
        $list = [];
        foreach ($days as $date => $rows) {
            usort($rows, fn (array $a, array $b): int => strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? '')) ?: strnatcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? '')));
            $list[] = ['date' => (string) $date, 'points' => $totals[$date] ?? 0, 'rows' => $rows];
        }

        $counts = collect($list)->flatMap(fn (array $day): array => $day['rows'])->countBy('status')->all();

        return [
            'days' => $list,
            'counts' => ['all' => array_sum($counts), 'check' => $counts['check'] ?? 0, 'done' => $counts['done'] ?? 0, 'missed' => $counts['missed'] ?? 0],
            'filter' => ($counts['check'] ?? 0) > 0 ? 'check' : 'all',
        ];
    }

    /**
     * Every reward: the ones the kid can get now first (enabled), then the rest
     * (disabled, with why), fewest points missing first.
     *
     * @param  list<array<string, mixed>>  $tasks
     * @return array{rewards: list<array<string, mixed>>}
     */
    private function rewardsData(string $kid, array $tasks, CarbonImmutable $today, int $balance): array
    {
        $rewards = collect($this->rewards($kid, $tasks, $today, $balance))
            ->sortBy(fn (array $r): array => [$r['state'] === 'ready' ? 0 : 1, max(0, $r['need'] - $balance), $r['need']])
            ->values();

        return ['rewards' => $rewards->all()];
    }

    /**
     * The store with each reward's state: "ready" (a parent can confirm it now),
     * "reach" (affordable, waiting for today's homework check), or "locked" with why.
     *
     * @param  list<array<string, mixed>>  $tasks
     * @return list<array<string, mixed>>
     */
    private function rewards(string $kid, array $tasks, CarbonImmutable $today, int $balance): array
    {
        $bought = app(RedeemReward::class)->boughtToday($kid, $today);
        $costs = array_column(HomeworkModule::REWARDS, 'cost', 'id');

        return array_map(function (array $r) use ($balance, $tasks, $bought, $today, $costs): array {
            $need = $r['cost'] + (isset($r['after']) && ($bought[$r['after']] ?? 0) === 0 ? $costs[$r['after']] : 0);
            $state = RedeemReward::state($r, $balance, $tasks, $bought, $today);

            $short = max(0, $need - $balance);
            // Affordable now, only waiting for today's homework check.
            $reach = $state === 'homework' && $balance >= $need;

            return $r + [
                'need' => $need,
                'state' => $state === 'ready' ? 'ready' : ($reach ? 'reach' : 'locked'),
                // One short line a kid can act on: how many points are missing, or the rule that blocks it.
                'why' => match (true) {
                    $state === 'ready', $reach => '',
                    $state === 'limit' => 'Already taken tonight',
                    $state === 'after' => 'After the first 2 periods',
                    $state === 'weekend' => 'Weekends only',
                    $short > 0 => $short.' more points',
                    default => 'Once today’s homework is checked',
                },
                'progress' => min(100, (int) round($balance * 100 / max(1, $need))),
                'blocker' => $state,
            ];
        }, HomeworkModule::REWARDS);
    }

    /**
     * @param  array{id: string, name: string, badge: string, color: string, grade: int}  $kid
     * @param  array<string, mixed>  $data
     */
    private function render(string $screen, array $kid, array $data): Response
    {
        return response()->view('modules.homework.kid.'.$screen, $data + [
            'kid' => $kid,
            'screen' => $screen,
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array{id: string, name: string, badge: string, color: string, grade: int} */
    private function kidOrFail(string $id): array
    {
        $module = $this->modules->get('homework');
        $kid = $module instanceof HomeworkModule ? $module->kid($id) : null;
        abort_if($kid === null, 404);

        return $kid;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay();
    }
}
