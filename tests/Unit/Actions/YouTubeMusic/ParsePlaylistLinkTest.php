<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\YouTubeMusic;

use App\Actions\YouTubeMusic\ParsePlaylistLink;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParsePlaylistLinkTest extends TestCase
{
    /** @return array<string, array{string, string|null}> */
    public static function links(): array
    {
        return [
            'music.youtube.com playlist' => ['https://music.youtube.com/playlist?list=PLabcdefghij123&si=xyz', 'PLabcdefghij123'],
            'watch link with list' => ['https://music.youtube.com/watch?v=abc&list=OLAK5uy_abcdefghijk', 'OLAK5uy_abcdefghijk'],
            'youtu.be share link' => ['https://youtu.be/dQw4w9WgXcQ?list=PLabcdefghij123', 'PLabcdefghij123'],
            'bare id with spaces' => [' PLabcdefghij123 ', 'PLabcdefghij123'],
            'video without playlist' => ['https://music.youtube.com/watch?v=dQw4w9WgXcQ', null],
            'too short to be an id' => ['PLshort', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('links')]
    public function test_it_finds_the_playlist_id(string $input, ?string $expected): void
    {
        $this->assertSame($expected, (new ParsePlaylistLink)->handle($input));
    }
}
