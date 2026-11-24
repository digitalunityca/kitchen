<?php

namespace App\Modules\Habs;

use App\Actions\Habs\BuildHabsTicker;
use App\Dashboard\Module;
use Illuminate\Routing\Router;

/**
 * Montreal Canadiens: a slim scrolling strip under the header on game days
 * (upcoming today or tomorrow, live score, or yesterday's final until 6 PM),
 * a hockey button in the header that opens a stats sheet any day, and a short
 * note in Fully's action bar on game days. Data from the NHL's public API.
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

    /** Rendered as a strip between the header and the grid. */
    public function placement(): string
    {
        return 'ticker';
    }

    /** @return array{items: list<array<string, mixed>>, title: string|null, demo: string|null} */
    public function data(): array
    {
        $demo = request()->query('habs');
        $demo = is_string($demo) && in_array($demo, DemoGames::STATES, true) ? $demo : null;

        return app(BuildHabsTicker::class)->handle(now()->toImmutable(), $demo) + ['demo' => $demo];
    }

    public function routes(Router $router): void
    {
        $router->get('ticker', [HabsController::class, 'ticker'])->middleware('throttle:30,1')->name('ticker');
        $router->get('sheet', [HabsController::class, 'sheet'])->middleware('throttle:30,1')->name('sheet');
    }
}
