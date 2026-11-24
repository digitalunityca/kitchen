<section class="card" aria-label="Clock">
  <p class="label">Time</p>
  <div style="display:flex;align-items:baseline;gap:10px">
    <span class="big" style="font-size:104px" id="clock-time"></span>
    <span style="font-size:22px;font-weight:500;color:var(--sub)" id="clock-ampm"></span>
  </div>
  <p style="margin:0;font-size:17px;color:var(--sub)" id="clock-weekday"></p>
</section>

@push('scripts')
<script>
  document.addEventListener('dashboard:tick', function (e) {
    var now = e.detail, h = now.getHours();
    document.getElementById('clock-time').textContent = (h % 12 || 12) + ':' + String(now.getMinutes()).padStart(2, '0');
    document.getElementById('clock-ampm').textContent = h < 12 ? 'AM' : 'PM';
    document.getElementById('clock-weekday').textContent = now.toLocaleDateString('en-CA', { weekday: 'long' });
  });
</script>
@endpush
