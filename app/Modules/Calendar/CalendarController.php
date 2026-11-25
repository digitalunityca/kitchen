<?php

declare(strict_types=1);

namespace App\Modules\Calendar;

use App\Actions\Calendar\AddCalendar;
use App\Actions\Calendar\BuildCalendarData;
use App\Actions\Calendar\MoveCalendarUp;
use App\Actions\Calendar\RemoveCalendar;
use App\Actions\Calendar\UpdateCalendar;
use App\Http\Controllers\Controller;
use App\Modules\Calendar\Requests\AddCalendarRequest;
use App\Modules\Calendar\Requests\UpdateCalendarRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CalendarController extends Controller
{
    /** GET /modules/calendar/events: the calendar sheet's data (6 months back, 12 ahead). */
    public function events(BuildCalendarData $buildCalendarData): JsonResponse
    {
        return response()->json($buildCalendarData->handle(now()->toImmutable()))->header('Cache-Control', 'no-store');
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
