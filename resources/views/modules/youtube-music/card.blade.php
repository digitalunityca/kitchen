@php($settingsUrl = route('modules.youtube-music.settings'))
<section class="card ytm" aria-label="YouTube Music" data-volume="{{ $volume }}"
  data-handoff="{{ route('modules.youtube-music.handoff') }}" data-handoff-clear="{{ route('modules.youtube-music.handoff.clear') }}" data-csrf="{{ csrf_token() }}">
  <div class="ytm-art">
    {{-- YouTube's player must stay visible (at least 200x200 px) under its terms, so it doubles as the artwork. --}}
    <div id="ytm-player"></div>
    <div class="ytm-art-empty" id="ytm-art-empty">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </div>
  </div>

  <div class="ytm-side">
    <div class="row">
      <p class="label"><span class="ytm-dot"></span>YouTube Music</p>
      <a class="pill" id="ytm-premium" href="{{ $tvUrl }}" data-tv-ua="{{ $tvUserAgent }}" aria-label="Open YouTube Premium (the YouTube TV app)">Open YouTube Premium ↗</a>
    </div>

    @if (count($playlists))
      <div class="ytm-meta">
        <p class="ytm-title" id="ytm-title">Ready to play</p>
        <p class="ytm-artist" id="ytm-artist">{{ $playlists[0]['name'] }}</p>
      </div>

      <div class="ytm-progress" aria-hidden="true">
        <div class="ytm-bar"><i id="ytm-bar"></i></div>
        <div class="ytm-times"><span id="ytm-now">0:00</span><span id="ytm-dur">0:00</span></div>
      </div>

      <div class="ytm-controls">
        <button class="ytm-btn ytm-small" id="ytm-shuffle" aria-label="Shuffle" aria-pressed="false">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
        </button>
        <button class="ytm-btn" id="ytm-prev" aria-label="Previous">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h2v14H6zM20 5v14L9.5 12z"/></svg>
        </button>
        <button class="ytm-btn ytm-play" id="ytm-play" aria-label="Play">
          <svg class="i-play" width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
          <svg class="i-pause" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/></svg>
        </button>
        <button class="ytm-btn" id="ytm-next" aria-label="Next">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M16 5h2v14h-2zM4 5v14l10.5-7z"/></svg>
        </button>
        <span class="ytm-small"></span>
      </div>

      <div class="ytm-lists" id="ytm-lists" role="tablist">
        @foreach ($playlists as $i => $p)
          <button class="ytm-chip" role="tab" data-index="{{ $i }}" data-list="{{ $p['id'] }}" data-name="{{ $p['name'] }}">{{ $p['name'] }}</button>
        @endforeach
      </div>
    @else
      <div class="ytm-meta">
        <p class="ytm-title">Add your first playlist</p>
        <p class="ytm-artist">On your phone, open <b>{{ $settingsUrl }}</b> and paste a YouTube Music playlist link.</p>
      </div>
      <span></span>
    @endif
  </div>
  <div class="ytm-toast" id="ytm-toast" role="status"></div>
</section>

@push('styles')
<style>
  .ytm { flex-direction: row; gap: 28px; position: relative; }
  .ytm-art { position: relative; flex: none; height: 100%; aspect-ratio: 16 / 9; min-height: 200px; max-width: 52%;
             border-radius: 16px; overflow: hidden; background: var(--fill); }
  .ytm-art iframe, #ytm-player { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
  .ytm-art-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--sub); pointer-events: none; }
  .ytm.has-video .ytm-art-empty { display: none; }
  .ytm-side { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; }
  .ytm-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #FF0033; margin-right: 8px; vertical-align: 1px; }
  .ytm-title { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -.01em; line-height: 1.2;
               display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .ytm-artist { margin: 4px 0 0; font-size: 15px; color: var(--sub); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ytm-artist b { color: var(--text); font-weight: 500; white-space: normal; word-break: break-all; }
  .ytm-bar { height: 4px; border-radius: 2px; background: var(--line); overflow: hidden; }
  .ytm-bar i { display: block; height: 100%; width: 0; background: var(--text); border-radius: 2px; transition: width .9s linear; }
  .ytm-times { display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px; font-weight: 500; color: var(--sub); font-variant-numeric: tabular-nums; }
  .ytm-controls { display: flex; align-items: center; justify-content: space-between; max-width: 340px; }
  .ytm-btn { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: transform .15s ease, background-color .2s; }
  .ytm-btn:active { transform: scale(.92); background: var(--fill); }
  .ytm-small { width: 44px; height: 44px; color: var(--sub); }
  .ytm-small[aria-pressed="true"] { color: var(--accent); background: var(--accent-soft); }
  .ytm-play { width: 60px; height: 60px; background: var(--accent); color: #fff; }
  .ytm-play:active { background: var(--accent); }
  .ytm-play .i-pause, .ytm.playing .ytm-play .i-play { display: none; }
  .ytm.playing .ytm-play .i-pause { display: block; }
  .ytm-lists { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; margin: 0 -2px; padding: 0 2px; }
  .ytm-lists::-webkit-scrollbar { display: none; }
  .ytm-chip { flex: none; font-size: 14px; font-weight: 500; padding: 7px 14px; border-radius: 999px; background: var(--fill); color: var(--text); }
  .ytm-chip[aria-selected="true"] { background: var(--accent); color: #fff; }
  .ytm-toast { position: absolute; left: 50%; bottom: 14px; transform: translate(-50%, 12px); opacity: 0; pointer-events: none;
               background: var(--text); color: var(--card); font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 999px;
               transition: opacity .25s, transform .25s; white-space: nowrap; }
  .ytm-toast.show { opacity: 1; transform: translate(-50%, 0); }
</style>
@endpush

@push('scripts')
<script>
// Fully Kiosk: the YouTube TV app needs a TV user agent, but the IFrame Player
// above won't play under one. So "Open YouTube Premium" switches Fully to the TV
// user agent, and the dashboard switches it back whenever it loads.
// Fully settings: userAgent "0" = default, "custom" = use customUserAgent.
// Song handoff: the button opens YouTube TV at the card's song and position
// (window.ytmNowPlaying), and the script installed below as Fully's "Inject
// JavaScript" reports what YouTube TV plays so the card can carry on afterwards.
window.ytmRestoreUserAgent = (function () {
  var link = document.getElementById('ytm-premium'), tvUa = link.dataset.tvUa;
  if (!window.fully || !tvUa) return false;

  var inject = @json($injectScript);
  try { if (fully.getStringSetting('injectJsCode') !== inject) fully.setStringSetting('injectJsCode', inject); } catch (err) {}

  link.addEventListener('click', function (e) {
    e.preventDefault();
    var href = link.href, now = window.ytmNowPlaying && window.ytmNowPlaying();
    if (now) href = link.href.replace(/#.*$/, '') + '#/watch?v=' + now.v + '&t=' + now.t + (now.list ? '&list=' + now.list : '');
    fully.setStringSetting('customUserAgent', tvUa);
    fully.setStringSetting('userAgent', 'custom');
    location.href = href;
  });

  if (fully.getStringSetting('userAgent') === 'custom' && fully.getStringSetting('customUserAgent') === tvUa) {
    fully.setStringSetting('userAgent', '0');
  }
  // This page itself was loaded with the TV agent: reload once under the normal one.
  // The timestamp guard stops a reload loop if Fully ever ignores the change.
  if (navigator.userAgent === tvUa) {
    var last = 0;
    try { last = parseInt(sessionStorage.getItem('ytm.uaReload'), 10) || 0; sessionStorage.setItem('ytm.uaReload', Date.now()); } catch (err) {}
    if (Date.now() - last > 15000) { location.reload(); return true; }
  }
  return false;
})();
</script>
@endpush

@if (count($playlists))
@push('scripts')
<script>
(function () {
  if (window.ytmRestoreUserAgent) return; // reloading under the normal user agent
  var card = document.querySelector('.ytm'), $ = function (id) { return document.getElementById(id); };
  var chips = [].slice.call(document.querySelectorAll('.ytm-chip'));
  var player = null, ready = false, current = 0, shuffle = false, toastTimer;
  var pending = null, single = false; // handoff: a song to find in the playlist / a lone song outside the playlists

  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }
  var saved = parseInt(store('ytm.playlist'), 10);
  if (saved >= 0 && saved < chips.length) current = saved;
  shuffle = store('ytm.shuffle') === '1';

  function toast(msg) {
    var t = $('ytm-toast'); t.textContent = msg; t.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3500);
  }
  function fmt(s) { s = Math.max(0, Math.floor(s || 0)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }
  function selectChip(i) {
    chips.forEach(function (c, k) { c.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
    chips[i].scrollIntoView({ inline: 'nearest', block: 'nearest' });
  }
  function setShuffle(on) {
    shuffle = on; store('ytm.shuffle', on ? '1' : '0');
    $('ytm-shuffle').setAttribute('aria-pressed', on ? 'true' : 'false');
    if (ready) player.setShuffle(on);
  }
  function showMeta() {
    if (!ready) return;
    var d = player.getVideoData() || {};
    if (d.title) {
      $('ytm-title').textContent = d.title;
      $('ytm-artist').textContent = (d.author || '').replace(/ - Topic$/, '') || chips[current].dataset.name;
      card.classList.add('has-video');
    }
  }

  // For "Open YouTube Premium": the song, position and playlist, then pause here.
  window.ytmNowPlaying = function () {
    if (!ready) return null;
    var d = player.getVideoData() || {};
    if (!d.video_id) return null;
    var now = { v: d.video_id, t: Math.floor(player.getCurrentTime() || 0), list: single ? null : chips[current].dataset.list };
    player.pauseVideo();
    return now;
  };

  // Back from YouTube Premium: carry on with the song it was playing (under 10 minutes old).
  function resumeHandoff() {
    fetch(card.dataset.handoff, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (h) {
        if (!h || !h.v) return;
        fetch(card.dataset.handoffClear, { method: 'POST', headers: { 'X-CSRF-TOKEN': card.dataset.csrf } }).catch(function () {});
        var i = -1;
        chips.forEach(function (c, k) { if (h.list && c.dataset.list === h.list) i = k; });
        if (i < 0) i = current;
        pending = h;
        loadList(i, false); // cue the playlist, then find the song in it (onStateChange)
      })
      .catch(function () {});
  }
  function playPending() {
    var h = pending, idx = (player.getPlaylist() || []).indexOf(h.v);
    pending = null;
    if (idx >= 0) player.loadPlaylist({ listType: 'playlist', list: chips[current].dataset.list, index: idx, startSeconds: h.t });
    else { single = true; player.loadVideoById({ videoId: h.v, startSeconds: h.t }); }
  }

  function loadList(i, autoplay) {
    single = false;
    current = i; store('ytm.playlist', i); selectChip(i);
    var args = { listType: 'playlist', list: chips[i].dataset.list, index: 0 };
    // Without stopVideo() first, YouTube keeps the old playlist's tracks and only swaps the list id.
    player.stopVideo();
    if (autoplay) player.loadPlaylist(args); else player.cuePlaylist(args);
  }

  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player('ytm-player', {
      host: 'https://www.youtube.com',
      playerVars: {
        listType: 'playlist', list: chips[current].dataset.list,
        controls: 0, playsinline: 1, rel: 0, iv_load_policy: 3, disablekb: 1, fs: 0,
        origin: location.origin
      },
      events: {
        onReady: function () {
          ready = true;
          player.setVolume(parseInt(card.dataset.volume, 10) || 60);
          selectChip(current);
          if (shuffle) setShuffle(true);
          resumeHandoff();
        },
        onStateChange: function (e) {
          if (pending && e.data === YT.PlayerState.CUED) { playPending(); return; }
          if (single && e.data === YT.PlayerState.ENDED) { loadList(current, true); return; }
          card.classList.toggle('playing', e.data === YT.PlayerState.PLAYING || e.data === YT.PlayerState.BUFFERING);
          $('ytm-play').setAttribute('aria-label', card.classList.contains('playing') ? 'Pause' : 'Play');
          showMeta();
        },
        onError: function (e) {
          // 101/150: the label blocks playback outside YouTube. 100: removed. Skip to the next song.
          if ((e.data === 101 || e.data === 150 || e.data === 100) && single) { toast('That song only plays on YouTube itself. Back to your playlist.'); loadList(current, true); }
          else if (e.data === 101 || e.data === 150 || e.data === 100) { toast('Skipped a song that only plays on YouTube itself'); player.nextVideo(); }
          else if (e.data === 153) toast('YouTube rejected the player (missing referrer)');
          else if (e.data === 2) toast('That playlist link looks wrong. Check it in settings.');
          else toast('YouTube error ' + e.data);
        }
      }
    });
  };

  $('ytm-play').addEventListener('click', function () {
    if (!ready) return;
    var s = player.getPlayerState();
    if (s === YT.PlayerState.PLAYING || s === YT.PlayerState.BUFFERING) player.pauseVideo(); else player.playVideo();
  });
  $('ytm-next').addEventListener('click', function () { if (ready) player.nextVideo(); });
  $('ytm-prev').addEventListener('click', function () {
    if (!ready) return;
    if (player.getCurrentTime() > 5) player.seekTo(0, true); else player.previousVideo();
  });
  $('ytm-shuffle').addEventListener('click', function () { setShuffle(!shuffle); toast(shuffle ? 'Shuffle on' : 'Shuffle off'); });
  chips.forEach(function (c, i) { c.addEventListener('click', function () { if (ready) loadList(i, true); }); });

  document.addEventListener('dashboard:tick', function () {
    if (!ready || !player.getDuration) return;
    var t = player.getCurrentTime(), d = player.getDuration();
    $('ytm-now').textContent = fmt(t); $('ytm-dur').textContent = fmt(d);
    $('ytm-bar').style.width = d ? (100 * t / d) + '%' : '0';
  });

  var s = document.createElement('script');
  s.src = 'https://www.youtube.com/iframe_api';
  s.onerror = function () { $('ytm-title').textContent = 'Can’t reach YouTube'; $('ytm-artist').textContent = 'Check the tablet’s internet connection.'; };
  document.head.appendChild(s);
})();
</script>
@endpush
@endif
