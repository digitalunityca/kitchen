<?php

declare(strict_types=1);

namespace App\Actions\AiChat;

use App\Modules\AiChat\RealtimeSettings;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Check a line from the voice chat (what the cook said, or what the assistant
 * said) with OpenAI's free moderation model. The tablet ends the chat when it
 * is flagged. This is the second line of defence after the family-safe
 * persona, so when moderation is unavailable the chat carries on and the
 * problem is logged, once a day, never with the key.
 */
class ModerateText
{
    public const URL = 'https://api.openai.com/v1/moderations';

    public const MODEL = 'omni-moderation-latest';

    private const LOGGED_KEY = 'ai-chat.moderation-unavailable-logged';

    public function __construct(
        private readonly RealtimeSettings $settings,
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly LoggerInterface $log,
    ) {}

    /**
     * @return array{flagged: bool, categories: list<string>, checked: bool}
     */
    public function handle(string $text): array
    {
        $text = trim($text);

        if ($text === '' || ! $this->settings->familySafe || ! $this->settings->isConfigured()) {
            return ['flagged' => false, 'categories' => [], 'checked' => false];
        }

        try {
            $response = $this->http->withToken((string) $this->settings->apiKey)
                ->acceptJson()
                ->timeout(5)
                ->post(self::URL, ['model' => self::MODEL, 'input' => $text]);
        } catch (Throwable $e) {
            return $this->unavailable(['error' => $e::class]);
        }

        if (! $response->successful()) {
            $context = ['status' => $response->status(), 'code' => $response->json('error.code')];

            if (in_array($response->status(), [401, 403], true)) {
                $context['hint'] = 'The OpenAI key may lack the Moderations permission (set it to Request).';
            }

            return $this->unavailable($context);
        }

        $result = (array) $response->json('results.0', []);

        return [
            'flagged' => (bool) ($result['flagged'] ?? false),
            'categories' => array_values(array_map('strval', array_keys(array_filter((array) ($result['categories'] ?? []))))),
            'checked' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{flagged: false, categories: list<string>, checked: false}
     */
    private function unavailable(array $context): array
    {
        if ($this->cache->add(self::LOGGED_KEY, true, now()->addDay())) {
            $this->log->warning('OpenAI moderation unavailable; the voice chat relies on its family-safe instructions only.', $context);
        }

        return ['flagged' => false, 'categories' => [], 'checked' => false];
    }
}
