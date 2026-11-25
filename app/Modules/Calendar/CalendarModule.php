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
 * /modules/calendar/settings. The card shows today, tomorrow and the next event
 * after that; its icon opens a month view with day lists, search, calendar toggles
 * and event details.
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

    /** How far ahead the card looks for the "Next" event after tomorrow. */
    public const NEXT_DAYS = 365;

    /** @return array{sample: bool, today: list<array<string, mixed>>, tomorrow: list<array<string, mixed>>, next: array<string, mixed>|null} */
    public function data(): array
    {
        $from = now(ParseIcs::TIMEZONE)->toImmutable()->startOfDay();
        $loaded = app(LoadCalendarEvents::class)->handle($from, $from->addDays(self::NEXT_DAYS));
        $days = LoadCalendarEvents::byDay($loaded['events'], $from, self::NEXT_DAYS);
        $later = array_slice($days, 2, null, true);
        $nextDay = array_key_first(array_filter($later));

        return [
            'sample' => $loaded['sample'],
            'today' => array_values($days)[0],
            'tomorrow' => array_values($days)[1],
            'next' => $nextDay === null ? null : $later[$nextDay][0],
        ];
    }

    public function routes(Router $router): void
    {
        $router->get('events', [CalendarController::class, 'events'])->middleware('throttle:30,1')->name('events');
        $router->get('settings', [CalendarController::class, 'settings'])->name('settings');
        $router->post('calendars', [CalendarController::class, 'store'])->middleware('throttle:10,1')->name('calendars.store');
        $router->post('calendars/{id}', [CalendarController::class, 'update'])->where('id', '[a-z0-9]{8}')->name('calendars.update');
        $router->post('calendars/{id}/delete', [CalendarController::class, 'destroy'])->where('id', '[a-z0-9]{8}')->name('calendars.destroy');
        $router->post('calendars/{id}/up', [CalendarController::class, 'moveUp'])->where('id', '[a-z0-9]{8}')->name('calendars.up');
    }
}
