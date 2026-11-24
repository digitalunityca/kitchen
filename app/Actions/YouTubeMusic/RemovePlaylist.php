<?php

declare(strict_types=1);

namespace App\Actions\YouTubeMusic;

use App\Modules\YouTubeMusic\PlaylistStore;

class RemovePlaylist
{
    public function __construct(private readonly PlaylistStore $store) {}

    /** Remove the playlist at this position; an unknown position changes nothing. */
    public function handle(int $index): void
    {
        $playlists = $this->store->all();

        if (! isset($playlists[$index])) {
            return;
        }

        unset($playlists[$index]);
        $this->store->save($playlists);
    }
}
