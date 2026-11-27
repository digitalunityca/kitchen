<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The link between the music card and the YouTube Music app on the tablet, through
 * MacroDroid (an automation app on the tablet):
 * - MacroDroid posts the song playing in YouTube Music here (X-Hub-Token header,
 *   YTM_APP_TOKEN in .env), and the card shows it with ⏮ ⏯ ⏭.
 * - The card's buttons queue a command here; MacroDroid fetches it every 2 s (same
 *   token) and presses play/pause, next or previous in YouTube Music.
 * The token is never logged or sent to the page.
 */
class MusicAppController
{
    public const CACHE_KEY = 'youtube-music:app-now';

    /** A song report older than this (seconds) means the app isn't the active player any more. */
    public const FRESH_FOR = 600;

    public const ACTIONS = ['play-pause', 'next', 'previous'];

    public const COMMAND_KEY = 'youtube-music:app-command';

    public const POLL_KEY = 'youtube-music:app-poll';

    /** POST …/app/now-playing (title, artist, playing), from MacroDroid. */
    public function store(Request $request): Response|JsonResponse
    {
        if (! $this->authorized($request)) {
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
            ? ['active' => true, 'title' => $now['title'], 'artist' => $now['artist'], 'playing' => $now['playing'], 'controls' => $this->pollerAlive()]
            : ['active' => false])->header('Cache-Control', 'no-store');
    }

    /**
     * POST …/app/control (action=play-pause|next|previous): a card button. The command
     * waits here until MacroDroid on the tablet fetches it (GET …/app/command). Push
     * (MacroDroid's webhook) doesn't reach Fire tablets, so the tablet asks every 2 s.
     */
    public function control(Request $request): JsonResponse
    {
        $action = $request->validate(['action' => ['required', 'in:'.implode(',', self::ACTIONS)]])['action'];
        if (! $this->pollerAlive()) {
            return response()->json(['error' => 'not_connected'], 503);
        }

        Cache::put(self::COMMAND_KEY, $action, now()->addSeconds(20));
        if ($action === 'play-pause' && is_array($now = Cache::get(self::CACHE_KEY))) {
            Cache::put(self::CACHE_KEY, ['playing' => ! $now['playing']] + $now, now()->addDay()); // until MacroDroid confirms
        }

        return response()->json(['ok' => true]);
    }

    /** GET …/app/command, from MacroDroid every 2 s: the waiting button press (plain text), or nothing. */
    public function command(Request $request): Response
    {
        if (! $this->authorized($request)) {
            return response('', 401);
        }

        Cache::put(self::POLL_KEY, now()->timestamp, now()->addMinutes(5));

        return response((string) Cache::pull(self::COMMAND_KEY, ''), 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
    }

    private function pollerAlive(): bool
    {
        return now()->timestamp - (int) Cache::get(self::POLL_KEY, 0) <= 30;
    }

    private function authorized(Request $request): bool
    {
        $token = (string) config('dashboard.modules.'.YouTubeMusicModule::class.'.app_token', '');

        return $token !== '' && hash_equals($token, (string) $request->header('X-Hub-Token', ''));
    }
}
