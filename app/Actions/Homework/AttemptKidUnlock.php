<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Actions\Access\VerifyPin;
use App\Modules\Homework\KidPinStore;
use Illuminate\Cache\RateLimiter;

/**
 * Check the PIN on a kid's sign-in screen: the kid's own PIN, or the parent PIN
 * (which opens any kid). Five wrong tries lock that kid out on this device for a minute.
 */
class AttemptKidUnlock
{
    public const MAX_TRIES = 5;

    public const LOCKOUT_SECONDS = 60;

    public function __construct(
        private readonly KidPinStore $pins,
        private readonly VerifyPin $verifyPin,
        private readonly RateLimiter $limiter,
    ) {}

    /** @return array{ok: bool, lockedFor: int|null} */
    public function handle(string $kid, string $ip, string $pin): array
    {
        $key = 'kid-pin:'.$kid.':'.$ip;

        if ($this->limiter->tooManyAttempts($key, self::MAX_TRIES)) {
            return ['ok' => false, 'lockedFor' => max(1, $this->limiter->availableIn($key))];
        }

        if ($this->pins->matches($kid, $pin) || $this->verifyPin->handle($pin)) {
            $this->limiter->clear($key);

            return ['ok' => true, 'lockedFor' => null];
        }

        $this->limiter->hit($key, self::LOCKOUT_SECONDS);

        return ['ok' => false, 'lockedFor' => $this->limiter->tooManyAttempts($key, self::MAX_TRIES) ? $this->limiter->availableIn($key) : null];
    }
}
