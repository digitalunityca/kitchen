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
<div class="wrap">
  <header>
    <div>
      <p class="eyebrow" id="dateLong"></p>
      <h1 id="greeting"></h1>
    </div>
    <p class="muted">{{ $title }}</p>
  </header>

  <main>
    @foreach ($modules->filter(fn ($m) => $m->placement() === 'grid') as $module)
      <div class="slot" data-module="{{ $module->key() }}" style="grid-column: {{ $module->column() }}; grid-row: {{ $module->row() }};">
        {!! $cards[$module->key()] !!}
      </div>
    @endforeach
  </main>
</div>

{{-- Modules with 'placement' => 'floating': round buttons above the cards, bottom right. --}}
<div class="floating">
  @foreach ($modules->filter(fn ($m) => $m->placement() === 'floating') as $module)
    <div class="floating-slot" data-module="{{ $module->key() }}">{!! $cards[$module->key()] !!}</div>
  @endforeach
</div>

<script>
  // Shared clock: modules listen for "dashboard:tick" instead of running their own timers.
  window.Dashboard = { night: @json($night) };
  (function () {
    var $ = function (id) { return document.getElementById(id); };
    function tick() {
      var now = new Date(), h = now.getHours(), n = Dashboard.night;
      var isNight = n.from > n.to ? (h >= n.from || h < n.to) : (h >= n.from && h < n.to);
      if (/[?&]theme=night/.test(location.search)) isNight = true;
      if (/[?&]theme=day/.test(location.search)) isNight = false;
      document.body.classList.toggle('night', isNight);
      $('greeting').textContent = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
      $('dateLong').textContent = now.toLocaleDateString('en-CA', { weekday: 'long', month: 'long', day: 'numeric' });
      document.dispatchEvent(new CustomEvent('dashboard:tick', { detail: now }));
    }
    document.addEventListener('DOMContentLoaded', function () { tick(); setInterval(tick, 1000); });
  })();
</script>
@stack('scripts')
</body>
</html>
