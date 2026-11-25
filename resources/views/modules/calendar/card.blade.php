<section class="card cal" aria-label="Calendar" id="cal" data-sheet="{{ route('modules.calendar.sheet') }}">
  <div class="row">
    <p class="label" id="cal-month"></p>
    <span class="row" style="gap:8px">
      @if ($sample)<a class="pill" href="{{ route('modules.calendar.settings') }}">Sample data</a>@endif
      <button type="button" class="cal-open" id="cal-open" aria-label="Open the calendar">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="3"/><path d="M3 9.5h18M8 3v3M16 3v3M7.5 13.5h3M7.5 17h6"/></svg>
      </button>
    </span>
  </div>
  <div class="grid7" id="cal-grid"></div>
  <div class="events cal-events">
    @foreach (['Today' => $today, 'Tomorrow' => $tomorrow] as $label => $events)
      <p class="cal-day">{{ $label }}</p>
      @forelse ($events as $event)
        <button type="button" class="ev" data-event="{{ $event['id'] }}">
          <i style="background:{{ $event['color'] }}"></i>
          <div><b>{{ $event['title'] }}</b><small>@include('modules.calendar.time', ['event' => $event])@if ($event['location']) · {{ \Illuminate\Support\Str::before($event['location'], ',') }}@endif</small></div>
        </button>
      @empty
        <p class="cal-none">Nothing planned</p>
      @endforelse
    @endforeach
    @if ($next)
      <p class="cal-day">Next</p>
      <button type="button" class="ev" data-event="{{ $next['id'] }}">
        <i style="background:{{ $next['color'] }}"></i>
        <div><b>{{ $next['title'] }}</b><small>{{ $next['start']->format('D M j') }} · @include('modules.calendar.time', ['event' => $next])@if ($next['location']) · {{ \Illuminate\Support\Str::before($next['location'], ',') }}@endif</small></div>
      </button>
    @endif
  </div>
</section>

<div class="calx-overlay" id="calx-overlay" hidden role="dialog" aria-modal="true" aria-label="Calendar">
  <div class="calx-sheet">
    <header class="calx-head">
      <button type="button" class="calx-back" id="calx-back" hidden aria-label="Back to the list">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
      </button>
      <span class="calx-title">Calendar</span>
      <a class="calx-settings" href="{{ route('modules.calendar.settings') }}">Calendars…</a>
      <button type="button" class="calx-close" id="calx-close" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </header>
    <div class="calx-body" id="calx-body"><p class="cal-none">Loading…</p></div>
  </div>
</div>

@push('styles')
<style>
  .cal-open { width: 40px; height: 40px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--fill); color: var(--text); }
  .cal-events { overflow: hidden; -webkit-mask-image: linear-gradient(#000 85%, transparent); mask-image: linear-gradient(#000 85%, transparent); }
  .cal-day { margin: 6px 0 2px; font-size: 15px; font-weight: 600; }
  .cal-none { margin: 2px 0 6px; color: var(--sub); font-size: 14px; }
  .cal .ev { width: 100%; text-align: left; padding: 4px 0; }
  .cal .ev small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .cal .ev div { min-width: 0; }

  .calx-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; padding: 32px 120px 32px 40px; }
  .calx-overlay[hidden] { display: none; }
  .calx-sheet { width: min(980px, 100%); height: 100%; display: flex; flex-direction: column; background: var(--bg); border-radius: 28px; padding: 20px 26px 24px; box-shadow: 0 30px 80px rgba(0,0,0,.3); }
  body.night .calx-sheet { background: #0E0E10; box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
  .calx-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
  .calx-title { flex: 1; font-size: 26px; font-weight: 600; letter-spacing: -.02em; }
  .calx-settings { font-size: 15px; color: var(--accent); text-decoration: none; }
  .calx-close, .calx-back { width: 48px; height: 48px; border-radius: 999px; background: var(--fill); display: flex; align-items: center; justify-content: center; color: var(--text); }
  .calx-back[hidden] { display: none; }
  .calx-body { flex: 1; min-height: 0; overflow: auto; }
  .calx-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
  .calx-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); font-size: 15px; font-weight: 500; }
  .calx-chip i { width: 10px; height: 10px; border-radius: 999px; }
  .calx-chip[aria-pressed="false"] { opacity: .45; }
  .calx-chip[aria-pressed="false"] i { background: var(--sub) !important; }
  .calx-day { background: var(--card); border-radius: 20px; padding: 14px 18px; margin-bottom: 12px; box-shadow: var(--shadow); }
  .calx-day h3 { margin: 0 0 6px; font-size: 15px; color: var(--sub); font-weight: 600; }
  .calx-day.calx-empty { display: none; }
  .calx-ev { display: flex; align-items: center; gap: 14px; width: 100%; text-align: left; padding: 8px 0; border-top: 1px solid var(--line); }
  .calx-ev:first-of-type { border-top: 0; }
  .calx-ev i { width: 4px; align-self: stretch; border-radius: 2px; flex: none; }
  .calx-ev .t { width: 150px; flex: none; color: var(--sub); font-size: 14px; font-variant-numeric: tabular-nums; }
  .calx-ev b { font-weight: 500; }
  .calx-ev small { display: block; color: var(--sub); font-size: 13px; }
  .calx-ev.calx-hidden { display: none; }
  .calx-detail { background: var(--card); border-radius: 22px; padding: 24px 28px; box-shadow: var(--shadow); border-left: 6px solid var(--c, var(--accent)); }
  .calx-detail h2 { margin: 0 0 4px; font-size: 28px; font-weight: 600; letter-spacing: -.02em; }
  .calx-detail .calx-meta { margin: 0 0 16px; color: var(--sub); font-size: 15px; }
  .calx-detail dl { margin: 0; display: grid; grid-template-columns: 120px 1fr; gap: 10px 16px; font-size: 16px; }
  .calx-detail dt { color: var(--sub); font-weight: 500; }
  .calx-detail dd { margin: 0; overflow-wrap: anywhere; line-height: 1.45; }
  .calx-sample { margin: 0 0 12px; color: var(--sub); font-size: 14px; }
</style>
@endpush

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

  // The calendar sheet: next 14 days, calendar filters (remembered), event details.
  (function () {
    var card = document.getElementById('cal'), overlay = document.getElementById('calx-overlay'), body = document.getElementById('calx-body'), back = document.getElementById('calx-back');
    var idleTimer = 0;
    function hiddenCalendars(list) {
      try { if (list === undefined) return JSON.parse(localStorage.getItem('calendar-hidden') || '[]'); localStorage.setItem('calendar-hidden', JSON.stringify(list)); } catch (e) { return []; }
    }
    function applyFilters() {
      var hidden = hiddenCalendars();
      body.querySelectorAll('[data-cal-chip]').forEach(function (c) { c.setAttribute('aria-pressed', hidden.indexOf(c.dataset.calChip) < 0 ? 'true' : 'false'); });
      body.querySelectorAll('.calx-ev').forEach(function (ev) { ev.classList.toggle('calx-hidden', hidden.indexOf(ev.dataset.cal) >= 0); });
      body.querySelectorAll('.calx-day').forEach(function (day) {
        day.classList.toggle('calx-empty', !day.querySelector('.calx-ev:not(.calx-hidden)') && !day.hasAttribute('data-always'));
        var none = day.querySelector('.calx-nothing'); if (none) none.hidden = !!day.querySelector('.calx-ev:not(.calx-hidden)');
      });
    }
    function showList() {
      back.hidden = true;
      body.querySelector('.calx-list').hidden = false;
      body.querySelectorAll('[data-detail]').forEach(function (d) { d.hidden = true; });
    }
    function showDetail(id) {
      var detail = body.querySelector('[data-detail="' + id + '"]'); if (!detail) return;
      body.querySelector('.calx-list').hidden = true;
      body.querySelectorAll('[data-detail]').forEach(function (d) { d.hidden = d !== detail; });
      back.hidden = false; body.scrollTop = 0;
    }
    function resetIdle() { clearTimeout(idleTimer); idleTimer = setTimeout(close, 2 * 60 * 1000); }
    function open(eventId) {
      overlay.hidden = false; back.hidden = true; resetIdle();
      body.innerHTML = '<p class="cal-none">Loading…</p>';
      fetch(card.dataset.sheet, { cache: 'no-store' })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) { body.innerHTML = html; applyFilters(); if (eventId) showDetail(eventId); })
        .catch(function () { body.innerHTML = '<p class="cal-none">Can’t load the calendars right now.</p>'; });
    }
    function close() { overlay.hidden = true; clearTimeout(idleTimer); }

    document.getElementById('cal-open').addEventListener('click', function () { open(null); });
    card.addEventListener('click', function (e) { var ev = e.target.closest('[data-event]'); if (ev) open(ev.dataset.event); });
    document.getElementById('calx-close').addEventListener('click', close);
    back.addEventListener('click', showList);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    overlay.addEventListener('pointerdown', resetIdle);
    document.addEventListener('dashboard:home', close);
    body.addEventListener('click', function (e) {
      var chip = e.target.closest('[data-cal-chip]'), ev = e.target.closest('.calx-ev');
      if (chip) {
        var hidden = hiddenCalendars(), i = hidden.indexOf(chip.dataset.calChip);
        if (i >= 0) hidden.splice(i, 1); else hidden.push(chip.dataset.calChip);
        hiddenCalendars(hidden); applyFilters();
      }
      if (ev) showDetail(ev.dataset.id);
    });
  })();
</script>
@endpush
