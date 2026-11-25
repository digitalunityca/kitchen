<?php

declare(strict_types=1);

namespace App\Modules\Calendar;

use App\Actions\Calendar\AddCalendar;
use App\Actions\Calendar\LoadCalendarEvents;
use App\Actions\Calendar\MoveCalendarUp;
use App\Actions\Calendar\ParseIcs;
use App\Actions\Calendar\RemoveCalendar;
use App\Actions\Calendar\UpdateCalendar;
use App\Http\Controllers\Controller;
use App\Modules\Calendar\Requests\AddCalendarRequest;
use App\Modules\Calendar\Requests\UpdateCalendarRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    /** Days shown in the calendar sheet. */
    public const SHEET_DAYS = 60;

    /**
     * GET /modules/calendar/sheet: the next 60 days, filterable by calendar, with each event's
     * details. The card's "Next" event may be further out, so its details come along too.
     */
    public function sheet(LoadCalendarEvents $loadCalendarEvents): Response
    {
        $from = now(ParseIcs::TIMEZONE)->toImmutable()->startOfDay();
        $loaded = $loadCalendarEvents->handle($from, $from->addDays(CalendarModule::NEXT_DAYS), visibleOnly: true);
        $days = LoadCalendarEvents::byDay($loaded['events'], $from, CalendarModule::NEXT_DAYS);

        return response()->view('modules.calendar.sheet', $loaded + [
            'days' => array_slice($days, 0, self::SHEET_DAYS, true),
            'extra' => array_slice($days, self::SHEET_DAYS, null, true),
            'today' => $from,
        ])->header('Cache-Control', 'no-store');
    }

    public function settings(CalendarStore $store): View
    {
        return view('modules.calendar.settings', ['calendars' => $store->all(), 'colors' => CalendarStore::COLORS]);
    }

    public function store(AddCalendarRequest $request, AddCalendar $addCalendar): RedirectResponse
    {
        $addCalendar->handle($request->validated('name'), $request->validated('color'), $request->validated('url'));

        return redirect()->route('modules.calendar.settings')->with('status', 'Calendar added.');
    }

    public function update(string $id, UpdateCalendarRequest $request, UpdateCalendar $updateCalendar): RedirectResponse
    {
        $changes = $request->validated();

        if (array_key_exists('visible', $changes)) {
            $changes['visible'] = (bool) $changes['visible'];
        }

        $updateCalendar->handle($id, $changes);

        return redirect()->route('modules.calendar.settings')->with('status', 'Saved.');
    }

    public function destroy(string $id, RemoveCalendar $removeCalendar): RedirectResponse
    {
        $removeCalendar->handle($id);

        return redirect()->route('modules.calendar.settings')->with('status', 'Calendar removed.');
    }

    public function moveUp(string $id, MoveCalendarUp $moveCalendarUp): RedirectResponse
    {
        $moveCalendarUp->handle($id);

        return redirect()->route('modules.calendar.settings');
    }
}
