<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Access\AttemptUnlock;
use App\Actions\Access\CheckUnlockLockout;
use App\Actions\Access\IssueUnlockCookie;
use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use App\Http\Access\UnlockOutcome;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The PIN page that lets visitors outside the home use the hub. */
class UnlockController extends Controller
{
    public function __construct(
        private readonly AccessSettings $settings,
        private readonly CheckUnlockLockout $checkUnlockLockout,
    ) {}

    public function show(Request $request): Response
    {
        if ($locked = $this->lockedOut($request)) {
            return $locked;
        }

        return response()->view('access.unlock', [
            'to' => AccessGate::safeTarget($request->query('to')),
            'enabled' => $this->settings->pinEnabled(),
        ]);
    }

    public function store(Request $request, AttemptUnlock $attemptUnlock, IssueUnlockCookie $issueUnlockCookie): Response
    {
        if ($locked = $this->lockedOut($request)) {
            return $locked;
        }

        abort_unless($this->settings->pinEnabled(), 403);

        $to = AccessGate::safeTarget($request->input('to'));

        return match ($attemptUnlock->handle(AccessGate::clientIp($request), (string) $request->input('pin', ''))) {
            UnlockOutcome::Unlocked => redirect()->to($to)->withCookie($issueUnlockCookie->handle()),
            UnlockOutcome::LockedOut => $this->lockedOut($request),
            UnlockOutcome::WrongPin => response()->view('access.unlock', [
                'to' => $to,
                'enabled' => true,
                'error' => 'That PIN isn’t right. Try again.',
            ], 422),
        };
    }

    private function lockedOut(Request $request): ?Response
    {
        $seconds = $this->checkUnlockLockout->handle(AccessGate::clientIp($request));

        if ($seconds === null) {
            return null;
        }

        return response()->view('access.locked', ['minutes' => max(1, (int) ceil($seconds / 60))], 429)
            ->header('Retry-After', (string) $seconds);
    }
}
