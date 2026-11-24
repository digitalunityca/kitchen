<?php

declare(strict_types=1);

namespace App\Actions\Access;

use Illuminate\Cache\RateLimiter;

/** After MAX_FAILURES wrong PINs, an IP is locked out of /unlock for LOCKOUT_SECONDS. */
class CheckUnlockLockout
{
    public const MAX_FAILURES = 10;

    public const LOCKOUT_SECONDS = 15 * 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    /** Seconds until this IP may try again, or null when it isn't locked out. */
    public function handle(string $ip): ?int
    {
        $key = self::key($ip);

        if (! $this->limiter->tooManyAttempts($key, self::MAX_FAILURES)) {
            return null;
        }

        return max(1, $this->limiter->availableIn($key));
    }

    public static function key(string $ip): string
    {
        return 'unlock-failures:'.$ip;
    }
}
