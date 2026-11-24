<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic;

use App\Actions\YouTubeMusic\RecordHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request, RecordHandoff $recordHandoff): Response
    {
        $payload = json_decode($request->getContent(), true);

        $payload = is_array($payload) ? $payload : [];

        $validator = Validator::make($payload, [
            'v' => ['required', 'string', 'regex:/^[\w-]{11}$/'],
            't' => ['required', 'numeric', 'min:0', 'max:86400'],
            'list' => ['nullable', 'string', 'regex:/^[\w-]{10,80}$/'],
        ])->after(function ($validator) use ($payload): void {
            // Rules skip blank strings, so "list": "" would slip through as a playlist id.
            if (is_string($payload['list'] ?? null) && trim($payload['list']) === '') {
                $validator->errors()->add('list', 'The list field must be a playlist id or null.');
            }
        });

        if ($validator->fails()) {
            return response('', 422)->header('Access-Control-Allow-Origin', self::ORIGIN);
        }

        $data = $validator->validated();
        $recordHandoff->handle($data['v'], $data['t'] + 0, $data['list'] ?? null);

        return response('', 204)->header('Access-Control-Allow-Origin', self::ORIGIN);
    }

    public function show(HandoffStore $store): JsonResponse
    {
        return response()->json($store->get())->header('Cache-Control', 'no-store');
    }

    public function clear(HandoffStore $store): Response
    {
        $store->clear();

        return response('', 204);
    }
}
