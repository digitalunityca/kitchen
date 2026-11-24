<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AiChat\CreateRealtimeSession;
use App\Dashboard\ModuleRegistry;
use App\Modules\AiChat\AiChatModule;
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

    public function test_without_a_key_the_tile_says_how_to_turn_it_on(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Ask ChatGPT')
            ->assertSee('Add OPENAI_API_KEY to turn this on')
            ->assertDontSee('id="aic-panel"', false);

        $this->postJson('/modules/ai-chat/session')->assertStatus(503)->assertJsonPath('error', 'not_configured');
    }

    public function test_with_a_key_the_tile_opens_the_voice_panel(): void
    {
        config(['services.openai.key' => 'sk-test-not-a-real-key']);

        $this->get('/')->assertOk()
            ->assertSee('id="aic-open"', false)
            ->assertSee('id="aic-panel"', false)
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

    public function test_as_a_grid_card_it_shows_the_full_tile(): void
    {
        config([
            'services.openai.key' => 'sk-test-not-a-real-key',
            'dashboard.modules.'.AiChatModule::class.'.placement' => 'grid',
        ]);
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->get('/')->assertOk()->assertSee('Tap and talk: recipes, swaps, timings')->assertSee('class="aic card"', false);
    }
}
