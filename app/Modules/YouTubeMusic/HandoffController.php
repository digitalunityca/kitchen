<?php

namespace App\Modules\YouTubeMusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Song handoff from YouTube Premium back to the card.
 *
 * The script Fully injects into youtube.com (resources/inject/youtube-handoff.js)
 * POSTs { v, t, list } every few seconds as text/plain, a "simple" CORS request
 * with no preflight. The route skips CSRF (it comes from youtube.com) but keeps
 * the tunnel IP/PIN rules and a rate limit. The card reads it on load and
 * clears it once it has picked the song up.
 */
class HandoffController
{
    public const ORIGIN = 'https://www.youtube.com';

    public function __construct(private HandoffStore $store) {}

    public function store(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $v = is_array($data) ? ($data['v'] ?? null) : null;
        $t = is_array($data) ? ($data['t'] ?? null) : null;
        $list = is_array($data) ? ($data['list'] ?? null) : null;

        if (! is_string($v) || ! preg_match('/^[\w-]{11}$/', $v)
            || ! is_numeric($t) || $t < 0 || $t > 86400
            || ($list !== null && (! is_string($list) || ! preg_match('/^[\w-]{10,80}$/', $list)))) {
            return response('', 422)->header('Access-Control-Allow-Origin', self::ORIGIN);
        }

        $this->store->put($v, (int) $t, $list);

        return response('', 204)->header('Access-Control-Allow-Origin', self::ORIGIN);
    }

    public function show(): JsonResponse
    {
        return response()->json($this->store->get())->header('Cache-Control', 'no-store');
    }

    public function clear(): Response
    {
        $this->store->clear();

        return response('', 204);
    }
}
