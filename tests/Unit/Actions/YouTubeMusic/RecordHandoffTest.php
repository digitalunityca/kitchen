<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\YouTubeMusic;

use App\Actions\YouTubeMusic\RecordHandoff;
use App\Modules\YouTubeMusic\HandoffStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordHandoffTest extends TestCase
{
    public function test_it_stores_the_song_with_whole_seconds(): void
    {
        $store = new HandoffStore(Storage::fake('local'));

        (new RecordHandoff($store))->handle('fPO76Jlnz6c', 83.6, 'PLabcdefghij123');

        $this->assertSame(
            ['v' => 'fPO76Jlnz6c', 't' => 83, 'list' => 'PLabcdefghij123', 'at' => now()->timestamp],
            $store->get(),
        );
    }

    public function test_a_corrupt_handoff_file_counts_as_none(): void
    {
        $disk = Storage::fake('local');
        $disk->put('youtube-handoff.json', 'garbage');

        $this->assertNull((new HandoffStore($disk))->get());
    }
}
