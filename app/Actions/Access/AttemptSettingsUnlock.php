<?php

declare(strict_types=1);

namespace App\Actions\Access;

use Illuminate\Cache\RateLimiter;

/**
 * Check the PIN that opens the Settings sheet. Five wrong tries lock the
 * device out for a minute. Returns the seconds left when locked out.
 */
class AttemptSettingsUnlock
{
    public const MAX_TRIES = 5;

    public const LOCKOUT_SECONDS = 60;

    public function __construct(
        private readonly VerifyPin $verifyPin,
        private readonly RateLimiter $limiter,
    ) {}

    /** @return array{ok: bool, lockedFor: int|null} */
    public function handle(string $ip, string $pin): array
    {
        $key = 'settings-pin:'.$ip;

        if ($this->limiter->tooManyAttempts($key, self::MAX_TRIES)) {
            return ['ok' => false, 'lockedFor' => max(1, $this->limiter->availableIn($key))];
        }

        if ($this->verifyPin->handle($pin)) {
            $this->limiter->clear($key);

            return ['ok' => true, 'lockedFor' => null];
        }

        $this->limiter->hit($key, self::LOCKOUT_SECONDS);

        return ['ok' => false, 'lockedFor' => $this->limiter->tooManyAttempts($key, self::MAX_TRIES) ? $this->limiter->availableIn($key) : null];
    }
}
