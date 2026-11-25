<?php

namespace App\Modules\Calendar;

use App\Actions\Calendar\BuildCalendarData;
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

    /**
     * The card's data, filtered and drawn in the browser so the calendars
     * dropdown works instantly: this week and next (Sunday start) with every
     * event, plus each calendar's first event after tomorrow for "Next".
     *
     * @return array{sample: bool, calendars: list<array<string, mixed>>, today: string, weekStart: string, events: list<array<string, mixed>>, next: list<array<string, mixed>>}
     */
    public function data(): array
    {
        $today = now(ParseIcs::TIMEZONE)->toImmutable()->startOfDay();
        $weekStart = $today->subDays($today->dayOfWeek);
        $windowEnd = $weekStart->addDays(14);
        $loaded = app(LoadCalendarEvents::class)->handle($weekStart, $today->addDays(self::NEXT_DAYS));
        $afterTomorrow = $today->addDays(2);

        $window = array_values(array_filter($loaded['events'], fn (array $e): bool => $e['start'] < $windowEnd && $e['end'] > $weekStart));
        $next = collect($loaded['events'])
            ->filter(fn (array $e): bool => $e['start'] >= $afterTomorrow)
            ->groupBy('calendarId')
            ->map(fn ($events) => $events->first())
            ->values()->all();

        return [
            'sample' => $loaded['sample'],
            'calendars' => $loaded['calendars'],
            'today' => $today->toDateString(),
            'weekStart' => $weekStart->toDateString(),
            'events' => array_map(fn (array $e): array => BuildCalendarData::compact($e, $weekStart, $windowEnd), $window),
            'next' => array_map(fn (array $e): array => BuildCalendarData::compact($e, $afterTomorrow, $today->addDays(self::NEXT_DAYS)), $next),
        ];
    }

    public function routes(Router $router): void
    {
        $router->get('events', [CalendarController::class, 'events'])->middleware('throttle:30,1')->name('events');
        $router->get('card', [CalendarController::class, 'card'])->middleware('throttle:30,1')->name('card');
        $router->get('settings', [CalendarController::class, 'settings'])->name('settings');
        $router->post('calendars', [CalendarController::class, 'store'])->middleware('throttle:10,1')->name('calendars.store');
        $router->post('calendars/{id}', [CalendarController::class, 'update'])->where('id', '[a-z0-9]{8}')->name('calendars.update');
        $router->post('calendars/{id}/delete', [CalendarController::class, 'destroy'])->where('id', '[a-z0-9]{8}')->name('calendars.destroy');
        $router->post('calendars/{id}/up', [CalendarController::class, 'moveUp'])->where('id', '[a-z0-9]{8}')->name('calendars.up');
    }
}
