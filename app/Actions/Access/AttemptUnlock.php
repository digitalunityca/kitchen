<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Http\Access\UnlockOutcome;
use Illuminate\Cache\RateLimiter;

/** Check a PIN from /unlock, counting failures per IP towards the lockout. */
class AttemptUnlock
{
    public function __construct(
        private readonly VerifyPin $verifyPin,
        private readonly CheckUnlockLockout $checkUnlockLockout,
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(string $ip, string $pin): UnlockOutcome
    {
        $key = CheckUnlockLockout::key($ip);

        if ($this->verifyPin->handle($pin)) {
            $this->limiter->clear($key);

            return UnlockOutcome::Unlocked;
        }

        $this->limiter->hit($key, CheckUnlockLockout::LOCKOUT_SECONDS);

        return $this->checkUnlockLockout->handle($ip) === null ? UnlockOutcome::WrongPin : UnlockOutcome::LockedOut;
    }
}
