<section class="card" aria-label="Weather">
  <div class="row">
    <p class="label">Outside · {{ $city }}</p>
    <span class="pill">Sample data</span>
  </div>
  <div style="display:flex;align-items:center;gap:20px">
    <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#F5A524" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="9" r="3.5"/><path d="M9 2.5v1.5M3.5 9H2M4.4 4.4l1 1M13.6 4.4l-1 1"/><path d="M8 20h9a4 4 0 0 0 0-8 5 5 0 0 0-9.6 1.5A3.3 3.3 0 0 0 8 20z" stroke="var(--sub)" fill="var(--card)"/></svg>
    <div style="display:flex;flex-direction:column;gap:2px">
      <span class="big" style="font-size:72px">4°</span>
      <span style="font-size:17px;color:var(--sub)">Partly cloudy · H 6° L −1°</span>
    </div>
  </div>
  <div class="hours" id="weather-hours"></div>
</section>

@push('scripts')
<script>
  (function () {
    var lastHour = -1, temps = ['4°', '3°', '2°', '1°'];
    document.addEventListener('dashboard:tick', function (e) {
      var h = e.detail.getHours();
      if (h === lastHour) return;
      lastHour = h;
      document.getElementById('weather-hours').innerHTML = [1, 2, 3, 4].map(function (k, i) {
        var hh = (h + k) % 24;
        return '<div><span>' + (hh % 12 || 12) + (hh < 12 ? ' AM' : ' PM') + '</span><span>' + temps[i] + '</span></div>';
      }).join('');
    });
  })();
</script>
@endpush
