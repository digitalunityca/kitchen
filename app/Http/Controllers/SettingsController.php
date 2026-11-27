<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Access\AttemptSettingsUnlock;
use App\Dashboard\ModuleRegistry;
use App\Http\Access\AccessGate;
use App\Http\Middleware\RequireSettingsPin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The Settings sheet: a PIN keypad, then a list of each module's settings. */
class SettingsController extends Controller
{
    public function index(ModuleRegistry $modules): View
    {
        $sections = collect([
            'calendar' => ['Calendars', 'Add calendars, icons, colours and reminders', 'modules.calendar.settings'],
            'homework' => ['Homework', 'Today’s worksheets, answer keys, points and word lists', 'modules.homework.settings'],
            'kids' => ['Kids', 'Each kid’s PIN for their homework', 'modules.homework.pins'],
            'chores' => ['Help at home', 'Chores, their points and which kid sees them', 'modules.homework.chores'],
        ])->filter(fn ($section, string $key): bool => $modules->get(in_array($key, ['kids', 'chores'], true) ? 'homework' : $key) !== null);

        return view('settings.index', ['sections' => $sections]);
    }

    public function showUnlock(Request $request): View
    {
        return view('settings.unlock', ['to' => AccessGate::safeTarget($request->query('to', route('settings', [], false)))]);
    }

    public function unlock(Request $request, AttemptSettingsUnlock $attemptSettingsUnlock): RedirectResponse|Response
    {
        $to = AccessGate::safeTarget($request->input('to', '/settings'));
        $result = $attemptSettingsUnlock->handle(AccessGate::clientIp($request), (string) $request->input('pin', ''));

        if ($result['ok']) {
            $request->session()->put(RequireSettingsPin::SESSION_KEY, now()->addMinutes(RequireSettingsPin::MINUTES)->timestamp);

            return redirect()->to($to);
        }

        return response()->view('settings.unlock', [
            'to' => $to,
            'error' => $result['lockedFor'] ? __('Too many tries. Wait :n seconds.', ['n' => $result['lockedFor']]) : __('That PIN isn’t right.'),
        ], $result['lockedFor'] ? 429 : 422);
    }

    public function lock(Request $request): RedirectResponse
    {
        $request->session()->forget(RequireSettingsPin::SESSION_KEY);

        return redirect()->route('settings.unlock');
    }
}
