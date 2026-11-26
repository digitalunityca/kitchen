<?php

namespace App\Modules\Habs;

use App\Actions\Habs\BuildHabsTicker;
use App\Dashboard\Module;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Cache;

/**
 * Montreal Canadiens: on game days the weather card turns into a game card (logos,
 * time or live score, venue, final), with a Turn button to flip back to the weather;
 * a Habs button in the side menu opens a stats sheet any day; and a short note goes
 * in Fully's action bar. Data from the NHL's public API.
 *
 * Preview without a game: /?habs=upcoming|live|final, or `php artisan habs:preview live`
 * (on the tablet too, until midnight; `habs:preview off` ends it).
 */
class HabsModule extends Module
{
    public function key(): string
    {
        return 'habs';
    }

    public function name(): string
    {
        return 'Canadiens';
    }

    public const PREVIEW_KEY = 'habs:preview';

    /** No card of its own: it takes over the weather card on game days, from the side menu. */
    public function placement(): string
    {
        return 'overlay';
    }

    /** @return array{items: list<array<string, mixed>>, today: array<string, mixed>|null, title: string|null, demo: string|null} */
    public function data(): array
    {
        $demo = self::demo(request()->query('habs'));

        return app(BuildHabsTicker::class)->handle(now()->toImmutable(), $demo) + ['demo' => $demo];
    }

    /** A sample game state: from ?habs=…, else the preview turned on with `habs:preview` (until midnight). */
    public static function demo(mixed $query): ?string
    {
        $demo = is_string($query) ? $query : Cache::get(self::PREVIEW_KEY);

        return is_string($demo) && in_array($demo, DemoGames::STATES, true) ? $demo : null;
    }

    public function routes(Router $router): void
    {
        $router->get('ticker', [HabsController::class, 'ticker'])->middleware('throttle:30,1')->name('ticker');
        $router->get('sheet', [HabsController::class, 'sheet'])->middleware('throttle:30,1')->name('sheet');
    }
}
