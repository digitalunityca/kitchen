<?php

namespace App\Modules\YouTubeMusic;

use App\Dashboard\Module;
use Illuminate\Routing\Router;

/**
 * The music card: a remote for the YouTube Music app on the tablet (installed with
 * Google Play). It shows the song the app is playing and has ⏮ ⏯ ⏭ and an "Open
 * YouTube Music" button. The link goes through MacroDroid on the tablet: it reports
 * the song and fetches button presses (see MusicAppController).
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

    /** @return array{appPackage: string} */
    public function data(): array
    {
        return [
            'appPackage' => (string) $this->config('app_package', 'com.google.android.apps.youtube.music'),
        ];
    }

    public function routes(Router $router): void
    {
        $router->post('app/now-playing', [MusicAppController::class, 'store'])->middleware('throttle:60,1')->name('app.store');
        $router->get('app/now', [MusicAppController::class, 'show'])->middleware('throttle:60,1')->name('app.now');
        $router->post('app/control', [MusicAppController::class, 'control'])->middleware('throttle:30,1')->name('app.control');
        $router->get('app/command', [MusicAppController::class, 'command'])->middleware('throttle:90,1')->name('app.command');
    }
}
