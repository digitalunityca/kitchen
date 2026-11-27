<?php

namespace App\Modules\Homework;

use App\Dashboard\Module;
use App\Http\Middleware\RequireSettingsPin;
use App\Models\Kid;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

/**
 * Kids' homework. One round avatar button per kid in the side menu, under Home.
 * Each opens the kid's own area behind their PIN (KidController): their points,
 * today's progress and four tiles. Homework (Monday to Friday): the kid picks
 * today's tasks (each with a daily limit) and taps Print all; the worksheets are
 * generated then, printed by the Mac Studio, and wait for a parent's check (PIN or
 * the QR code on the sheet) before their points count. Rewards: evening rewards and
 * weekend prizes, bought with the parent PIN. History: points by day and the tasks
 * to check. News: teachers' messages (later). Worksheets are in French, sized to
 * the kid's grade (Quebec primary).
 * Kids are configured in config/dashboard.php: name, badge, colour, grade.
 */
class HomeworkModule extends Module
{
    public const TIMEZONE = 'America/Toronto';

    /**
     * Each kind of task the kids can pick: its name on the tablet and on the sheet,
     * its points, whether it has a printable worksheet, and which grades get it
     * (writing is for the younger kids). No daily limit: a kid prints as many as they like.
     *
     * @var array<string, array{name: string, sheet: string, emoji: string, points: int, printable: bool, grades: array{int, int}}>
     */
    public const TEMPLATES = [
        'addition' => ['name' => 'Addition', 'sheet' => 'Additions', 'emoji' => '➕', 'points' => 10, 'printable' => true, 'grades' => [1, 6]],
        'multiplication' => ['name' => 'Multiplication', 'sheet' => 'Multiplications', 'emoji' => '✖️', 'points' => 15, 'printable' => true, 'grades' => [1, 6]],
        'division' => ['name' => 'Division', 'sheet' => 'Divisions', 'emoji' => '➗', 'points' => 15, 'printable' => true, 'grades' => [1, 6]],
        'writing' => ['name' => 'Writing', 'sheet' => 'Écriture', 'emoji' => '✏️', 'points' => 10, 'printable' => true, 'grades' => [1, 3]],
        'reading' => ['name' => 'Reading', 'sheet' => 'Lecture', 'emoji' => '📖', 'points' => 10, 'printable' => false, 'grades' => [1, 6]],
        'grammar' => ['name' => 'Grammar', 'sheet' => 'Grammaire et conjugaison', 'emoji' => '🔤', 'points' => 10, 'printable' => true, 'grades' => [2, 6]],
    ];

    /**
     * The rewards store. "evening" rewards can be bought on a school day once all of
     * that day's homework is approved (any time on weekends); "weekend" prizes only on
     * Saturday and Sunday, so the kids save up for them. "limit" caps how many per day;
     * "after" means that reward must be bought first the same day (the Habs 3rd period).
     *
     * @var list<array{id: string, name: string, cost: int, when: string, note: string, limit?: int, after?: string}>
     */
    public const REWARDS = [
        ['id' => 'dessert', 'name' => 'Choose dessert', 'cost' => 25, 'when' => 'evening', 'note' => '1 per evening', 'limit' => 1],
        ['id' => 'stay-up', 'name' => 'Stay up 15 min later', 'cost' => 30, 'when' => 'evening', 'note' => '1 per evening', 'limit' => 1],
        ['id' => 'board-game', 'name' => 'Board game with a parent', 'cost' => 30, 'when' => 'evening', 'note' => ''],
        ['id' => 'movie', 'name' => 'Movie in the evening', 'cost' => 40, 'when' => 'evening', 'note' => '1 per evening', 'limit' => 1],
        ['id' => 'habs', 'name' => 'Habs game: 2 periods', 'cost' => 50, 'when' => 'evening', 'note' => 'game days', 'limit' => 1],
        ['id' => 'habs-3rd', 'name' => 'Habs game: 3rd period', 'cost' => 30, 'when' => 'evening', 'note' => 'after the 2 periods', 'limit' => 1, 'after' => 'habs'],
        ['id' => 'ps5', 'name' => '30 min PS5', 'cost' => 60, 'when' => 'evening', 'note' => '1 per evening', 'limit' => 1],
        ['id' => 'weekend-treat', 'name' => 'Weekend treat (small toy, snack)', 'cost' => 150, 'when' => 'weekend', 'note' => 'Saturday or Sunday'],
        ['id' => 'weekend-outing', 'name' => 'Weekend outing (cinema, trampoline park)', 'cost' => 300, 'when' => 'weekend', 'note' => 'Saturday or Sunday'],
    ];

    /** Copying words for the writing sheets until a parent sets the list of the week. */
    public const DEFAULT_WORDS = [
        'maison', 'école', 'soleil', 'jardin', 'ami', 'chat', 'chien', 'lune', 'pomme', 'livre',
        'arbre', 'fleur', 'neige', 'hiver', 'maman', 'papa', 'frère', 'ballon', 'table', 'classe',
    ];

    public function key(): string
    {
        return 'homework';
    }

    public function name(): string
    {
        return 'Homework';
    }

    /** No card: the buttons go into the side menu and the sheet floats over the dashboard. */
    public function placement(): string
    {
        return 'overlay';
    }

    public function register(Container $app): void
    {
        $app->singleton(HomeworkStore::class);
        $app->singleton(KidPinStore::class);
    }

    /** @return array{kids: list<array<string, mixed>>, cursor: int, voice: bool} */
    public function data(): array
    {
        $store = app(HomeworkStore::class);

        return [
            'kids' => array_map(fn (array $kid): array => $kid + ['unseen' => $store->unseenPoints($kid['id'])], $this->kids()),
            'cursor' => count($store->load()['ledger']),
            'voice' => (bool) $this->config('celebrate_voice', true),
        ];
    }

    /** @return list<array{id: string, name: string, badge: string, color: string, grade: int, photo: string|null}> */
    public function kids(): array
    {
        return Kid::query()->orderBy('position')->orderBy('id')->get()->map(fn (Kid $kid): array => [
            'id' => $kid->slug,
            'name' => $kid->name,
            'badge' => $kid->badge,
            'color' => $kid->color,
            'grade' => $kid->grade,
            'workGrade' => $kid->workGrade(),
            'theme' => in_array($kid->theme, Kid::THEMES, true) ? $kid->theme : null,
            'photo' => self::photoPath($kid->slug) !== null
                ? route('modules.homework.photo', ['kid' => $kid->slug, 'v' => Storage::disk('local')->lastModified(self::photoPath($kid->slug))], absolute: false)
                : null,
        ])->all();
    }

    /**
     * The kid's face for the avatars, if a parent added one: storage/app/private/kid-photos/<id>.jpg
     * (on the data volume, never in git). Null when there is none.
     */
    public static function photoPath(string $kid): ?string
    {
        $path = 'kid-photos/'.$kid.'.jpg';

        return Storage::disk('local')->exists($path) ? $path : null;
    }

    /**
     * The templates this kid can pick (by grade).
     *
     * @return array<string, array{name: string, sheet: string, emoji: string, points: int, printable: bool, grades: array{int, int}}>
     */
    public static function templatesFor(int $grade): array
    {
        return array_filter(self::TEMPLATES, fn (array $t): bool => $grade >= $t['grades'][0] && $grade <= $t['grades'][1]);
    }

    /**
     * A darker shade of a kid's colour that white text can sit on (WCAG 4.5:1),
     * for buttons and the selected cards; the bright colour stays for the avatar.
     */
    public static function ink(string $hex): string
    {
        $rgb = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        if (count($rgb) !== 3) {
            return '#0071E3';
        }

        $luminance = function (array $c): float {
            $l = array_map(fn (float $v): float => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, array_map(fn (int $v): float => $v / 255, $c));

            return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
        };

        for ($i = 0; $i < 20 && 1.05 / ($luminance($rgb) + 0.05) < 4.5; $i++) {
            $rgb = array_map(fn (int $v): int => (int) floor($v * 0.92), $rgb);
        }

        return sprintf('#%02X%02X%02X', ...$rgb);
    }

    /** @return array{id: string, name: string, badge: string, color: string, grade: int}|null */
    public function kid(string $id): ?array
    {
        return collect($this->kids())->firstWhere('id', $id);
    }

    public function routes(Router $router): void
    {
        $kid = '[a-z0-9-]+';
        $task = 't[0-9]+';

        $router->get('events', [KidController::class, 'events'])->middleware('throttle:60,1')->name('events');
        $router->get('kids/{kid}/sheet', [KidController::class, 'screen'])->where('kid', $kid)->middleware('throttle:120,1')->name('sheet');
        $router->post('kids/{kid}/unlock', [KidController::class, 'unlock'])->where('kid', $kid)->middleware('throttle:30,1')->name('unlock');
        $router->post('kids/{kid}/lock', [KidController::class, 'lock'])->where('kid', $kid)->middleware('throttle:60,1')->name('lock');
        $router->post('kids/{kid}/print', [KidController::class, 'print'])->where('kid', $kid)->middleware('throttle:6,1,homework-print-all:')->name('print');
        $router->post('kids/{kid}/check/{date}/{task}', [KidController::class, 'check'])
            ->where(['kid' => $kid, 'date' => '\d{4}-\d{2}-\d{2}', 'task' => $task])->middleware('throttle:30,1')->name('check');
        $router->get('kids/{kid}/photo', [KidController::class, 'photo'])->where('kid', $kid)->middleware('throttle:120,1')->name('photo');
        $router->get('kids/{kid}/preview/{date}/{task}', [KidController::class, 'preview'])
            ->where(['kid' => $kid, 'date' => '\d{4}-\d{2}-\d{2}', 'task' => $task])->middleware('throttle:60,1')->name('preview');
        $router->post('kids/{kid}/chores/{chore}/done', [KidController::class, 'choreDone'])->where(['kid' => $kid, 'chore' => '[0-9]+'])->middleware('throttle:60,1')->name('chore.done');
        $router->post('kids/{kid}/chores/check/{task}', [KidController::class, 'choreCheck'])->where(['kid' => $kid, 'task' => '[0-9]+'])->middleware('throttle:30,1')->name('chore.check');
        $router->post('kids/{kid}/rewards/{reward}', [KidController::class, 'redeem'])->where(['kid' => $kid, 'reward' => '[a-z0-9-]+'])->middleware('throttle:30,1')->name('redeem');
        $router->get('kids/{kid}/{date}/{task}/worksheet', [HomeworkController::class, 'worksheet'])
            ->where(['kid' => $kid, 'date' => '\d{4}-\d{2}-\d{2}', 'task' => $task])->middleware('throttle:30,1')->name('worksheet');

        $packet = ['kid' => $kid, 'date' => '\d{4}-\d{2}-\d{2}'];
        $router->get('kids/{kid}/{date}/packet', [WorksheetPacketController::class, 'show'])
            ->where($packet)->middleware('throttle:30,1')->name('packet');
        $router->post('kids/{kid}/{date}/packet', [WorksheetPacketController::class, 'generate'])
            ->where($packet)->middleware('throttle:8,1,homework-generate:')->name('packet.generate');
        $router->post('kids/{kid}/{date}/packet/print/{job}', [WorksheetPacketController::class, 'print'])
            ->where($packet)->whereUuid('job')->middleware('throttle:6,1,homework-print:')->name('packet.print');
        $router->get('kids/{kid}/{date}/packet/print/{job}', [WorksheetPacketController::class, 'printStatus'])
            ->where($packet)->whereUuid('job')->middleware('throttle:60,1,homework-print-status:')->name('packet.print-status');
        $router->match(['GET', 'POST'], 'confirm/{kid}/{date}/{task}', [WorksheetPacketController::class, 'confirm'])
            ->where($packet + ['task' => $task])->middleware(['signed:relative', 'throttle:30,1'])->name('packet.confirm');

        // Answer keys and the word lists need the settings PIN.
        $router->middleware(RequireSettingsPin::class)->group(function (Router $router) use ($kid, $task): void {
            $router->get('settings', [HomeworkController::class, 'settings'])->name('settings');
            $router->get('kid-pins', [HomeworkController::class, 'pins'])->name('pins');
            $router->get('chores', [ChoreController::class, 'index'])->name('chores');
            $router->get('progress', [ProgressController::class, 'index'])->name('progress');
            $router->post('progress/{kid}/grade', [ProgressController::class, 'grade'])->where('kid', $kid)->name('progress.grade');
            $router->post('progress/{kid}/skills/{skill}', [ProgressController::class, 'adjust'])->where('kid', $kid)->whereNumber('skill')->name('progress.adjust');
            $router->post('chores', [ChoreController::class, 'store'])->name('chores.store');
            $router->post('chores/{chore}', [ChoreController::class, 'update'])->whereNumber('chore')->name('chores.update');
            $router->post('kids/{kid}/pin', [HomeworkController::class, 'savePin'])->where('kid', $kid)->name('pin');
            $router->get('kids/{kid}/{date}/{task}/answers', [HomeworkController::class, 'answers'])
                ->where(['kid' => $kid, 'date' => '\d{4}-\d{2}-\d{2}', 'task' => $task])->name('answers');
        });
    }
}
