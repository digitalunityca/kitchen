<?php

declare(strict_types=1);

namespace App\Actions\YouTubeMusic;

use App\Modules\YouTubeMusic\PlaylistStore;

/** Save a playlist at the end of the list. Adding one that is already saved moves it to the end. */
class AddPlaylist
{
    public function __construct(private readonly PlaylistStore $store) {}

    /** A blank name becomes "Playlist N". */
    public function handle(string $playlistId, ?string $name = null): void
    {
        $playlists = $this->store->all();
        $name = $name ?: 'Playlist '.(count($playlists) + 1);

        $playlists = array_filter($playlists, fn (array $playlist): bool => $playlist['id'] !== $playlistId);
        $playlists[] = ['name' => $name, 'id' => $playlistId];

        $this->store->save($playlists);
    }
}
