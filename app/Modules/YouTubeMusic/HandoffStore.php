<?php

namespace App\Modules\YouTubeMusic;

use Illuminate\Support\Facades\Storage;

/**
 * The song last playing in YouTube Premium (youtube.com/tv) on the tablet, so
 * the card can carry on with it after the Home button. Kept in
 * storage/app/private/youtube-handoff.json; older than MAX_AGE counts as gone.
 */
class HandoffStore
{
    private const FILE = 'youtube-handoff.json';

    public const MAX_AGE = 600; // seconds

    /** @return array{v: string, t: int, list: string|null, at: int}|null */
    public function get(): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::FILE)) {
            return null;
        }

        $handoff = json_decode($disk->get(self::FILE), true);
        if (! is_array($handoff) || now()->timestamp - ($handoff['at'] ?? 0) > self::MAX_AGE) {
            return null;
        }

        return $handoff;
    }

    public function put(string $videoId, int $seconds, ?string $list): void
    {
        Storage::disk('local')->put(self::FILE, json_encode([
            'v' => $videoId, 't' => $seconds, 'list' => $list, 'at' => now()->timestamp,
        ]));
    }

    public function clear(): void
    {
        Storage::disk('local')->delete(self::FILE);
    }
}
