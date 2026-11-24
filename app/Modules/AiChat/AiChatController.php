<?php

declare(strict_types=1);

namespace App\Modules\AiChat;

use App\Actions\AiChat\CreateRealtimeSession;
use Illuminate\Http\JsonResponse;

/** POST /modules/ai-chat/session: a client secret for one voice chat. */
class AiChatController
{
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
