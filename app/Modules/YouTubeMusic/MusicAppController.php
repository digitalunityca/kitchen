<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The link between the music card and the YouTube Music app on the tablet, through
 * MacroDroid (an automation app on the tablet):
 * - MacroDroid posts the song playing in YouTube Music here (X-Hub-Token header,
 *   YTM_APP_TOKEN in .env), and the card shows it with ⏮ ⏯ ⏭.
 * - The card's buttons come back here and the hub calls MacroDroid's webhook for
 *   that button (YTM_APP_WEBHOOK in .env is the device's webhook address; the
 *   identifiers are play-pause, next and previous), which controls the app.
 * Neither secret is ever logged or sent to the page.
 */
class MusicAppController
{
    public const CACHE_KEY = 'youtube-music:app-now';

    /** A song report older than this (seconds) means the app isn't the active player any more. */
    public const FRESH_FOR = 600;

    public const ACTIONS = ['play-pause', 'next', 'previous'];

    /** POST …/app/now-playing (title, artist, playing), from MacroDroid. */
    public function store(Request $request): Response|JsonResponse
    {
        $token = (string) config('dashboard.modules.'.YouTubeMusicModule::class.'.app_token', '');
        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Hub-Token', ''))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'artist' => ['nullable', 'string', 'max:200'],
            'playing' => ['nullable', 'in:0,1,true,false,playing,paused,stopped'],
        ]);

        Cache::put(self::CACHE_KEY, [
            'title' => trim((string) ($data['title'] ?? '')),
            'artist' => trim((string) ($data['artist'] ?? '')),
            'playing' => in_array($data['playing'] ?? '1', ['1', 'true', 'playing'], true),
            'at' => now()->timestamp,
        ], now()->addDay());

        return response()->noContent();
    }

    /** GET …/app/now: what YouTube Music plays, for the card (polled while the dashboard is visible). */
    public function show(): JsonResponse
    {
        $now = Cache::get(self::CACHE_KEY);
        $active = is_array($now) && now()->timestamp - $now['at'] <= self::FRESH_FOR && $now['title'] !== '';

        return response()->json($active
            ? ['active' => true, 'title' => $now['title'], 'artist' => $now['artist'], 'playing' => $now['playing'], 'controls' => $this->webhook() !== '']
            : ['active' => false])->header('Cache-Control', 'no-store');
    }

    /** POST …/app/control (action=play-pause|next|previous): press a button in YouTube Music. */
    public function control(Request $request, Http $http): JsonResponse
    {
        $action = $request->validate(['action' => ['required', 'in:'.implode(',', self::ACTIONS)]])['action'];
        if ($this->webhook() === '') {
            return response()->json(['error' => 'not_configured'], 503);
        }

        try {
            // One MacroDroid webhook per button: <device url>/play-pause, /next, /previous.
            $ok = $http->timeout(6)->get(rtrim($this->webhook(), '/').'/'.$action)->successful();
        } catch (ConnectionException) {
            $ok = false;
        }

        if ($ok && $action === 'play-pause' && is_array($now = Cache::get(self::CACHE_KEY))) {
            Cache::put(self::CACHE_KEY, ['playing' => ! $now['playing']] + $now, now()->addDay()); // until MacroDroid confirms
        }

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }

    private function webhook(): string
    {
        return (string) config('dashboard.modules.'.YouTubeMusicModule::class.'.app_webhook', '');
    }
}
