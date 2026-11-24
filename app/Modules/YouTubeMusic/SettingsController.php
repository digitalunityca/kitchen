<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic;

use App\Actions\YouTubeMusic\AddPlaylist;
use App\Actions\YouTubeMusic\MovePlaylistUp;
use App\Actions\YouTubeMusic\RemovePlaylist;
use App\Http\Controllers\Controller;
use App\Modules\YouTubeMusic\Requests\AddPlaylistRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function edit(PlaylistStore $store): View
    {
        return view('modules.youtube-music.settings', ['playlists' => $store->all()]);
    }

    public function add(AddPlaylistRequest $request, AddPlaylist $addPlaylist): RedirectResponse
    {
        $addPlaylist->handle((string) $request->playlistId(), $request->validated('name'));

        return redirect()->route('modules.youtube-music.settings')->with('status', 'Playlist added.');
    }

    public function destroy(int $index, RemovePlaylist $removePlaylist): RedirectResponse
    {
        $removePlaylist->handle($index);

        return redirect()->route('modules.youtube-music.settings')->with('status', 'Playlist removed.');
    }

    public function moveUp(int $index, MovePlaylistUp $movePlaylistUp): RedirectResponse
    {
        $movePlaylistUp->handle($index);

        return redirect()->route('modules.youtube-music.settings');
    }
}
