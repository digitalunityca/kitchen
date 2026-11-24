<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic;

use Illuminate\Contracts\Filesystem\Filesystem;

/** Saved playlists, kept in storage/app/youtube-music.json so no database is needed. */
class PlaylistStore
{
    private const FILE = 'youtube-music.json';

    /** @param  list<array{name: string, id: string}>  $defaults  used until the first save */
    public function __construct(
        private readonly Filesystem $disk,
        private readonly array $defaults = [],
    ) {}

    /** @return list<array{name: string, id: string}> */
    public function all(): array
    {
        if (! $this->disk->exists(self::FILE)) {
            return array_values($this->defaults);
        }

        $playlists = json_decode((string) $this->disk->get(self::FILE), true);

        return is_array($playlists) ? array_values($playlists) : [];
    }

    /** @param  array<int, array{name: string, id: string}>  $playlists */
    public function save(array $playlists): void
    {
        $this->disk->put(self::FILE, (string) json_encode(array_values($playlists), JSON_PRETTY_PRINT));
    }
}
