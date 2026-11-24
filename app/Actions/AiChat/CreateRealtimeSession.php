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
                        'instructions' => $this->buildAssistantInstructions->handle($this->settings->location, $this->settings->familySafe),
                        'tools' => [self::weatherTool(), self::searchTool()],
                        'tool_choice' => 'auto',
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

    /**
     * The get_weather function the model may call; the tablet answers it from
     * /modules/ai-chat/weather (App\Actions\AiChat\BuildWeatherToolResult).
     *
     * @return array<string, mixed>
     */
    public static function weatherTool(): array
    {
        return [
            'type' => 'function',
            'name' => 'get_weather',
            'description' => 'Current weather and the daily forecast (high, low, conditions, chance of precipitation) for the next 7 days at the family\'s home. Use it for any weather or temperature question.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'day' => [
                        'type' => 'string',
                        'description' => 'The day asked about, e.g. "today", "tomorrow", "saturday". Optional; the result always covers all 7 days.',
                    ],
                ],
            ],
        ];
    }

    /**
     * The search_web function for anything current; the tablet answers it from
     * /modules/ai-chat/search (App\Actions\AiChat\SearchWeb).
     *
     * @return array<string, mixed>
     */
    public static function searchTool(): array
    {
        return [
            'type' => 'function',
            'name' => 'search_web',
            'description' => 'Search the web for anything current or that may have changed: news, events, sports scores, opening hours, prices, recent facts. Returns a short answer to speak.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'What to look up, as a full question, in the language the user spoke.'],
                ],
                'required' => ['query'],
            ],
        ];
    }
}
