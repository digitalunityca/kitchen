<?php

declare(strict_types=1);

namespace App\Actions\AiChat;

use App\Modules\AiChat\RealtimeSettings;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Answer the voice assistant's search_web tool: ask the Responses API (with
 * its built-in web_search tool, localized to the family's town) for a short,
 * spoken-style answer. In family-safe mode the answer goes through the same
 * moderation check as the chat before the assistant may say it.
 */
class SearchWeb
{
    public const URL = 'https://api.openai.com/v1/responses';

    public function __construct(
        private readonly RealtimeSettings $settings,
        private readonly Http $http,
        private readonly ModerateText $moderateText,
        private readonly LoggerInterface $log,
    ) {}

    /** @return array{ok: bool, answer?: string, error?: string, note?: string} */
    public function handle(string $query): array
    {
        $query = trim($query);

        if ($query === '' || ! $this->settings->isConfigured()) {
            return $this->failure('unavailable', 'Web search is not available. Say so briefly.');
        }

        try {
            $response = $this->http->withToken((string) $this->settings->apiKey)
                ->acceptJson()
                ->timeout(30)
                ->post(self::URL, [
                    'model' => $this->settings->searchModel,
                    'instructions' => $this->instructions(),
                    'input' => $query,
                    'tools' => [['type' => 'web_search', 'user_location' => $this->userLocation()]],
                    'max_output_tokens' => 1200,
                ]);
        } catch (Throwable $e) {
            $this->log->warning('OpenAI web search failed.', ['error' => $e::class]);

            return $this->failure('unavailable', 'The web search did not answer. Say you could not look it up right now.');
        }

        if (! $response->successful()) {
            $context = ['status' => $response->status(), 'code' => $response->json('error.code')];

            if (in_array($response->status(), [401, 403], true)) {
                $this->log->warning('OpenAI web search refused: add the Responses permission (Write) to the OpenAI key.', $context);

                return $this->failure('missing_permission', 'Web search is not set up yet: the OpenAI key needs the Responses permission. Tell the user briefly.');
            }

            $this->log->warning('OpenAI web search failed.', $context);

            return $this->failure('unavailable', 'The web search did not answer. Say you could not look it up right now.');
        }

        $answer = $this->speakable($this->outputText((array) $response->json('output', [])));

        if ($answer === '') {
            return $this->failure('no_answer', 'The search found nothing useful. Say you could not find it.');
        }

        if ($this->moderateText->handle($answer)['flagged']) {
            return $this->failure('unsuitable', 'The result is not suitable for children. Say kindly that you cannot talk about that here, and offer another topic.');
        }

        return ['ok' => true, 'answer' => $answer];
    }

    private function instructions(): string
    {
        $rules = [
            'Search the web and answer the question for a family in '.$this->settings->location.', Canada. Today is '.now()->toFormattedDayDateString().'.',
            'Reply in two to four short plain sentences that will be read aloud: no markdown, lists, links, URLs or source names unless they are the answer. Use metric units and degrees Celsius.',
            'Prefer local and Canadian results when the question is about places, hours, prices or events.',
        ];

        if ($this->settings->familySafe) {
            $rules[] = 'A child may be listening: keep the answer suitable for young children and leave out anything violent, sexual, scary or otherwise mature. If the question cannot be answered that way, answer only: "That is not something I can talk about here."';
        }

        return implode(' ', $rules);
    }

    /** @return array<string, string> */
    private function userLocation(): array
    {
        [$city, $region] = array_pad(array_map('trim', explode(',', $this->settings->location, 2)), 2, null);

        return array_filter([
            'type' => 'approximate',
            'country' => 'CA',
            'city' => $city,
            'region' => $region,
            'timezone' => (string) config('app.timezone') !== 'UTC' ? (string) config('app.timezone') : 'America/Toronto',
        ]);
    }

    /** @param  array<int, mixed>  $output */
    private function outputText(array $output): string
    {
        $text = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $part) {
                if (($part['type'] ?? null) === 'output_text' && is_string($part['text'] ?? null)) {
                    $text[] = $part['text'];
                }
            }
        }

        return implode("\n", $text);
    }

    /** Drop citation links, URLs and markdown so the answer reads aloud cleanly. */
    private function speakable(string $text): string
    {
        $text = (string) preg_replace('/\s*\(\[[^\]]*\]\([^)]*\)\)/', '', $text);
        $text = (string) preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = (string) preg_replace('~https?://\S+~', '', $text);
        $text = str_replace(['**', '__', '#', '`'], '', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    /** @return array{ok: false, error: string, note: string} */
    private function failure(string $error, string $note): array
    {
        return ['ok' => false, 'error' => $error, 'note' => $note];
    }
}
