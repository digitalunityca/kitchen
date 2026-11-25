{{-- Render cards first so their @push('styles') lands in <head>. --}}
@php($cards = $modules->map(fn ($m) => $m->render()->render()))
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{{-- YouTube's player refuses to play (error 153) without a Referer, so send the origin. --}}
<meta name="referrer" content="strict-origin-when-cross-origin">
<title>{{ $title }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@stack('styles')
</head>
<body>
{{-- No header: the day and date are in Fully's top bar, the greeting is on the clock card. --}}
<div class="wrap">
  {{-- Modules with 'placement' => 'ticker': slim strips above the grid. --}}
  @foreach ($modules->filter(fn ($m) => $m->placement() === 'ticker') as $module)
    <div class="ticker-slot" data-module="{{ $module->key() }}">{!! $cards[$module->key()] !!}</div>
  @endforeach

  <main>
    @foreach ($modules->filter(fn ($m) => $m->placement() === 'grid') as $module)
      <div class="slot" data-module="{{ $module->key() }}" style="grid-column: {{ $module->column() }}; grid-row: {{ $module->row() }};">
        {!! $cards[$module->key()] !!}
      </div>
    @endforeach
  </main>
</div>

{{--
  The side menu: a frosted pill on the right holding round buttons. Modules add
  a button either with 'placement' => 'menu' (their whole card is the button) or
  by pushing to the 'menu-items' stack alongside their normal card.
--}}
<nav class="side-menu" aria-label="Menu">
  <button type="button" class="menu-button menu-home" id="menu-settings" aria-label="Settings">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
  </button>
  <span class="menu-divider" aria-hidden="true"></span>
  @stack('menu-items')
  @foreach ($modules->filter(fn ($m) => $m->placement() === 'menu') as $module)
    <div class="menu-slot" data-module="{{ $module->key() }}">{!! $cards[$module->key()] !!}</div>
  @endforeach
</nav>

<script>
  // Shared clock: modules listen for "dashboard:tick" instead of running their own timers.
  window.Dashboard = { night: @json($night) };

  // Breadcrumbs for unexpected reloads: the last taps and Fully setting writes,
  // sent to the hub when the page unloads (storage/logs: "Dashboard page unloaded").
  (function () {
    var recent = [], started = Date.now();
    Dashboard.note = function (what) { recent.push(Math.round((Date.now() - started) / 1000) + 's ' + what); if (recent.length > 12) recent.shift(); };
    document.addEventListener('click', function (e) {
      var t = e.target.closest('[id], [data-index], [class]');
      if (t) Dashboard.note('tap ' + (t.id || (t.className && String(t.className).split(' ')[0]) || t.tagName));
    }, true);
    window.addEventListener('pagehide', function () {
      var card = document.querySelector('.ytm');
      var report = JSON.stringify({ ranSeconds: (Date.now() - started) / 1000, playing: !!(card && card.classList.contains('playing')), visibility: document.visibilityState, recent: recent });
      try { navigator.sendBeacon(@json(route('diagnostics.unload')), report); } catch (e) {}
    });
  })();

  // Fully's action bar title is shared. Modules set a part (with an optional
  // shorter form): "date", then "weather", then "habs". When the whole line is
  // too long, the Habs note goes first, then the weather's rain note.
  (function () {
    var parts = {}, MAX = 96;
    function join(list) { return list.filter(Boolean).join('  ·  '); }
    Dashboard.setTitlePart = function (key, text, shortText) {
      parts[key] = { full: text || '', short: shortText || text || '' };
      var p = function (k, form) { return parts[k] ? parts[k][form] : ''; };
      var candidates = [
        join([p('date', 'full'), p('weather', 'full'), p('habs', 'full')]),
        join([p('date', 'full'), p('weather', 'full')]),
        join([p('date', 'full'), p('weather', 'short')]),
        join([p('date', 'full')]),
      ];
      var title = candidates.filter(function (c) { return c.length <= MAX; })[0] || candidates[candidates.length - 1];
      Dashboard.title = title;
      if (!window.fully || typeof fully.setStringSetting !== 'function') return;
      try { if (fully.getStringSetting('actionBarTitle') !== title) { fully.setStringSetting('actionBarTitle', title); Dashboard.note('set actionBarTitle'); } } catch (e) {}
    };
  })();
  (function () {
    var lastDate = '';
    function tick() {
      var now = new Date(), h = now.getHours(), n = Dashboard.night;
      var isNight = n.from > n.to ? (h >= n.from || h < n.to) : (h >= n.from && h < n.to);
      if (/[?&]theme=night/.test(location.search)) isNight = true;
      if (/[?&]theme=day/.test(location.search)) isNight = false;
      document.body.classList.toggle('night', isNight);
      var date = now.toLocaleDateString('en-CA', { weekday: 'long', month: 'long', day: 'numeric' });
      if (date !== lastDate) { lastDate = date; Dashboard.setTitlePart('date', date); } // also rolls over at midnight
      document.dispatchEvent(new CustomEvent('dashboard:tick', { detail: now }));
    }
    document.addEventListener('DOMContentLoaded', function () { tick(); setInterval(tick, 1000); });
  })();
</script>
{{-- Settings sheet: the ⚙ opens /settings (PIN first) in a frame over the dashboard, so music keeps playing. --}}
<div class="settings-overlay" id="settings-overlay" hidden role="dialog" aria-modal="true" aria-label="Settings">
  <div class="settings-sheet">
    <button type="button" class="settings-close" id="settings-close" aria-label="Close settings">✕</button>
    <iframe id="settings-frame" title="Settings"></iframe>
  </div>
</div>
<script>
  (function () {
    var overlay = document.getElementById('settings-overlay'), frame = document.getElementById('settings-frame');
    function close() {
      overlay.hidden = true; frame.src = 'about:blank';
      document.dispatchEvent(new CustomEvent('dashboard:home')); // also tidies open sheets on the dashboard
    }
    document.getElementById('menu-settings').addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('dashboard:home'));
      frame.src = @json(route('settings', [], false)); overlay.hidden = false;
    });
    document.getElementById('settings-close').addEventListener('click', close);
    window.addEventListener('message', function (e) { if (e.origin === location.origin && e.data === 'settings:close') close(); });
  })();
</script>
@stack('scripts')
</body>
</html>
