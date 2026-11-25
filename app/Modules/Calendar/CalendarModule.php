<?php

namespace App\Modules\Calendar;

use App\Actions\Calendar\LoadCalendarEvents;
use App\Actions\Calendar\ParseIcs;
use App\Dashboard\Module;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

/**
 * Calendars from iCal links (Google, iCloud, Outlook…), added on
 * /modules/calendar/settings. The card shows today and tomorrow; its icon
 * opens a sheet with the next 14 days, calendar filters and event details.
 * Sample events show until the first calendar is added.
 */
class CalendarModule extends Module
{
    public function key(): string
    {
        return 'calendar';
    }

    public function name(): string
    {
        return 'Calendar';
    }

    public function register(Container $app): void
    {
        $app->bind(CalendarStore::class, fn (): CalendarStore => new CalendarStore(Storage::disk('local')));
    }

    /** @return array{sample: bool, today: list<array<string, mixed>>, tomorrow: list<array<string, mixed>>} */
    public function data(): array
    {
        $from = now(ParseIcs::TIMEZONE)->toImmutable()->startOfDay();
        $loaded = app(LoadCalendarEvents::class)->handle($from, $from->addDays(2));
        $days = array_values(LoadCalendarEvents::byDay($loaded['events'], $from, 2));

        return ['sample' => $loaded['sample'], 'today' => $days[0], 'tomorrow' => $days[1]];
    }

    public function routes(Router $router): void
    {
        $router->get('sheet', [CalendarController::class, 'sheet'])->middleware('throttle:30,1')->name('sheet');
        $router->get('settings', [CalendarController::class, 'settings'])->name('settings');
        $router->post('calendars', [CalendarController::class, 'store'])->middleware('throttle:10,1')->name('calendars.store');
        $router->post('calendars/{id}', [CalendarController::class, 'update'])->where('id', '[a-z0-9]{8}')->name('calendars.update');
        $router->post('calendars/{id}/delete', [CalendarController::class, 'destroy'])->where('id', '[a-z0-9]{8}')->name('calendars.destroy');
        $router->post('calendars/{id}/up', [CalendarController::class, 'moveUp'])->where('id', '[a-z0-9]{8}')->name('calendars.up');
    }
}
