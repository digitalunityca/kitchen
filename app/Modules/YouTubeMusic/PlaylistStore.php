<?php

namespace App\Modules\YouTubeMusic;

use Illuminate\Support\Facades\Storage;

/** Saved playlists, kept in storage/app/youtube-music.json so no database is needed. */
class PlaylistStore
{
    private const FILE = 'youtube-music.json';

    /** @param array<int, array{name: string, id: string}> $defaults used until the first save */
    public function __construct(private array $defaults = []) {}

    /** @return array<int, array{name: string, id: string}> */
    public function all(): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::FILE)) {
            return array_values($this->defaults);
        }

        return array_values(json_decode($disk->get(self::FILE), true) ?: []);
    }

    public function save(array $playlists): void
    {
        Storage::disk('local')->put(self::FILE, json_encode(array_values($playlists), JSON_PRETTY_PRINT));
    }

    public function add(string $name, string $id): void
    {
        $all = array_filter($this->all(), fn ($p) => $p['id'] !== $id);
        $all[] = ['name' => $name, 'id' => $id];
        $this->save($all);
    }

    public function remove(int $index): void
    {
        $all = $this->all();
        unset($all[$index]);
        $this->save($all);
    }

    public function moveUp(int $index): void
    {
        $all = $this->all();
        if ($index > 0 && isset($all[$index])) {
            [$all[$index - 1], $all[$index]] = [$all[$index], $all[$index - 1]];
            $this->save($all);
        }
    }

    /**
     * Pull a playlist id out of anything the user pastes: a music.youtube.com or
     * youtube.com link with ?list=, a youtu.be link, or a bare id.
     */
    public static function parseId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/[?&]list=([A-Za-z0-9_-]{10,})/', $input, $m)) {
            return $m[1];
        }
        if (preg_match('/^[A-Za-z0-9_-]{10,}$/', $input)) {
            return $input;
        }

        return null;
    }
}
