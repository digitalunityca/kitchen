<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\YouTubeMusic;

use App\Actions\YouTubeMusic\AddPlaylist;
use App\Actions\YouTubeMusic\MovePlaylistUp;
use App\Actions\YouTubeMusic\RemovePlaylist;
use App\Modules\YouTubeMusic\PlaylistStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlaylistActionsTest extends TestCase
{
    private PlaylistStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new PlaylistStore(Storage::fake('local'));
    }

    /** @param  list<string>  $names */
    private function seedPlaylists(array $names): void
    {
        $this->store->save(array_map(fn (string $name): array => ['name' => $name, 'id' => 'PL'.str_repeat($name, 10)], $names));
    }

    /** @return list<string> */
    private function names(): array
    {
        return array_column($this->store->all(), 'name');
    }

    public function test_add_appends_and_names_unnamed_playlists_by_position(): void
    {
        $add = new AddPlaylist($this->store);

        $add->handle('PLaaaaaaaaaa', 'Dinner jazz');
        $add->handle('PLbbbbbbbbbb');

        $this->assertSame(['Dinner jazz', 'Playlist 2'], $this->names());
    }

    public function test_adding_a_saved_playlist_again_moves_it_to_the_end(): void
    {
        $add = new AddPlaylist($this->store);
        $add->handle('PLaaaaaaaaaa', 'A');
        $add->handle('PLbbbbbbbbbb', 'B');

        $add->handle('PLaaaaaaaaaa', 'A again');

        $this->assertSame(['B', 'A again'], $this->names());
    }

    public function test_remove_drops_one_playlist_and_ignores_unknown_positions(): void
    {
        $this->seedPlaylists(['A', 'B', 'C']);
        $remove = new RemovePlaylist($this->store);

        $remove->handle(1);
        $remove->handle(9);

        $this->assertSame(['A', 'C'], $this->names());
    }

    public function test_move_up_swaps_with_the_one_above_and_leaves_the_first_alone(): void
    {
        $this->seedPlaylists(['A', 'B', 'C']);
        $moveUp = new MovePlaylistUp($this->store);

        $moveUp->handle(2);
        $this->assertSame(['A', 'C', 'B'], $this->names());

        $moveUp->handle(0);
        $moveUp->handle(7);
        $this->assertSame(['A', 'C', 'B'], $this->names());
    }

    public function test_store_uses_config_defaults_until_the_first_save_and_survives_a_corrupt_file(): void
    {
        $disk = Storage::fake('local');
        $store = new PlaylistStore($disk, [['name' => 'Default', 'id' => 'PLdefault000']]);
        $this->assertSame('Default', $store->all()[0]['name']);

        $disk->put('youtube-music.json', '{not json');
        $this->assertSame([], $store->all());
    }
}
