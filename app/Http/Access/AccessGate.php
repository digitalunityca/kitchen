<?php

declare(strict_types=1);

namespace App\Http\Access;

use Illuminate\Http\Request;

/**
 * Request helpers for tunnel access. The rules themselves (home IPs, PIN,
 * unlock cookie) live in AccessSettings and the actions in App\Actions\Access.
 */
final class AccessGate
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

    /** Only local paths, so ?to= can't send visitors to another site. */
    public static function safeTarget(mixed $to): string
    {
        if (! is_string($to) || ! str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
            return '/';
        }

        return $to;
    }
}
