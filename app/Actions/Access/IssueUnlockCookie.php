<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The cookie that lets a visitor outside the home in for COOKIE_DAYS. Encrypted
 * here rather than by EncryptCookies, because RestrictTunnelVisitors reads it
 * before that middleware runs.
 */
class IssueUnlockCookie
{
    public function __construct(
        private readonly AccessSettings $settings,
        private readonly StringEncrypter $encrypter,
    ) {}

    public function handle(): Cookie
    {
        $expires = now()->addDays(AccessGate::COOKIE_DAYS);
        $value = $this->encrypter->encryptString((string) json_encode([
            'v' => $this->settings->pinVersion(),
            'exp' => $expires->timestamp,
        ]));

        return Cookie::create(AccessGate::COOKIE, $value, $expires, '/', null, true, true, false, Cookie::SAMESITE_LAX);
    }
}
