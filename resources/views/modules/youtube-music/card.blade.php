{{-- The music card: a remote for the YouTube Music app (song, ⏮ ⏯ ⏭, open the app). MacroDroid on the tablet links them (MusicAppController). --}}
<section class="card ytm idle" id="ytm" aria-label="YouTube Music" data-package="{{ $appPackage }}" data-csrf="{{ csrf_token() }}"
  data-now="{{ route('modules.youtube-music.app.now') }}" data-control="{{ route('modules.youtube-music.app.control') }}">
  <div class="ytm-art" aria-hidden="true">
    <svg width="84" height="84" viewBox="0 0 48 48"><circle cx="24" cy="24" r="22" fill="#FF0033"/><circle cx="24" cy="24" r="12.5" fill="none" stroke="#fff" stroke-width="2.4"/><path d="M21 18.5v11l9-5.5z" fill="#fff"/></svg>
    <span class="ytm-eq"><i></i><i></i><i></i></span>
  </div>

  <div class="ytm-side">
    <p class="label">YouTube Music</p>
    <div class="ytm-meta">
      <p class="ytm-title" id="ytm-title">Nothing playing</p>
      <p class="ytm-artist" id="ytm-artist">Open YouTube Music to start a song</p>
    </div>

    <div class="ytm-controls" id="ytm-controls" hidden>
      <button type="button" data-app-action="previous" aria-label="Previous"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h2v14H6zM20 5v14L9 12z"/></svg></button>
      <button type="button" data-app-action="play-pause" class="ytm-main" aria-label="Play or pause"><svg class="i-play" width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg><svg class="i-pause" width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg></button>
      <button type="button" data-app-action="next" aria-label="Next"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 5h2v14h-2zM4 5v14l11-7z"/></svg></button>
    </div>

    <button type="button" class="ytm-open" id="ytm-open">Open YouTube Music ↗</button>
    <p class="ytm-note" id="ytm-note" role="status" hidden></p>
  </div>
</section>

@push('styles')
<style>
  .ytm { flex-direction: row; align-items: stretch; gap: 28px; }
  .ytm-art { position: relative; flex: none; width: 42%; max-width: 300px; aspect-ratio: 1; align-self: center; border-radius: 22px; background: linear-gradient(145deg, #fff1f3, #ffe1e6); display: flex; align-items: center; justify-content: center; }
  body.night .ytm-art { background: linear-gradient(145deg, #2a1418, #1c0e11); }
  .ytm-eq { position: absolute; bottom: 18px; display: flex; gap: 4px; align-items: flex-end; height: 18px; opacity: 0; transition: opacity .3s ease; }
  .ytm-eq i { width: 4px; height: 6px; border-radius: 2px; background: #FF0033; }
  .ytm.playing .ytm-eq { opacity: 1; }
  .ytm.playing .ytm-eq i { animation: ytm-eq 1s ease-in-out infinite; }
  .ytm.playing .ytm-eq i:nth-child(2) { animation-delay: .2s; }
  .ytm.playing .ytm-eq i:nth-child(3) { animation-delay: .4s; }
  @keyframes ytm-eq { 50% { height: 18px; } }
  .ytm-side { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; gap: 14px; }
  .ytm-meta { min-width: 0; }
  .ytm-title { margin: 0; font-size: 26px; font-weight: 600; letter-spacing: -.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ytm-artist { margin: 4px 0 0; font-size: 17px; color: var(--sub); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ytm.idle .ytm-title { color: var(--sub); font-weight: 500; }
  .ytm-controls { display: flex; align-items: center; gap: 18px; }
  .ytm-controls[hidden], .ytm-note[hidden] { display: none; }
  .ytm-controls button { width: 56px; height: 56px; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: var(--text); }
  .ytm-controls .ytm-main { width: 72px; height: 72px; background: #FF0033; color: #fff; box-shadow: 0 6px 18px rgba(255, 0, 51, .25); }
  .ytm-controls button:active, .ytm-open:active { transform: scale(.95); }
  .ytm .i-pause, .ytm.playing .i-play { display: none; }
  .ytm.playing .i-pause { display: block; }
  .ytm-open { align-self: flex-start; min-height: 44px; padding: 0 18px; border-radius: 999px; background: var(--fill); color: #D70030; font-size: 16px; font-weight: 600; }
  .ytm.idle .ytm-open { background: #FF0033; color: #fff; }
  body.night .ytm-open { color: #FF4D6D; }
  body.night .ytm.idle .ytm-open { color: #fff; }
  .ytm-note { margin: 0; font-size: 14px; color: var(--sub); }
  @media (prefers-reduced-motion: reduce) { .ytm.playing .ytm-eq i { animation: none; height: 12px; } }
</style>
@endpush

@push('scripts')
<script>
// The YouTube Music remote. The song comes from MacroDroid through the hub (checked
// every 5 s while the dashboard is visible); the buttons queue a press that MacroDroid
// fetches. "Open YouTube Music" launches the app through Fully; Back returns here.
(function () {
  var card = document.getElementById('ytm'), $ = function (id) { return document.getElementById(id); };
  var state = { active: false, playing: false }, claimed = false;

  // Leftovers from the old YouTube TV mode: Fully's injected script and TV user agent.
  if (window.fully) {
    try {
      if ((fully.getStringSetting('injectJsCode') || '').indexOf('__homeHubHandoff') !== -1) fully.setStringSetting('injectJsCode', '');
      if (fully.getStringSetting('userAgent') === 'custom' && /SMART-TV/.test(fully.getStringSetting('customUserAgent') || '')) fully.setStringSetting('userAgent', '0');
    } catch (err) {}
  }

  function render(d) {
    state = d;
    card.classList.toggle('idle', !d.active);
    card.classList.toggle('playing', !!(d.active && d.playing));
    $('ytm-title').textContent = d.active ? d.title : 'Nothing playing';
    $('ytm-artist').textContent = d.active ? (d.artist || '') : 'Open YouTube Music to start a song';
    $('ytm-controls').hidden = !(d.active && d.controls);
  }
  function poll() {
    if (document.hidden) return;
    fetch(card.dataset.now, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { if (d) render(d); }).catch(function () {});
  }
  function press(action) {
    var form = new FormData(); form.append('action', action);
    if (action === 'play-pause') card.classList.toggle('playing');
    return fetch(card.dataset.control, { method: 'POST', body: form, headers: { 'X-CSRF-TOKEN': card.dataset.csrf, Accept: 'application/json' } })
      .then(function () { setTimeout(poll, 2500); }).catch(function () {});
  }
  card.addEventListener('click', function (e) {
    var b = e.target.closest('[data-app-action]');
    if (b) press(b.dataset.appAction);
  });
  $('ytm-open').addEventListener('click', function () {
    try { if (window.fully && typeof fully.startApplication === 'function') { fully.startApplication(card.dataset.package); return; } } catch (err) {}
    var note = $('ytm-note'); note.hidden = false; note.textContent = 'Open YouTube Music from the tablet’s home screen.';
  });

  // The voice chat needs the speaker: pause the app, and resume afterwards if it was playing.
  document.addEventListener('dashboard:audio-claim', function () {
    claimed = !!(state.active && state.playing && state.controls);
    if (claimed) press('play-pause');
  });
  document.addEventListener('dashboard:audio-release', function () {
    if (claimed) press('play-pause');
    claimed = false;
  });

  setInterval(poll, 5000); poll();
  document.addEventListener('visibilitychange', poll);
})();
</script>
@endpush
