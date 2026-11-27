{{-- Volume slider for the side menu: drag or tap anywhere in the column; the speaker mutes. --}}
<div class="vol" id="vol" data-default="{{ $default }}">
  <div class="vol-slider" id="vol-slider" role="slider" aria-label="{{ __('Volume') }}" aria-orientation="vertical" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $default }}" tabindex="0">
    <div class="vol-track"><i id="vol-fill"></i></div>
    <span class="vol-thumb" aria-hidden="true"></span>
  </div>
  <button type="button" class="vol-mute" id="vol-mute" aria-label="{{ __('Mute') }}">
    <svg class="i-on" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H3v6h3l5 4z" fill="currentColor"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
    <svg class="i-off" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H3v6h3l5 4z" fill="currentColor"/><path d="m16 9 6 6M22 9l-6 6"/></svg>
  </button>
</div>

@push('styles')
<style>
  .vol { display: flex; flex-direction: column; align-items: center; gap: 4px; width: 64px; color: var(--text); }
  /* The whole 64 px column is the touch target; only the track inside it is drawn. */
  .vol-slider { position: relative; width: 64px; height: 168px; display: flex; justify-content: center; padding: 10px 0; touch-action: none; cursor: pointer; }
  .vol-track { position: relative; width: 12px; height: 100%; border-radius: 6px; background: rgba(120,120,128,.24); overflow: hidden; transition: width .3s ease; }
  .vol-track i { position: absolute; left: 0; right: 0; bottom: 0; height: 50%; background: var(--accent); transition: height .06s linear; }
  /* The blue knob sits on top of the filled part (--level is 0–1). */
  .vol-thumb {
    position: absolute; left: 50%; width: 22px; height: 22px; margin-left: -11px; border-radius: 999px;
    bottom: calc(10px + (100% - 20px) * var(--level, .5) - 11px);
    background: var(--accent); border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,.25);
    transition: bottom .06s linear, transform .3s ease; pointer-events: none;
  }
  body.night .vol-thumb { border-color: #2C2C2E; }
  .vol.dim .vol-thumb { transform: scale(.6); }
  .vol-mute { width: 48px; height: 48px; border-radius: 999px; display: flex; align-items: center; justify-content: center; transition: opacity .3s ease; }
  .vol-mute .i-off, .vol.muted .vol-mute .i-on { display: none; }
  .vol.muted .vol-mute .i-off { display: block; }
  .vol.muted .vol-track i { opacity: .3; }
  /* Idle for 4 s: a slim line, still draggable. */
  .vol.dim .vol-track { width: 4px; }
  .vol.dim .vol-mute { opacity: .45; }
  @media (prefers-reduced-motion: reduce) { .vol-track, .vol-track i, .vol-mute, .vol-thumb { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
  var root = document.getElementById('vol'), slider = document.getElementById('vol-slider'), fill = document.getElementById('vol-fill');
  var system = !!(window.fully && typeof fully.setAudioVolume === 'function');
  var level = 50, beforeMute = 50, dimTimer = 0, dragging = false;

  function store(v) { try { if (v === undefined) return localStorage.getItem('dashboard-volume'); localStorage.setItem('dashboard-volume', v); } catch (e) { return null; } }
  function read() {
    if (system) { try { var v = parseInt(fully.getAudioVolume(3), 10); if (!isNaN(v)) return v; } catch (e) {} }
    var saved = parseInt(store(), 10);
    return isNaN(saved) ? (parseInt(root.dataset.default, 10) || 50) : saved;
  }
  function show(v) {
    fill.style.height = v + '%';
    slider.style.setProperty('--level', String(v / 100));
    slider.setAttribute('aria-valuenow', String(v));
    root.classList.toggle('muted', v === 0);
    document.getElementById('vol-mute').setAttribute('aria-label', v === 0 ? __('Unmute') : __('Mute'));
  }
  /** Set the loudness: the tablet's media volume through Fully, else the YouTube player. */
  function apply(v) {
    level = Math.max(0, Math.min(100, Math.round(v)));
    var viaFully = false;
    if (system) { try { fully.setAudioVolume(level, 3); viaFully = true; } catch (e) {} }
    Dashboard.volume = { level: level, system: viaFully };
    document.dispatchEvent(new CustomEvent('dashboard:volume', { detail: Dashboard.volume }));
    if (level > 0) store(String(level));
    show(level);
  }
  function wake() {
    root.classList.remove('dim'); clearTimeout(dimTimer);
    dimTimer = setTimeout(function () { if (!dragging) root.classList.add('dim'); }, 4000);
  }
  function fromPointer(e) {
    var r = slider.getBoundingClientRect(), pad = 10;
    apply((1 - (e.clientY - r.top - pad) / (r.height - 2 * pad)) * 100);
  }

  level = read(); beforeMute = level || 50; show(level);
  Dashboard.volume = { level: level, system: system };
  wake();

  slider.addEventListener('pointerdown', function (e) {
    e.preventDefault(); dragging = true; wake();
    try { slider.setPointerCapture(e.pointerId); } catch (err) {}
    fromPointer(e);
  });
  slider.addEventListener('pointermove', function (e) { if (dragging) { fromPointer(e); wake(); } });
  ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (t) {
    slider.addEventListener(t, function () { dragging = false; wake(); });
  });
  document.getElementById('vol-mute').addEventListener('click', function () {
    wake();
    if (level > 0) { beforeMute = level; apply(0); } else { apply(beforeMute || 50); }
  });
  slider.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowUp') { apply(level + 5); wake(); }
    if (e.key === 'ArrowDown') { apply(level - 5); wake(); }
  });
  // Pick up changes made with the tablet's hardware buttons.
  if (system) setInterval(function () { if (!dragging) { var v = read(); if (v !== level) { level = v; show(v); } } }, 3000);
})();
</script>
@endpush
