<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AiChat;

use App\Actions\AiChat\CreateRealtimeSession;
use App\Modules\AiChat\RealtimeSettings;
use App\Modules\AiChat\RealtimeUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CreateRealtimeSessionTest extends TestCase
{
    private const KEY = 'sk-test-not-a-real-key';

    private function create(?string $key = self::KEY): array
    {
        $this->app->instance(RealtimeSettings::class, new RealtimeSettings($key, 'gpt-realtime-2.1', 'marin', 'Sainte-Catherine, Quebec'));

        return $this->app->make(CreateRealtimeSession::class)->handle();
    }

    public function test_it_mints_a_one_minute_secret_for_the_kitchen_session(): void
    {
        Http::fake([CreateRealtimeSession::URL => Http::response(['value' => 'ek_abc123', 'expires_at' => 1764000060, 'session' => []])]);

        $session = $this->create();

        $this->assertSame(['value' => 'ek_abc123', 'expires_at' => 1764000060, 'model' => 'gpt-realtime-2.1'], $session);
        Http::assertSent(fn (Request $request): bool => $request->url() === CreateRealtimeSession::URL
            && $request->hasHeader('Authorization', 'Bearer '.self::KEY)
            && $request->hasHeader('OpenAI-Safety-Identifier')
            && $request['expires_after'] === ['anchor' => 'created_at', 'seconds' => 60]
            && $request['session']['type'] === 'realtime'
            && $request['session']['model'] === 'gpt-realtime-2.1'
            && $request['session']['audio']['output']['voice'] === 'marin'
            && $request['session']['audio']['input']['transcription']['model'] === 'gpt-4o-mini-transcribe'
            && str_contains($request['session']['instructions'], 'Sainte-Catherine, Quebec'));
    }

    public function test_without_a_key_it_refuses_and_calls_nothing(): void
    {
        Http::fake();

        try {
            $this->create('  ');
            $this->fail('Expected RealtimeUnavailable.');
        } catch (RealtimeUnavailable $e) {
            $this->assertSame(RealtimeUnavailable::NOT_CONFIGURED, $e->reason);
        }

        Http::assertNothingSent();
    }

    public function test_an_openai_error_is_reported_without_leaking_the_key(): void
    {
        Log::spy();
        Http::fake([CreateRealtimeSession::URL => Http::response(['error' => ['code' => 'invalid_api_key', 'message' => 'Incorrect API key provided: sk-test...']], 401)]);

        try {
            $this->create();
            $this->fail('Expected RealtimeUnavailable.');
        } catch (RealtimeUnavailable $e) {
            $this->assertSame(RealtimeUnavailable::UPSTREAM, $e->reason);
            $this->assertStringNotContainsString(self::KEY, $e->getMessage());
        }

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === ['status' => 401, 'code' => 'invalid_api_key']);
    }

    public function test_a_connection_error_is_reported_as_upstream(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->expectException(RealtimeUnavailable::class);

        $this->create();
    }

    public function test_a_response_without_a_secret_is_an_error(): void
    {
        Http::fake([CreateRealtimeSession::URL => Http::response(['session' => []])]);

        $this->expectException(RealtimeUnavailable::class);

        $this->create();
    }

    public function test_settings_hide_the_key_from_dumps(): void
    {
        $settings = new RealtimeSettings(self::KEY, 'm', 'v', 'l');

        $this->assertStringNotContainsString(self::KEY, print_r($settings, true));
        $this->assertFalse((new RealtimeSettings(null, 'm', 'v', 'l'))->isConfigured());
    }
}
