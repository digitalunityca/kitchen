<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Music playlists</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
  body { overflow: auto; -webkit-user-select: auto; user-select: auto; }
  @media (prefers-color-scheme: dark) { body { --bg:#000; --card:#1C1C1E; --text:#F5F5F7; --sub:#A1A1A6; --line:#2C2C2E; --fill:#2C2C2E; --accent:#0A84FF; --shadow:none; } }
  .page { max-width: 640px; margin: 0 auto; padding: 40px 16px 64px; display: flex; flex-direction: column; gap: 24px; }
  .card { padding: 24px; gap: 16px; justify-content: flex-start; }
  h1 { font-size: 30px; }
  p { margin: 0; line-height: 1.5; }
  label { font-size: 13px; font-weight: 600; color: var(--sub); display: block; margin-bottom: 6px; }
  input { width: 100%; font: inherit; font-size: 17px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 12px 14px; }
  input:focus { outline: none; border-color: var(--accent); }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 12px 18px; font-weight: 600; font-size: 16px; }
  .err { color: #FF3B30; font-size: 14px; margin-top: 6px; }
  .ok { color: #34C759; font-weight: 500; }
  ul { list-style: none; margin: 0; padding: 0; }
  li { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
  li:first-child { border-top: 0; }
  li .name { flex: 1; min-width: 0; }
  li small { display: block; color: var(--sub); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .ghost { color: var(--sub); font-size: 14px; padding: 6px 10px; border-radius: 8px; background: var(--fill); }
  .danger { color: #FF3B30; }
  a { color: var(--accent); }
</style>
</head>
<body>
<div class="page">
  <div>
    <p class="eyebrow">YouTube Music</p>
    <h1>Kitchen playlists</h1>
  </div>

  @if (session('status')) <p class="ok">{{ session('status') }}</p> @endif

  <form class="card" method="post" action="{{ route('modules.youtube-music.playlists.store') }}">
    @csrf
    <p>In YouTube Music, open a playlist or album, tap <b>Share › Copy link</b>, and paste it here. The kitchen screen picks it up on its next reload.</p>
    <div>
      <label for="link">Playlist link</label>
      <input id="link" name="link" value="{{ old('link') }}" placeholder="https://music.youtube.com/playlist?list=…" required autocomplete="off">
      @error('link') <p class="err">{{ $message }}</p> @enderror
    </div>
    <div>
      <label for="name">Name on the screen</label>
      <input id="name" name="name" value="{{ old('name') }}" placeholder="Dinner jazz" maxlength="60">
    </div>
    <div><button class="btn" type="submit">Add playlist</button></div>
  </form>

  <div class="card">
    <p class="label">On the screen ({{ count($playlists) }})</p>
    @if (count($playlists))
      <ul>
        @foreach ($playlists as $i => $p)
          <li>
            <div class="name">{{ $p['name'] }}<small>{{ $p['id'] }}</small></div>
            @if ($i > 0)
              <form method="post" action="{{ route('modules.youtube-music.playlists.up', $i) }}">@csrf<button class="ghost" aria-label="Move up">↑</button></form>
            @endif
            <form method="post" action="{{ route('modules.youtube-music.playlists.destroy', $i) }}">@csrf<button class="ghost danger">Remove</button></form>
          </li>
        @endforeach
      </ul>
    @else
      <p style="color:var(--sub)">No playlists yet.</p>
    @endif
  </div>

  <p style="color:var(--sub);font-size:14px">Private playlists won’t play on the kitchen screen. Set them to Public or Unlisted. Your personal mixes (Supermix, My Mix) are made for your account and usually can’t be embedded. Save one as a playlist first.</p>
  <p><a href="{{ route('dashboard') }}">← Back to the dashboard</a></p>
</div>
</body>
</html>
