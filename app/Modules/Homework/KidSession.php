<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use Illuminate\Http\Request;

/**
 * Which kid is signed in to their homework on this device. One kid at a time;
 * the sign-in ends after IDLE_SECONDS without a tap, on ✕, or on Home.
 */
class KidSession
{
    public const KEY = 'homework_kid';

    public const IDLE_SECONDS = 300;

    public function start(Request $request, string $kid): void
    {
        $request->session()->put(self::KEY, ['id' => $kid, 'until' => now()->addSeconds(self::IDLE_SECONDS)->timestamp]);
    }

    /** True when this kid is signed in; each check counts as activity and pushes the timeout back. */
    public function active(Request $request, string $kid): bool
    {
        $current = $request->session()->get(self::KEY);
        if (! is_array($current) || ($current['id'] ?? null) !== $kid || (int) ($current['until'] ?? 0) < now()->timestamp) {
            return false;
        }

        $this->start($request, $kid);

        return true;
    }

    public function end(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}
