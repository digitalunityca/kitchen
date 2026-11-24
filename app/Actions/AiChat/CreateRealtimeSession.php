<?php

declare(strict_types=1);

namespace App\Actions\AiChat;

use App\Modules\AiChat\RealtimeSettings;
use App\Modules\AiChat\RealtimeUnavailable;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Mint a short-lived OpenAI Realtime client secret. The tablet uses it to open
 * a WebRTC call straight to OpenAI (POST /v1/realtime/calls), so the real API
 * key never leaves the server. The session (model, voice, persona, captions)
 * is fixed here and travels inside the secret.
 */
class CreateRealtimeSession
{
    public const URL = 'https://api.openai.com/v1/realtime/client_secrets';

    /** The secret only has to last until the tablet has connected. */
    public const SECRET_SECONDS = 60;

    public function __construct(
        private readonly RealtimeSettings $settings,
        private readonly Http $http,
        private readonly LoggerInterface $log,
        private readonly BuildAssistantInstructions $buildAssistantInstructions,
    ) {}

    /**
     * @return array{value: string, expires_at: int, model: string}
     *
     * @throws RealtimeUnavailable
     */
    public function handle(): array
    {
        if (! $this->settings->isConfigured()) {
            throw RealtimeUnavailable::notConfigured();
        }

        try {
            $response = $this->http->withToken((string) $this->settings->apiKey)
                ->withHeaders(['OpenAI-Safety-Identifier' => hash('sha256', 'home-hub:'.config('app.url'))])
                ->acceptJson()
                ->timeout(10)
                ->post(self::URL, [
                    'expires_after' => ['anchor' => 'created_at', 'seconds' => self::SECRET_SECONDS],
                    'session' => [
                        'type' => 'realtime',
                        'model' => $this->settings->model,
                        'instructions' => $this->buildAssistantInstructions->handle($this->settings->location),
                        'audio' => [
                            'input' => [
                                'transcription' => ['model' => 'gpt-4o-mini-transcribe'],
                                'noise_reduction' => ['type' => 'far_field'],
                                'turn_detection' => ['type' => 'server_vad', 'silence_duration_ms' => 600],
                            ],
                            'output' => ['voice' => $this->settings->voice],
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            $this->log->warning('OpenAI Realtime session request failed.', ['error' => $e::class]);

            throw RealtimeUnavailable::upstream('OpenAI did not answer.');
        }

        $value = $response->json('value');

        if (! $response->successful() || ! is_string($value) || $value === '') {
            // Status and OpenAI's error code only: never the key or the secret.
            $this->log->warning('OpenAI Realtime session request failed.', [
                'status' => $response->status(),
                'code' => $response->json('error.code'),
            ]);

            throw RealtimeUnavailable::upstream('OpenAI refused the session (HTTP '.$response->status().').');
        }

        return [
            'value' => $value,
            'expires_at' => (int) $response->json('expires_at'),
            'model' => $this->settings->model,
        ];
    }
}
