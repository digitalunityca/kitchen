<?php

declare(strict_types=1);

namespace App\Modules\Habs;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The NHL's public web API (api-web.nhle.com), with caching. A failed call
 * returns null so the dashboard can hide Canadiens info quietly; failures are
 * cached for a minute so a down API isn't hammered.
 */
class NhlApi
{
    public const BASE = 'https://api-web.nhle.com/v1/';

    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly LoggerInterface $log,
    ) {}

    /**
     * @param  int|Closure(array<string, mixed>): int  $ttlSeconds  fixed, or computed from the response
     * @return array<string, mixed>|null
     */
    public function get(string $path, int|Closure $ttlSeconds): ?array
    {
        $key = 'nhl.'.$path;
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            return $cached['ok'] ? $cached['data'] : null;
        }

        try {
            $response = $this->http->acceptJson()->timeout(8)->get(self::BASE.$path);
            $data = $response->successful() ? $response->json() : null;
        } catch (Throwable $e) {
            $data = null;
            $response = null;
        }

        if (! is_array($data)) {
            $this->log->warning('NHL API request failed.', ['path' => $path, 'status' => $response?->status()]);
            $this->cache->put($key, ['ok' => false], 60);

            return null;
        }

        $ttl = $ttlSeconds instanceof Closure ? $ttlSeconds($data) : $ttlSeconds;
        $this->cache->put($key, ['ok' => true, 'data' => $data], max(30, $ttl));

        return $data;
    }
}
