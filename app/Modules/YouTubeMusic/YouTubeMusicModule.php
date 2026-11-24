<?php

namespace App\Modules\YouTubeMusic;

use App\Dashboard\Module;
use Illuminate\Routing\Router;

/**
 * Plays YouTube / YouTube Music playlists through the official YouTube
 * IFrame Player, with Apple-style controls drawn by the dashboard.
 *
 * Why not music.youtube.com itself: it refuses to load inside a frame, and
 * Fire OS has no Google Play, so the app needs a Play Services sideload.
 * The "Open YouTube Premium" button loads the YouTube TV app in Fully
 * instead, switching Fully to a TV user agent only for that trip, because
 * the IFrame Player refuses to play under a TV user agent.
 */
class YouTubeMusicModule extends Module
{
    public function key(): string
    {
        return 'youtube-music';
    }

    public function name(): string
    {
        return 'YouTube Music';
    }

    public function data(): array
    {
        return [
            'playlists' => $this->store()->all(),
            'volume' => (int) $this->config('volume', 60),
            'tvUrl' => (string) $this->config('tv_url', 'https://www.youtube.com/tv'),
            'tvUserAgent' => (string) $this->config('tv_user_agent', ''),
        ];
    }

    public function store(): PlaylistStore
    {
        return new PlaylistStore($this->config('playlists', []));
    }

    public function routes(Router $router): void
    {
        $router->get('settings', [SettingsController::class, 'edit'])->name('settings');
        $router->post('playlists', [SettingsController::class, 'add'])->name('playlists.store');
        $router->post('playlists/{index}/delete', [SettingsController::class, 'destroy'])
            ->whereNumber('index')->name('playlists.destroy');
        $router->post('playlists/{index}/up', [SettingsController::class, 'moveUp'])
            ->whereNumber('index')->name('playlists.up');
    }
}
