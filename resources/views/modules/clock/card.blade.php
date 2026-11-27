<section class="card" aria-label="{{ __('Clock') }}">
  <p class="label" id="clock-greeting">{{ __('Good day') }}</p>
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
    var fr = window.LOCALE === 'fr-CA';
    document.getElementById('clock-time').textContent = (fr ? h : (h % 12 || 12)) + ':' + String(now.getMinutes()).padStart(2, '0');
    document.getElementById('clock-ampm').textContent = fr ? '' : (h < 12 ? 'AM' : 'PM');
    document.getElementById('clock-greeting').textContent = h < 12 ? __('Good morning') : h < 18 ? __('Good afternoon') : __('Good evening');
    var day = now.toLocaleDateString(window.LOCALE, { weekday: 'long' });
    document.getElementById('clock-weekday').textContent = day.charAt(0).toUpperCase() + day.slice(1);
  });
</script>
@endpush
