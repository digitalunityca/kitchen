<?php

declare(strict_types=1);

namespace App\Actions\YouTubeMusic;

use App\Modules\YouTubeMusic\PlaylistStore;

class MovePlaylistUp
{
    public function __construct(private readonly PlaylistStore $store) {}

    /** Swap the playlist with the one above it; the first one stays put. */
    public function handle(int $index): void
    {
        $playlists = $this->store->all();

        if ($index < 1 || ! isset($playlists[$index])) {
            return;
        }

        [$playlists[$index - 1], $playlists[$index]] = [$playlists[$index], $playlists[$index - 1]];
        $this->store->save($playlists);
    }
}
