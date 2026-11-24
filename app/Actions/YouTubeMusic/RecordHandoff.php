<?php

declare(strict_types=1);

namespace App\Actions\YouTubeMusic;

use App\Modules\YouTubeMusic\HandoffStore;

/** Remember the song YouTube Premium is playing so the card can carry on with it. */
class RecordHandoff
{
    public function __construct(private readonly HandoffStore $store) {}

    /** @param  float|int  $seconds  playback position; fractions are dropped */
    public function handle(string $videoId, float|int $seconds, ?string $playlistId = null): void
    {
        $this->store->put($videoId, (int) $seconds, $playlistId);
    }
}
