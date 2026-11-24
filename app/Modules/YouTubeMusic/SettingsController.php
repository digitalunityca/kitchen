<?php

namespace App\Modules\YouTubeMusic;

use App\Dashboard\ModuleRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private ModuleRegistry $modules) {}

    private function store(): PlaylistStore
    {
        return $this->modules->get('youtube-music')->store();
    }

    public function edit()
    {
        return view('modules.youtube-music.settings', ['playlists' => $this->store()->all()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'link' => ['required', 'string', 'max:500'],
            'name' => ['nullable', 'string', 'max:60'],
        ]);

        $id = PlaylistStore::parseId($data['link']);
        if (! $id) {
            return back()->withInput()->withErrors(['link' => 'That link has no playlist in it. Open the playlist, tap Share, Copy link, and paste that.']);
        }

        $this->store()->add($data['name'] ?: 'Playlist '.(count($this->store()->all()) + 1), $id);

        return redirect()->route('modules.youtube-music.settings')->with('status', 'Playlist added.');
    }

    public function destroy(int $index)
    {
        $this->store()->remove($index);

        return redirect()->route('modules.youtube-music.settings')->with('status', 'Playlist removed.');
    }

    public function moveUp(int $index)
    {
        $this->store()->moveUp($index);

        return redirect()->route('modules.youtube-music.settings');
    }
}
