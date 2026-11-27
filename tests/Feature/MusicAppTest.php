<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\YouTubeMusic\YouTubeMusicModule;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MusicAppTest extends TestCase
{
    private const KEY = 'dashboard.modules.'.YouTubeMusicModule::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([self::KEY.'.app_token' => 'test-token-123']);
    }

    public function test_macrodroid_reports_the_song_with_its_token_and_the_card_shows_it(): void
    {
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => false]);

        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'Every Breath You Take', 'artist' => 'The Police'])->assertUnauthorized();
        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'x'], ['X-Hub-Token' => 'wrong'])->assertUnauthorized();
        $this->postJson('/modules/youtube-music/app/now-playing', ['title' => str_repeat('a', 300)], ['X-Hub-Token' => 'test-token-123'])->assertUnprocessable();

        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'Every Breath You Take', 'artist' => 'The Police', 'playing' => '1'], ['X-Hub-Token' => 'test-token-123'])->assertNoContent();
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => true, 'title' => 'Every Breath You Take', 'artist' => 'The Police', 'playing' => true, 'controls' => false]);

        // Ten minutes without news: the app isn't the active player any more.
        $this->travel(11)->minutes();
        $this->getJson('/modules/youtube-music/app/now')->assertExactJson(['active' => false]);

        $this->get('/')->assertSee('id="ytm"', false)->assertDontSee('test-token-123');
    }

    public function test_without_a_token_configured_reports_are_refused(): void
    {
        config([self::KEY.'.app_token' => '']);
        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'x'], ['X-Hub-Token' => ''])->assertUnauthorized();
    }

    public function test_card_buttons_queue_a_command_that_macrodroid_fetches(): void
    {
        // Nobody fetching yet: the buttons say so.
        $this->postJson('/modules/youtube-music/app/control', ['action' => 'next'])->assertStatus(503);

        $this->get('/modules/youtube-music/app/command')->assertUnauthorized();
        $this->get('/modules/youtube-music/app/command', ['X-Hub-Token' => 'test-token-123'])->assertOk()->assertContent('');

        $this->postJson('/modules/youtube-music/app/control', ['action' => 'next'])->assertOk();
        $this->postJson('/modules/youtube-music/app/control', ['action' => 'rm -rf'])->assertUnprocessable();
        $this->get('/modules/youtube-music/app/command', ['X-Hub-Token' => 'test-token-123'])->assertContent('next');
        $this->get('/modules/youtube-music/app/command', ['X-Hub-Token' => 'test-token-123'])->assertContent('');

        // MacroDroid stopped asking: no controls on the card.
        $this->post('/modules/youtube-music/app/now-playing', ['title' => 'Song', 'artist' => 'Band'], ['X-Hub-Token' => 'test-token-123']);
        $this->travel(31)->seconds();
        $this->getJson('/modules/youtube-music/app/now')->assertJsonPath('controls', false);
    }
}
