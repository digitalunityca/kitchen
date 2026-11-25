<section class="card cal" aria-label="Calendar" id="cal" data-events="{{ route('modules.calendar.events') }}">
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
      <label class="calx-search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="search" id="calx-q" placeholder="Search events" autocomplete="off" aria-label="Search events">
        <button type="button" id="calx-clear" hidden aria-label="Clear search">✕</button>
      </label>
      <div class="calx-toggles" id="calx-toggles" role="group" aria-label="Calendars"></div>
      <a class="calx-settings" href="{{ route('modules.calendar.settings') }}">Calendars…</a>
      <button type="button" class="calx-close" id="calx-close" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </header>
    <p class="calx-sample" id="calx-sample" hidden>Sample events. <a href="{{ route('modules.calendar.settings') }}">Add your calendars</a> to see your own.</p>
    <div class="calx-main">
      <section class="calx-month" aria-label="Month">
        <div class="calx-month-head">
          <button type="button" class="calx-nav" id="calx-prev" aria-label="Previous month">‹</button>
          <h2 id="calx-month-title"></h2>
          <button type="button" class="calx-nav" id="calx-next" aria-label="Next month">›</button>
          <button type="button" class="calx-today" id="calx-today">Today</button>
        </div>
        <div class="calx-grid" id="calx-grid"></div>
      </section>
      <section class="calx-side" id="calx-side" aria-live="polite"><p class="cal-none">Loading…</p></section>
    </div>
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

  .calx-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; padding: 28px 120px 28px 36px; }
  .calx-overlay[hidden] { display: none; }
  .calx-sheet { width: 100%; height: 100%; display: flex; flex-direction: column; background: var(--bg); border-radius: 28px; padding: 18px 22px 22px; box-shadow: 0 30px 80px rgba(0,0,0,.3); }
  body.night .calx-sheet { background: #0E0E10; box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
  .calx-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .calx-search { display: flex; align-items: center; gap: 8px; width: 300px; flex: none; padding: 0 12px; height: 44px; border-radius: 12px; background: var(--fill); color: var(--sub); }
  .calx-search input { flex: 1; min-width: 0; border: 0; background: none; font: inherit; font-size: 16px; color: var(--text); outline: none; -webkit-appearance: none; }
  .calx-search input::-webkit-search-cancel-button { display: none; }
  .calx-search button { color: var(--sub); font-size: 14px; padding: 4px; }
  .calx-toggles { flex: 1; display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; }
  .calx-chip { flex: none; display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); font-size: 15px; font-weight: 500; }
  .calx-chip i { width: 10px; height: 10px; border-radius: 999px; }
  .calx-chip[aria-pressed="false"] { opacity: .45; }
  .calx-chip[aria-pressed="false"] i { background: var(--sub) !important; }
  .calx-settings { font-size: 15px; color: var(--accent); text-decoration: none; flex: none; }
  .calx-close, .calx-back { width: 44px; height: 44px; flex: none; border-radius: 999px; background: var(--fill); display: flex; align-items: center; justify-content: center; color: var(--text); }
  .calx-sample { margin: 0 0 10px; color: var(--sub); font-size: 14px; }
  .calx-sample[hidden] { display: none; }
  .calx-main { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 18px; }
  .calx-month { background: var(--card); border-radius: 22px; padding: 14px 16px; box-shadow: var(--shadow); display: flex; flex-direction: column; min-height: 0; }
  .calx-month-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
  .calx-month-head h2 { flex: 1; margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -.02em; text-align: center; }
  .calx-nav { width: 40px; height: 40px; border-radius: 999px; background: var(--fill); font-size: 24px; line-height: 1; color: var(--text); }
  .calx-today { padding: 8px 14px; border-radius: 999px; background: var(--fill); font-size: 14px; font-weight: 600; color: var(--accent); }
  .calx-grid { flex: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); grid-template-rows: auto; grid-auto-rows: 1fr; gap: 2px; }
  .calx-dow { font-size: 12px; font-weight: 600; color: var(--sub); text-align: center; padding: 2px 0 4px; align-self: end; }
  .calx-cell { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 3px; padding: 4px 0; border-radius: 12px; font-size: 16px; font-variant-numeric: tabular-nums; color: var(--text); }
  .calx-cell.out { color: var(--sub); opacity: .5; }
  .calx-cell > span:first-child { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 999px; }
  .calx-cell.today > span:first-child { color: var(--accent); font-weight: 700; }
  .calx-cell.selected > span:first-child { background: var(--text); color: var(--bg); font-weight: 600; }
  .calx-cell.today.selected > span:first-child { background: var(--accent); color: #fff; }
  .calx-dots { display: flex; gap: 3px; height: 6px; }
  .calx-dots i { width: 6px; height: 6px; border-radius: 999px; }
  .calx-side { background: var(--card); border-radius: 22px; padding: 16px 18px; box-shadow: var(--shadow); overflow: auto; min-height: 0; }
  .calx-side h3 { margin: 0 0 8px; font-size: 18px; font-weight: 600; }
  .calx-side h4 { margin: 14px 0 4px; font-size: 14px; color: var(--sub); font-weight: 600; }
  .calx-ev { display: flex; align-items: center; gap: 12px; width: 100%; text-align: left; padding: 9px 0; border-top: 1px solid var(--line); color: var(--text); }
  .calx-ev:first-of-type { border-top: 0; }
  .calx-ev i { width: 4px; align-self: stretch; border-radius: 2px; flex: none; }
  .calx-ev .t { width: 118px; flex: none; color: var(--sub); font-size: 14px; font-variant-numeric: tabular-nums; }
  .calx-ev b { font-weight: 500; }
  .calx-ev small { display: block; color: var(--sub); font-size: 13px; }
  .calx-ev mark { background: rgba(255, 214, 10, .45); color: inherit; border-radius: 3px; }
  .calx-detail-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
  .calx-detail { border-left: 5px solid var(--c, var(--accent)); padding-left: 16px; }
  .calx-detail h2 { margin: 0 0 4px; font-size: 24px; font-weight: 600; letter-spacing: -.02em; }
  .calx-detail .calx-meta { margin: 0 0 14px; color: var(--sub); font-size: 15px; }
  .calx-detail dl { margin: 0; display: grid; grid-template-columns: 90px 1fr; gap: 10px 14px; font-size: 16px; }
  .calx-detail dt { color: var(--sub); font-weight: 500; }
  .calx-detail dd { margin: 0; overflow-wrap: anywhere; line-height: 1.45; white-space: pre-line; }
  @media (prefers-reduced-motion: reduce) { .calx-overlay, .calx-sheet { animation: none; transition: none; } }
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

  // The calendar sheet: a month grid with coloured dots per calendar, the selected
  // day's events, search over 18 months, calendar toggles (remembered) and details.
  // Everything runs on one download from /modules/calendar/events.
  (function () {
    var card = document.getElementById('cal'), overlay = document.getElementById('calx-overlay'), side = document.getElementById('calx-side');
    var q = document.getElementById('calx-q'), clear = document.getElementById('calx-clear');
    var data = null, byDay = {}, month = null, selected = null, idleTimer = 0, detailFrom = null;
    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function fold(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
    function hidden(list) { try { if (list === undefined) return JSON.parse(localStorage.getItem('calendar-hidden') || '[]'); localStorage.setItem('calendar-hidden', JSON.stringify(list)); } catch (e) { return []; } }
    function on(ev) { return hidden().indexOf(ev.calendarId) < 0; }
    function parse(s) { var p = s.split(/[-T:]/).map(Number); return new Date(p[0], p[1] - 1, p[2], p[3] || 0, p[4] || 0); }
    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function clock(d) { var h = d.getHours(); return (h % 12 || 12) + ':' + String(d.getMinutes()).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); }
    function longDate(d) { return d.toLocaleDateString('en-CA', { weekday: 'long', month: 'long', day: 'numeric' }); }
    function timeLabel(ev) {
      if (ev.allDay) return 'All day';
      var s = parse(ev.start), e = parse(ev.end);
      return clock(s) + (e > s ? ' – ' + clock(e) : '');
    }
    function dayLabel(key) {
      if (key === data.today) return 'Today';
      var t = parse(data.today); t.setDate(t.getDate() + 1);
      return key === ymd(t) ? 'Tomorrow' : longDate(parse(key));
    }
    function mark(text, term) {
      var safe = esc(text); if (!term) return safe;
      var i = fold(text).indexOf(term); if (i < 0) return safe;
      return esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + term.length)) + '</mark>' + esc(text.slice(i + term.length));
    }
    function row(ev, term) {
      return '<button type="button" class="calx-ev" data-id="' + esc(ev.id) + '"><i style="background:' + esc(ev.color) + '"></i>' +
        '<span class="t">' + esc(timeLabel(ev)) + '</span><span><b>' + mark(ev.title, term) + '</b>' +
        (ev.location ? '<small>' + mark(ev.location, term) + '</small>' : '') + '</span></button>';
    }

    function renderToggles() {
      var off = hidden();
      document.getElementById('calx-toggles').innerHTML = data.calendars.map(function (c) {
        return '<button type="button" class="calx-chip" data-cal="' + esc(c.id) + '" aria-pressed="' + (off.indexOf(c.id) < 0) + '"><i style="background:' + esc(c.color) + '"></i>' + esc(c.name) + '</button>';
      }).join('');
    }
    function renderMonth() {
      var y = month.getFullYear(), m = month.getMonth();
      document.getElementById('calx-month-title').textContent = MONTHS[m] + ' ' + y;
      var html = ['S', 'M', 'T', 'W', 'T', 'F', 'S'].map(function (d) { return '<span class="calx-dow">' + d + '</span>'; }).join('');
      var start = new Date(y, m, 1 - new Date(y, m, 1).getDay()); // weeks start on Sunday
      for (var i = 0; i < 42; i++) {
        var d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i), key = ymd(d);
        if (i === 35 && d.getMonth() !== m) break;
        var colors = [];
        (byDay[key] || []).forEach(function (ev) { if (on(ev) && colors.indexOf(ev.color) < 0 && colors.length < 4) colors.push(ev.color); });
        html += '<button type="button" class="calx-cell' + (d.getMonth() !== m ? ' out' : '') + (key === data.today ? ' today' : '') + (key === selected ? ' selected' : '') + '" data-day="' + key + '">' +
          '<span>' + d.getDate() + '</span><span class="calx-dots">' + colors.map(function (c) { return '<i style="background:' + esc(c) + '"></i>'; }).join('') + '</span></button>';
      }
      document.getElementById('calx-grid').innerHTML = html;
    }
    function renderDay() {
      detailFrom = 'day';
      var events = (byDay[selected] || []).filter(on);
      side.innerHTML = '<h3>' + esc(dayLabel(selected)) + '</h3>' + (events.length ? events.map(function (ev) { return row(ev); }).join('') : '<p class="cal-none">Nothing planned</p>');
    }
    function renderSearch() {
      detailFrom = 'search';
      var term = fold(q.value.trim());
      if (!term) { renderDay(); return; }
      var groups = {}, count = 0;
      data.events.forEach(function (ev) {
        if (!on(ev) || count >= 200) return;
        if ((fold(ev.title) + '\n' + fold(ev.location) + '\n' + fold(ev.notes)).indexOf(term) < 0) return;
        var key = ev.start.slice(0, 10); (groups[key] = groups[key] || []).push(ev); count++;
      });
      var keys = Object.keys(groups).sort();
      side.innerHTML = '<h3>' + count + (count === 1 ? ' result' : ' results') + '</h3>' + (keys.length ? keys.map(function (k) {
        return '<h4>' + esc(dayLabel(k)) + '</h4>' + groups[k].map(function (ev) { return row(ev, term); }).join('');
      }).join('') : '<p class="cal-none">No events match.</p>');
    }
    function renderDetail(id) {
      var ev = data.events.filter(function (e) { return e.id === id; })[0]; if (!ev) return;
      var s = parse(ev.start), e = parse(ev.end), when;
      if (ev.allDay) { var last = new Date(e.getFullYear(), e.getMonth(), e.getDate() - 1); when = longDate(s) + (ymd(last) !== ymd(s) ? ' – ' + longDate(last) : '') + ' · All day'; }
      else when = longDate(s) + ' · ' + clock(s) + ' – ' + (ymd(s) === ymd(e) ? clock(e) : longDate(e) + ' · ' + clock(e));
      side.innerHTML = '<div class="calx-detail-head"><button type="button" class="calx-back" id="calx-back" aria-label="Back"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg></button></div>' +
        '<article class="calx-detail" style="--c:' + esc(ev.color) + '"><h2>' + esc(ev.title) + '</h2><p class="calx-meta">' + esc(ev.calendar) + '</p><dl>' +
        '<dt>When</dt><dd>' + esc(when) + '</dd>' +
        (ev.location ? '<dt>Where</dt><dd>' + esc(ev.location) + '</dd>' : '') +
        (ev.notes ? '<dt>Notes</dt><dd>' + esc(ev.notes) + '</dd>' : '') +
        (ev.attendees.length ? '<dt>Guests</dt><dd>' + esc(ev.attendees.join(', ')) + '</dd>' : '') + '</dl></article>';
      side.scrollTop = 0;
    }
    function back() { if (detailFrom === 'search' && q.value.trim()) renderSearch(); else renderDay(); }
    function select(key) {
      selected = key; var d = parse(key);
      if (d.getMonth() !== month.getMonth() || d.getFullYear() !== month.getFullYear()) month = new Date(d.getFullYear(), d.getMonth(), 1);
      q.value = ''; clear.hidden = true; renderMonth(); renderDay();
    }
    function resetIdle() { clearTimeout(idleTimer); idleTimer = setTimeout(close, 2 * 60 * 1000); }
    function open(eventId) {
      overlay.hidden = false; resetIdle(); side.innerHTML = '<p class="cal-none">Loading…</p>';
      fetch(card.dataset.events, { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (d) {
          data = d; byDay = {};
          d.events.forEach(function (ev) { ev.days.forEach(function (k) { (byDay[k] = byDay[k] || []).push(ev); }); });
          document.getElementById('calx-sample').hidden = !d.sample;
          renderToggles();
          var target = eventId && d.events.filter(function (e) { return e.id === eventId; })[0];
          month = parse((target ? target.days[0] : null) || d.today); month = new Date(month.getFullYear(), month.getMonth(), 1);
          select(target ? target.days[0] : d.today);
          if (target) renderDetail(target.id);
        })
        .catch(function () { side.innerHTML = '<p class="cal-none">Can’t load the calendars right now.</p>'; });
    }
    function close() { overlay.hidden = true; clearTimeout(idleTimer); q.blur(); }
    function shiftMonth(n) {
      month = new Date(month.getFullYear(), month.getMonth() + n, 1);
      var key = ymd(month);
      if (key < data.from.slice(0, 7) + '-01' || key > data.to) { month = new Date(month.getFullYear(), month.getMonth() - n, 1); return; }
      renderMonth();
    }

    document.getElementById('cal-open').addEventListener('click', function () { open(null); });
    card.addEventListener('click', function (e) { var ev = e.target.closest('[data-event]'); if (ev) open(ev.dataset.event); });
    document.getElementById('calx-close').addEventListener('click', close);
    document.getElementById('calx-prev').addEventListener('click', function () { shiftMonth(-1); });
    document.getElementById('calx-next').addEventListener('click', function () { shiftMonth(1); });
    document.getElementById('calx-today').addEventListener('click', function () { select(data.today); });
    document.getElementById('calx-grid').addEventListener('click', function (e) { var c = e.target.closest('[data-day]'); if (c) select(c.dataset.day); });
    document.getElementById('calx-toggles').addEventListener('click', function (e) {
      var chip = e.target.closest('[data-cal]'); if (!chip) return;
      var off = hidden(), i = off.indexOf(chip.dataset.cal);
      if (i >= 0) off.splice(i, 1); else off.push(chip.dataset.cal);
      hidden(off); renderToggles(); renderMonth();
      if (q.value.trim()) renderSearch(); else renderDay();
    });
    side.addEventListener('click', function (e) {
      if (e.target.closest('#calx-back')) { back(); return; }
      var ev = e.target.closest('.calx-ev'); if (ev) renderDetail(ev.dataset.id);
    });
    q.addEventListener('input', function () { clear.hidden = !q.value; resetIdle(); renderSearch(); });
    clear.addEventListener('click', function () { q.value = ''; clear.hidden = true; renderDay(); q.focus(); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    overlay.addEventListener('pointerdown', resetIdle);
    document.addEventListener('dashboard:home', close);
  })();
</script>
@endpush
