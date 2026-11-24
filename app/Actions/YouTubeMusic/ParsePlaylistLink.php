<?php

declare(strict_types=1);

namespace App\Actions\YouTubeMusic;

/**
 * Pull a playlist id out of anything the user pastes: a music.youtube.com or
 * youtube.com link with ?list=, a youtu.be link, or a bare id.
 */
class ParsePlaylistLink
{
    public function handle(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('/[?&]list=([A-Za-z0-9_-]{10,})/', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9_-]{10,}$/', $input)) {
            return $input;
        }

        return null;
    }
}
