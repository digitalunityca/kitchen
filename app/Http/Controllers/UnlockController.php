<?php

namespace App\Http\Controllers;

use App\Http\Access\AccessGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/** The PIN page that lets visitors outside the home use the hub. */
class UnlockController extends Controller
{
    public const MAX_FAILURES = 10;

    public const LOCKOUT_SECONDS = 15 * 60;

    public function show(Request $request): Response
    {
        if ($locked = $this->lockedOut($request)) {
            return $locked;
        }

        return response()->view('access.unlock', [
            'to' => AccessGate::safeTarget($request->query('to')),
            'enabled' => AccessGate::pinEnabled(),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($locked = $this->lockedOut($request)) {
            return $locked;
        }

        abort_unless(AccessGate::pinEnabled(), 403);

        $to = AccessGate::safeTarget($request->input('to'));

        if (AccessGate::pinMatches((string) $request->input('pin', ''))) {
            RateLimiter::clear($this->failureKey($request));

            return redirect()->to($to)->withCookie(AccessGate::makeCookie());
        }

        RateLimiter::hit($this->failureKey($request), self::LOCKOUT_SECONDS);

        if ($locked = $this->lockedOut($request)) {
            return $locked;
        }

        return response()->view('access.unlock', [
            'to' => $to,
            'enabled' => true,
            'error' => 'That PIN isn’t right. Try again.',
        ], 422);
    }

    private function failureKey(Request $request): string
    {
        return 'unlock-failures:'.AccessGate::clientIp($request);
    }

    private function lockedOut(Request $request): ?Response
    {
        $key = $this->failureKey($request);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            return null;
        }

        $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

        return response()->view('access.locked', ['minutes' => $minutes], 429)
            ->header('Retry-After', (string) RateLimiter::availableIn($key));
    }
}
