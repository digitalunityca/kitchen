<?php

declare(strict_types=1);

namespace App\Modules\AiChat;

use App\Actions\AiChat\BuildWeatherToolResult;
use App\Actions\AiChat\CreateRealtimeSession;
use App\Actions\AiChat\ModerateText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoints for the voice chat: a client secret per chat, and a moderation check per spoken line. */
class AiChatController
{
    /** GET /modules/ai-chat/weather: the result of the assistant's get_weather tool. */
    public function weather(Request $request, BuildWeatherToolResult $buildWeatherToolResult): JsonResponse
    {
        $day = $request->query('day');

        return response()->json($buildWeatherToolResult->handle(is_string($day) ? mb_substr($day, 0, 40) : null))
            ->header('Cache-Control', 'no-store');
    }

    /** POST /modules/ai-chat/moderate: is this transcript OK for a family kitchen? */
    public function moderate(Request $request, ModerateText $moderateText): JsonResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:2000']]);

        return response()->json($moderateText->handle($data['text']))->header('Cache-Control', 'no-store');
    }

    public function session(CreateRealtimeSession $createRealtimeSession): JsonResponse
    {
        try {
            $session = $createRealtimeSession->handle();
        } catch (RealtimeUnavailable $e) {
            $status = $e->reason === RealtimeUnavailable::NOT_CONFIGURED ? 503 : 502;

            return response()->json(['error' => $e->reason, 'message' => $e->getMessage()], $status)
                ->header('Cache-Control', 'no-store');
        }

        return response()->json($session)->header('Cache-Control', 'no-store');
    }
}
