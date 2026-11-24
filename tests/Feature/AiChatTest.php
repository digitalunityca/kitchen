<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AiChat\CreateRealtimeSession;
use App\Dashboard\ModuleRegistry;
use App\Modules\AiChat\AiChatModule;
use App\Modules\Weather\WeatherModule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    private const HOME = '203.0.113.7';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.openai.key' => null, 'dashboard.allowed_ips' => [self::HOME], 'dashboard.pin' => '4821']);
    }

    public function test_without_a_key_the_button_says_how_to_turn_it_on(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Ask ChatGPT (not set up)')
            ->assertSee('Add OPENAI_API_KEY to the hub’s .env to turn on Ask ChatGPT.', false)
            ->assertDontSee('data-session=', false);

        $this->postJson('/modules/ai-chat/session')->assertStatus(503)->assertJsonPath('error', 'not_configured');
    }

    public function test_with_a_key_the_mic_button_starts_a_voice_chat(): void
    {
        config(['services.openai.key' => 'sk-test-not-a-real-key']);

        $this->get('/')->assertOk()
            ->assertSee('aria-label="Talk to ChatGPT"', false)
            ->assertSee('id="aic-audio"', false)
            ->assertSee('prefers-reduced-motion', false)
            ->assertSee('data-session="'.route('modules.ai-chat.session').'"', false)
            ->assertSee('dashboard:audio-claim', false)
            ->assertDontSee('sk-test-not-a-real-key');
    }

    public function test_the_session_route_returns_only_the_short_lived_secret(): void
    {
        config(['services.openai.key' => 'sk-test-not-a-real-key']);
        Http::fake([CreateRealtimeSession::URL => Http::response(['value' => 'ek_abc123', 'expires_at' => 1764000060])]);

        $this->postJson('/modules/ai-chat/session')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['value' => 'ek_abc123', 'expires_at' => 1764000060, 'model' => 'gpt-realtime-2.1']);
    }

    public function test_an_openai_failure_is_a_502(): void
    {
        config(['services.openai.key' => 'sk-test-not-a-real-key']);
        Http::fake([CreateRealtimeSession::URL => Http::response('down', 500)]);

        $this->postJson('/modules/ai-chat/session')->assertStatus(502)->assertJsonPath('error', 'upstream');
    }

    public function test_visitors_outside_home_without_the_pin_cannot_start_a_chat(): void
    {
        $this->withHeader('CF-Connecting-IP', '192.0.2.1')->postJson('/modules/ai-chat/session')->assertForbidden();
    }

    public function test_sessions_are_rate_limited_to_ten_a_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/modules/ai-chat/session')->assertStatus(503);
        }

        $this->postJson('/modules/ai-chat/session')->assertStatus(429);
    }

    public function test_family_safe_mode_moderates_every_line_and_can_be_turned_off(): void
    {
        config(['services.openai.key' => 'sk-test-not-a-real-key']);
        Http::fake(['api.openai.com/v1/moderations' => Http::response(['results' => [['flagged' => true, 'categories' => ['violence' => true]]]])]);

        $this->get('/')->assertSee('data-moderate="'.route('modules.ai-chat.moderate').'"', false);
        $this->postJson('/modules/ai-chat/moderate', ['text' => 'a line'])->assertOk()->assertJsonPath('flagged', true);
        $this->postJson('/modules/ai-chat/moderate', [])->assertUnprocessable();

        config(['dashboard.modules.'.AiChatModule::class.'.family_safe' => false]);
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->get('/')->assertDontSee('data-moderate=', false);
        $this->postJson('/modules/ai-chat/moderate', ['text' => 'a line'])->assertOk()->assertJsonPath('flagged', false);
    }

    public function test_the_weather_tool_route_answers_from_the_hub_forecast(): void
    {
        config(['dashboard.modules.'.WeatherModule::class.'.lat' => null]);
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->getJson('/modules/ai-chat/weather?day=tomorrow')->assertOk()->assertJsonPath('available', false);
    }
}
