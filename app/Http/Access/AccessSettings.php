<?php

declare(strict_types=1);

namespace App\Http\Access;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * The access rules from config/dashboard.php. Bound per resolution (not as a
 * singleton), so a changed PIN applies to the very next request.
 */
final class AccessSettings
{
    /** @param  list<string>  $allowedIps  IPs or CIDR ranges of the home */
    public function __construct(
        public readonly array $allowedIps,
        public readonly string $pin,
        private readonly string $appKey,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            array_values((array) config('dashboard.allowed_ips', [])),
            (string) config('dashboard.pin', ''),
            (string) config('app.key'),
        );
    }

    public function hasAllowList(): bool
    {
        return $this->allowedIps !== [];
    }

    public function isHomeIp(string $ip): bool
    {
        return $this->hasAllowList() && IpUtils::checkIp($ip, $this->allowedIps);
    }

    public function pinEnabled(): bool
    {
        return $this->pin !== '';
    }

    /** Ties unlock cookies to the current PIN: a new PIN invalidates every cookie. */
    public function pinVersion(): string
    {
        return substr(hash_hmac('sha256', $this->pin, $this->appKey), 0, 16);
    }
}
