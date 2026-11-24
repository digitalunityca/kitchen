@php($deg = fn ($t) => \App\Modules\Weather\WeatherFormat::deg($t))
<section class="card" aria-label="Weather">
  <div class="row">
    <p class="label">Outside · {{ $city }}</p>
    <span class="pill" id="weather-sample"{{ $weather['real'] ? ' hidden' : '' }}>Sample data</span>
  </div>
  <div style="display:flex;align-items:center;gap:20px">
    <span id="weather-icon" style="display:flex">@include('modules.weather.icon', ['icon' => $weather['icon'], 'size' => 72])</span>
    <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
      <span class="big" style="font-size:72px" id="weather-temp">{{ $deg($weather['temp']) }}</span>
      <span style="font-size:17px;color:var(--sub)" id="weather-line">{{ $weather['label'] }} · H {{ $deg($weather['high']) }} L {{ $deg($weather['low']) }}</span>
    </div>
  </div>
  <div class="hours" id="weather-hours">
    @foreach ($weather['hours'] ?? [] as $hour)
      <div><span>{{ $hour['label'] }}</span><span>{{ $deg($hour['temp']) }}</span></div>
    @endforeach
  </div>
</section>

@push('scripts')
<script>
  // Refreshes from /modules/weather/now every 15 minutes. In Fully Kiosk the
  // one-line summary goes in the action bar title (only for real data).
  (function () {
    var $ = function (id) { return document.getElementById(id); };
    var url = @json(route('modules.weather.now'));
    var weather = @json($weather), lastHour = -1;
    function deg(t) { return (t < 0 ? '−' + Math.abs(t) : t) + '°'; }
    function hourLabel(h) { return (h % 12 || 12) + (h < 12 ? ' AM' : ' PM'); }

    function renderHours(now) {
      var hours = weather.hours;
      if (!hours) { // sample data: the next 4 hours from the tablet's clock
        hours = [3, 2, 1, 0].map(function (t, i) { return { label: hourLabel((now.getHours() + i + 1) % 24), temp: weather.temp - 3 + t }; });
      }
      $('weather-hours').innerHTML = hours.map(function (h) {
        return '<div><span>' + h.label + '</span><span>' + deg(h.temp) + '</span></div>';
      }).join('');
    }

    // The summary goes first in Fully's action bar title (Dashboard.setTitlePart).
    function setTitle() {
      if (window.Dashboard && Dashboard.setTitlePart) Dashboard.setTitlePart('weather', weather.real ? weather.summary : '');
    }

    function render(w) {
      weather = w;
      $('weather-sample').hidden = !!w.real;
      $('weather-temp').textContent = deg(w.temp);
      $('weather-line').textContent = w.label + ' · H ' + deg(w.high) + ' L ' + deg(w.low);
      if (w.icon_svg) $('weather-icon').innerHTML = w.icon_svg;
      renderHours(new Date());
      setTitle();
    }

    function refresh() {
      fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(render)
        .catch(function () {}); // keep showing the last forecast
    }

    document.addEventListener('dashboard:tick', function (e) {
      var h = e.detail.getHours();
      if (h === lastHour) return;
      if (lastHour === -1) { setTitle(); if (!weather.hours) renderHours(e.detail); }
      else if (!weather.hours) renderHours(e.detail);
      lastHour = h;
    });
    setInterval(refresh, 15 * 60 * 1000);
  })();
</script>
@endpush
