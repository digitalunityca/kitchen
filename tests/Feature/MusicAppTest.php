<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\YouTubeMusic\YouTubeMusicModule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MusicAppTest extends TestCase
{
    private const KEY = 'dashboard.modules.'.YouTubeMusicModule::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([self::KEY.'.app_token' => 'test-token-123', self::KEY.'.app_webhook' => 'https://trigger.example.test/device']);
    }

    public function test_macrodroid_reports_the_song_with_its_token_and_the_card_shows_it(): void
    {
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => false]);

        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'Every Breath You Take', 'artist' => 'The Police'])->assertUnauthorized();
        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'x'], ['X-Hub-Token' => 'wrong'])->assertUnauthorized();
        $this->postJson('/modules/youtube-music/app/now-playing', ['title' => str_repeat('a', 300)], ['X-Hub-Token' => 'test-token-123'])->assertUnprocessable();

        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'Every Breath You Take', 'artist' => 'The Police', 'playing' => '1'], ['X-Hub-Token' => 'test-token-123'])->assertNoContent();
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => true, 'title' => 'Every Breath You Take', 'artist' => 'The Police', 'playing' => true, 'controls' => true]);

        // Ten minutes without news: the app isn't the active player any more.
        $this->travel(11)->minutes();
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => false]);

        $this->get('/')->assertSee('id="ytm-appnow"', false)->assertDontSee('test-token-123')->assertDontSee('trigger.example.test');
    }

    public function test_without_a_token_configured_reports_are_refused(): void
    {
        config([self::KEY.'.app_token' => '']);
        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'x'], ['X-Hub-Token' => ''])->assertUnauthorized();
    }

    public function test_the_card_buttons_call_macrodroids_webhook(): void
    {
        Http::fake(['trigger.example.test/*' => Http::response('ok')]);

        $this->postJson('/modules/youtube-music/app/control', ['action' => 'next'])->assertOk()->assertJson(['ok' => true]);
        Http::assertSent(fn (Request $r): bool => $r->url() === 'https://trigger.example.test/device/next');
        $this->postJson('/modules/youtube-music/app/control', ['action' => 'rm -rf'])->assertUnprocessable();

        config([self::KEY.'.app_webhook' => '']);
        $this->postJson('/modules/youtube-music/app/control', ['action' => 'next'])->assertStatus(503);
    }
}
