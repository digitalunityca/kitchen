<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Http\Access\AccessSettings;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Throwable;

/** Whether an unlock cookie is genuine, unexpired and issued for the current PIN. */
class VerifyUnlockCookie
{
    public function __construct(
        private readonly AccessSettings $settings,
        private readonly StringEncrypter $encrypter,
    ) {}

    public function handle(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || ! $this->settings->pinEnabled()) {
            return false;
        }

        try {
            $data = json_decode($this->encrypter->decryptString($value), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($data)
            && hash_equals($this->settings->pinVersion(), (string) ($data['v'] ?? ''))
            && (int) ($data['exp'] ?? 0) > now()->timestamp;
    }
}
