<section class="card cal" aria-label="Calendar">
  <div class="row">
    <p class="label" id="cal-month"></p>
    <span class="pill">Sample events</span>
  </div>
  <div class="grid7" id="cal-grid"></div>
  <div class="events">
    <p style="margin:0 0 8px;font-size:15px;font-weight:600">Today</p>
    @foreach ($events as $event)
      <div class="ev"><i style="background:{{ $event['color'] }}"></i><div><b>{{ $event['title'] }}</b><small>{{ $event['time'] }}</small></div></div>
    @endforeach
  </div>
</section>

@push('scripts')
<script>
  (function () {
    var lastDay = -1;
    document.addEventListener('dashboard:tick', function (e) {
      var now = e.detail, d = now.getDate();
      if (d === lastDay) return;
      lastDay = d;
      var y = now.getFullYear(), m = now.getMonth();
      document.getElementById('cal-month').textContent = now.toLocaleDateString('en-CA', { month: 'long', year: 'numeric' });
      var html = ['M', 'T', 'W', 'T', 'F', 'S', 'S'].map(function (x) { return '<span class="dow">' + x + '</span>'; }).join('');
      var first = (new Date(y, m, 1).getDay() + 6) % 7, days = new Date(y, m + 1, 0).getDate();
      for (var i = 0; i < first; i++) html += '<span class="day"><span></span></span>';
      for (var n = 1; n <= days; n++) html += '<span class="day' + (n === d ? ' today' : n < d ? ' past' : '') + '"><span>' + n + '</span></span>';
      document.getElementById('cal-grid').innerHTML = html;
    });
  })();
</script>
@endpush
