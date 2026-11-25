<?php

namespace App\Modules\YouTubeMusic;

use App\Dashboard\Module;
use App\Dashboard\ModuleRegistry;
use App\Http\Middleware\RequireSettingsPin;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

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
            'playlists' => app(PlaylistStore::class)->all(),
            'volume' => (int) $this->config('volume', 60),
            'tvUrl' => (string) $this->config('tv_url', 'https://www.youtube.com/tv'),
            'tvUserAgent' => (string) $this->config('tv_user_agent', ''),
            'injectScript' => $this->injectScript(),
        ];
    }

    public function register(Container $app): void
    {
        $app->bind(PlaylistStore::class, fn (Container $app): PlaylistStore => new PlaylistStore(
            Storage::disk('local'),
            (array) $app->make(ModuleRegistry::class)->get($this->key())?->config('playlists', []),
        ));
        $app->bind(HandoffStore::class, fn (): HandoffStore => new HandoffStore(Storage::disk('local')));
    }

    public function routes(Router $router): void
    {
        // Managing playlists needs the settings PIN.
        $router->middleware(RequireSettingsPin::class)->group(function (Router $router): void {
            $router->get('settings', [SettingsController::class, 'edit'])->name('settings');
            $router->post('playlists', [SettingsController::class, 'add'])->name('playlists.store');
            $router->post('playlists/{index}/delete', [SettingsController::class, 'destroy'])
                ->whereNumber('index')->name('playlists.destroy');
            $router->post('playlists/{index}/up', [SettingsController::class, 'moveUp'])
                ->whereNumber('index')->name('playlists.up');
        });

        // Song handoff from YouTube Premium (see HandoffController).
        $router->post('handoff', [HandoffController::class, 'store'])
            ->middleware('throttle:30,1')->name('handoff.store');
        $router->get('handoff', [HandoffController::class, 'show'])->name('handoff');
        $router->post('handoff/clear', [HandoffController::class, 'clear'])->name('handoff.clear');
    }

    /** The script Fully injects into youtube.com, with this install's handoff URL. */
    public function injectScript(): string
    {
        return str_replace('__HANDOFF_URL__', route('modules.youtube-music.handoff.store'),
            (string) file_get_contents(resource_path('inject/youtube-handoff.js')));
    }
}
