<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Download one calendar's iCal feed. Cached 15 minutes; if the feed fails, the
 * last good copy is used so the screen keeps its events. Logs name the
 * calendar, never its (secret) URL.
 */
class FetchCalendarFeed
{
    public const CACHE_MINUTES = 15;

    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly LoggerInterface $log,
    ) {}

    /** @param  array{name: string, url: string}  $calendar */
    public function handle(array $calendar, bool $fresh = false): ?string
    {
        $key = 'calendar.feed.'.sha1($calendar['url']);

        if (! $fresh && is_string($cached = $this->cache->get($key))) {
            return $cached;
        }

        try {
            $response = $this->http->timeout(10)->withHeaders(['Accept' => 'text/calendar, */*'])->get(self::httpUrl($calendar['url']));
            $body = $response->successful() ? $response->body() : null;
        } catch (Throwable) {
            $body = null;
            $response = null;
        }

        if ($body === null || ! str_contains($body, 'BEGIN:VCALENDAR')) {
            $this->log->warning('Calendar feed unavailable.', ['calendar' => $calendar['name'], 'status' => $response?->status()]);
            $this->cache->put($key, $last = $this->cache->get($key.'.last'), now()->addMinutes(2));

            return is_string($last) ? $last : null;
        }

        $this->cache->put($key, $body, now()->addMinutes(self::CACHE_MINUTES));
        $this->cache->forever($key.'.last', $body);

        return $body;
    }

    /**
     * The feed address for a pasted link: webcal:// links (iCloud) are plain HTTPS,
     * and a Google Calendar embed link (…/calendar/embed?src=ID) becomes that
     * calendar's public iCal feed.
     */
    public static function httpUrl(string $url): string
    {
        $url = (string) preg_replace('#^webcals?://#i', 'https://', trim($url));

        if (preg_match('#^https://calendar\.google\.com/calendar/(?:u/\d+/)?embed\?#i', $url)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $src = is_array($query['src'] ?? null) ? ($query['src'][0] ?? '') : (string) ($query['src'] ?? '');

            if ($src !== '') {
                return 'https://calendar.google.com/calendar/ical/'.rawurlencode($src).'/public/basic.ics';
            }
        }

        return $url;
    }
}
