<?php

namespace App\Http\Access;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

/**
 * Who may use the hub through the Cloudflare Tunnel: visitors from the home IP
 * (HOME_HUB_ALLOWED_IPS), or anyone holding a valid unlock cookie, which is
 * issued after entering HOME_HUB_PIN on /unlock.
 */
class AccessGate
{
    public const COOKIE = 'home_hub_unlock';

    public const COOKIE_DAYS = 30;

    /** Requests through the tunnel carry the visitor's address in CF-Connecting-IP. */
    public static function viaTunnel(Request $request): bool
    {
        return $request->headers->has('CF-Connecting-IP');
    }

    /** The visitor's real address: CF-Connecting-IP when present, else the request IP. */
    public static function clientIp(Request $request): string
    {
        return (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
    }

    public static function allowedIps(): array
    {
        return config('dashboard.allowed_ips', []);
    }

    public static function fromHome(Request $request): bool
    {
        $allowed = self::allowedIps();

        return $allowed !== [] && IpUtils::checkIp(self::clientIp($request), $allowed);
    }

    public static function pin(): string
    {
        return (string) config('dashboard.pin', '');
    }

    public static function pinEnabled(): bool
    {
        return self::pin() !== '';
    }

    public static function pinMatches(string $attempt): bool
    {
        return self::pinEnabled() && hash_equals(self::pin(), $attempt);
    }

    /** Ties cookies to the current PIN: a new PIN invalidates every cookie. */
    private static function pinVersion(): string
    {
        return substr(hash_hmac('sha256', self::pin(), (string) config('app.key')), 0, 16);
    }

    public static function makeCookie(): Cookie
    {
        $expires = now()->addDays(self::COOKIE_DAYS);
        $value = Crypt::encryptString(json_encode(['v' => self::pinVersion(), 'exp' => $expires->timestamp]));

        return Cookie::create(self::COOKIE, $value, $expires, '/', null, true, true, false, Cookie::SAMESITE_LAX);
    }

    public static function hasValidCookie(Request $request): bool
    {
        $raw = $request->cookies->get(self::COOKIE);

        if (! is_string($raw) || $raw === '' || ! self::pinEnabled()) {
            return false;
        }

        try {
            $data = json_decode(Crypt::decryptString($raw), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($data)
            && hash_equals(self::pinVersion(), (string) ($data['v'] ?? ''))
            && (int) ($data['exp'] ?? 0) > now()->timestamp;
    }

    /** Only local paths, so ?to= can't send visitors to another site. */
    public static function safeTarget(?string $to): string
    {
        if (! is_string($to) || ! str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
            return '/';
        }

        return $to;
    }
}
