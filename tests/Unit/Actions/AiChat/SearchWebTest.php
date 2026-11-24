<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AiChat;

use App\Actions\AiChat\ModerateText;
use App\Actions\AiChat\SearchWeb;
use App\Modules\AiChat\RealtimeSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SearchWebTest extends TestCase
{
    private const KEY = 'sk-test-not-a-real-key';

    private function search(string $query, bool $familySafe = true): array
    {
        $this->app->instance(RealtimeSettings::class, new RealtimeSettings(self::KEY, 'gpt-realtime-2.1', 'marin', 'Sainte-Catherine, Quebec', $familySafe, 'gpt-5-mini'));

        return $this->app->make(SearchWeb::class)->handle($query);
    }

    /** @return array<string, mixed> */
    private static function answer(string $text): array
    {
        return ['output' => [
            ['type' => 'web_search_call', 'status' => 'completed'],
            ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => []]]],
        ]];
    }

    private static function notFlagged(): array
    {
        return ['results' => [['flagged' => false, 'categories' => []]]];
    }

    public function test_it_asks_the_responses_api_with_local_web_search_and_returns_a_speakable_answer(): void
    {
        Http::fake([
            SearchWeb::URL => Http::response(self::answer('The **Canadiens** won 4–2 last night ([nhl.com](https://www.nhl.com/game/1)). See https://example.com for more.')),
            ModerateText::URL => Http::response(self::notFlagged()),
        ]);

        $this->assertSame(['ok' => true, 'answer' => 'The Canadiens won 4–2 last night. See for more.'], $this->search('Did the Canadiens win last night?'));

        Http::assertSent(fn (Request $request): bool => $request->url() === SearchWeb::URL
            && $request->hasHeader('Authorization', 'Bearer '.self::KEY)
            && $request['model'] === 'gpt-5-mini'
            && $request['input'] === 'Did the Canadiens win last night?'
            && $request['tools'][0]['type'] === 'web_search'
            && $request['tools'][0]['user_location']['country'] === 'CA'
            && $request['tools'][0]['user_location']['city'] === 'Sainte-Catherine'
            && $request['tools'][0]['user_location']['region'] === 'Quebec'
            && str_contains($request['instructions'], 'suitable for young children'));
        Http::assertSent(fn (Request $request): bool => $request->url() === ModerateText::URL);
    }

    public function test_an_answer_flagged_by_moderation_is_withheld(): void
    {
        Http::fake([
            SearchWeb::URL => Http::response(self::answer('Something unsuitable.')),
            ModerateText::URL => Http::response(['results' => [['flagged' => true, 'categories' => ['violence' => true]]]]),
        ]);

        $result = $this->search('tell me something');

        $this->assertFalse($result['ok']);
        $this->assertSame('unsuitable', $result['error']);
        $this->assertArrayNotHasKey('answer', $result);
    }

    public function test_a_missing_responses_permission_says_what_to_fix_without_logging_the_key(): void
    {
        Log::spy();
        Http::fake([SearchWeb::URL => Http::response(['error' => ['code' => 'missing_scope']], 401)]);

        $result = $this->search('news today');

        $this->assertSame('missing_permission', $result['error']);
        $this->assertStringContainsString('Responses permission', $result['note']);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => str_contains($message, 'Responses permission')
            && $context === ['status' => 401, 'code' => 'missing_scope']);
    }

    public function test_a_connection_error_fails_gracefully(): void
    {
        Http::fake([SearchWeb::URL => fn () => throw new ConnectionException('timed out')]);

        $this->assertSame('unavailable', $this->search('anything')['error']);
    }

    public function test_an_empty_answer_says_nothing_was_found(): void
    {
        Http::fake([SearchWeb::URL => Http::response(['output' => [['type' => 'web_search_call']]])]);

        $this->assertSame('no_answer', $this->search('anything')['error']);
    }

    public function test_without_family_safe_mode_there_is_no_moderation_call_or_child_rule(): void
    {
        Http::fake([SearchWeb::URL => Http::response(self::answer('Fine answer.'))]);

        $this->assertTrue($this->search('anything', familySafe: false)['ok']);
        Http::assertNotSent(fn (Request $request): bool => $request->url() === ModerateText::URL);
        Http::assertSent(fn (Request $request): bool => $request->url() === SearchWeb::URL && ! str_contains($request['instructions'], 'child'));
    }
}
