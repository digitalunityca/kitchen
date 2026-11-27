<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouTubeMusicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_the_card_is_a_remote_for_the_youtube_music_app(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="ytm"', false)
            ->assertSee('data-package="com.google.android.apps.youtube.music"', false)
            ->assertSee('Open YouTube Music', false)
            ->assertSee('data-app-action="play-pause"', false)
            ->assertDontSee('ytm-player', false)
            ->assertDontSee('youtube.com/tv', false)
            ->assertDontSee('iframe_api', false);
    }

    public function test_the_old_playlist_and_handoff_pages_are_gone(): void
    {
        $this->get('/modules/youtube-music/settings')->assertNotFound();
        $this->get('/modules/youtube-music/handoff')->assertNotFound();
    }
}
