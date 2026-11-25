<section class="card cal" aria-label="Calendar" id="cal" data-events="{{ route('modules.calendar.events') }}" data-card="{{ route('modules.calendar.card') }}">
  <div class="row">
    <button type="button" class="label cal-month" id="cal-month" aria-label="Open the month view"></button>
    <span class="row" style="gap:8px">
      @if ($sample)<a class="pill" href="{{ route('modules.calendar.settings') }}">Sample data</a>@endif
      <span class="cal-pick">
        <button type="button" class="cal-icon-btn" id="cal-pick" aria-haspopup="true" aria-expanded="false" aria-label="Choose calendars to show">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h11M4 12h11M4 17h11"/><path d="m17 10 2.5 2.5L22 10"/></svg>
        </button>
        <span class="cal-menu" id="cal-menu" hidden role="menu"></span>
      </span>
      <button type="button" class="cal-icon-btn" id="cal-open" aria-label="Open the month view">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="3"/><path d="M3 9.5h18M8 3v3M16 3v3M7.5 13.5h3M7.5 17h6"/></svg>
      </button>
    </span>
  </div>
  <div class="cal-weeks" id="cal-weeks"></div>
  <div class="events cal-events" id="cal-list"></div>
  @php($cardJson = json_encode(['today' => $today, 'weekStart' => $weekStart, 'calendars' => $calendars, 'events' => $events, 'next' => $next], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE))
  <script type="application/json" id="cal-data">{!! $cardJson !!}</script>
</section>

{{-- Reminder banner, shown at the top of the screen at an event's reminder time. --}}
<div class="cal-reminder" id="cal-reminder" hidden role="alert"></div>

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
  .cal { justify-content: flex-start; gap: 14px; }
  .cal-month { font: inherit; font-size: 15px; font-weight: 600; color: var(--sub); padding: 0; }
  .cal-icon-btn { width: 40px; height: 40px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--fill); color: var(--text); }
  .cal-pick { position: relative; }
  .cal-menu { position: absolute; right: 0; top: 46px; z-index: 30; min-width: 220px; display: flex; flex-direction: column; padding: 6px; border-radius: 16px; background: var(--card); box-shadow: 0 10px 30px rgba(0,0,0,.18), 0 0 0 1px var(--line); }
  .cal-menu[hidden] { display: none; }
  .cal-menu label { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 15px; font-weight: 500; }
  .cal-menu label:active { background: var(--fill); }
  .cal-menu input { width: 20px; height: 20px; accent-color: var(--accent); margin: 0; }
  .cal-menu i { width: 10px; height: 10px; border-radius: 999px; flex: none; }
  .cal-weeks { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); row-gap: 2px; text-align: center; }
  .cal-weeks .dow { padding-bottom: 2px; }
  .cal-d { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 2px 0; font-size: 15px; font-variant-numeric: tabular-nums; color: var(--text); }
  .cal-d > span:first-child { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 999px; }
  .cal-d.past { color: var(--sub); }
  .cal-d.today > span:first-child { background: var(--accent); color: #fff; font-weight: 600; }
  .cal-d.selected > span:first-child { box-shadow: 0 0 0 2px var(--text); }
  .cal-d .dots { display: flex; gap: 3px; height: 5px; }
  .cal-d .dots i { width: 5px; height: 5px; border-radius: 999px; }
  .cal-events { flex: 1; overflow: hidden; }
  .cal-back { font-size: 14px; color: var(--accent); padding: 0; margin-left: 8px; font-weight: 500; }
  /* Colour bars come from --c; finished events step back (muted, hollow bar), running ones say "Now". */
  .cal .ev i, .calx-ev i { background: var(--c); }
  /* Leading column, shared width so titles line up: a big icon over both lines, or the colour dot. */
  .ev-lead { width: 32px; flex: none; display: flex; align-items: center; justify-content: center; align-self: center; }
  .ev-icon { font-size: 28px; line-height: 1; }
  .ev-dot { width: 12px; height: 12px; border-radius: 999px; background: var(--c); }
  .cal .ev { gap: 12px; }
  .cal .ev > i { display: none; }
  .ev.past .ev-icon, .calx-ev.past .ev-icon { opacity: .5; }
  .ev.past .ev-dot, .calx-ev.past .ev-dot { background: transparent; box-shadow: inset 0 0 0 1.5px var(--c); }
  .calx-ev .ev-lead { margin-left: -4px; }
  .cal .ev.past, .calx-ev.past { color: var(--sub); }
  .cal .ev.past b, .calx-ev.past b { color: var(--sub); font-weight: 400; }
  .cal .ev.past i, .calx-ev.past i { background: transparent; box-shadow: inset 0 0 0 1.5px var(--c); }
  .cal-now { font-style: normal; font-size: 11px; font-weight: 700; color: #fff; background: #FF3B30; border-radius: 6px; padding: 2px 6px; margin-left: 8px; vertical-align: 2px; letter-spacing: .03em; text-transform: uppercase; }
  .cal-reminder {
    position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 55; width: min(640px, calc(100vw - 180px));
    display: flex; align-items: center; gap: 16px; padding: 16px 18px; border-radius: 22px;
    background: var(--card); box-shadow: 0 16px 50px rgba(0,0,0,.22), 0 0 0 1px var(--line); border-left: 6px solid var(--c, var(--accent));
    animation: cal-drop .35s ease-out;
  }
  .cal-reminder[hidden] { display: none; }
  body.night .cal-reminder { background: #2C2C2E; }
  @keyframes cal-drop { from { opacity: 0; transform: translate(-50%, -12px); } }
  .cal-reminder .ev-lead { width: 40px; }
  .cal-reminder .ev-icon { font-size: 34px; }
  .cal-reminder .ev-dot { width: 16px; height: 16px; }
  .cal-reminder-text { flex: 1; min-width: 0; }
  .cal-reminder-text small { display: block; font-size: 13px; font-weight: 700; color: var(--c, var(--accent)); text-transform: uppercase; letter-spacing: .04em; }
  .cal-reminder-text b { display: block; font-size: 20px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .cal-reminder-text span { display: block; font-size: 15px; color: var(--sub); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .cal-reminder button { width: 44px; height: 44px; border-radius: 999px; background: var(--fill); color: var(--text); flex: none; font-size: 18px; }
  @media (prefers-reduced-motion: reduce) { .cal-reminder { animation: none; } }
  .cal-more { font-size: 14px; color: var(--accent); text-align: left; padding: 4px 0; }
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
  .calx-url { color: var(--sub); font-size: 14px; }
  .calx-detail dd { margin: 0; overflow-wrap: anywhere; line-height: 1.45; white-space: pre-line; }
  @media (prefers-reduced-motion: reduce) { .calx-overlay, .calx-sheet { animation: none; transition: none; } }
</style>
@endpush

@push('scripts')
<script>
  // Shared calendar helpers and the one setting both views use: which calendars are hidden.
  window.CalendarUi = (function () {
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function parse(s) { var p = s.split(/[-T:]/).map(Number); return new Date(p[0], p[1] - 1, p[2], p[3] || 0, p[4] || 0); }
    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function clock(d) { var h = d.getHours(); return (h % 12 || 12) + ':' + String(d.getMinutes()).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); }
    function timeLabel(ev) { if (ev.allDay) return 'All day'; var s = parse(ev.start), e = parse(ev.end); return clock(s) + (e > s ? ' – ' + clock(e) : ''); }
    /** "past" once a timed event ends or an all-day event's last day is over; "now" while a timed event runs. */
    function phase(ev) {
      var now = new Date(), s = parse(ev.start), e = parse(ev.end);
      if (ev.allDay) e = new Date(e.getFullYear(), e.getMonth(), e.getDate());
      if (e <= now) return 'past';
      return !ev.allDay && s <= now ? 'now' : '';
    }
    function title(ev) { return (ev.icon ? ev.icon + ' ' : '') + ev.title; }
    /** The leading column: the calendar's icon spanning both lines, or its colour dot. */
    function lead(ev) { return '<span class="ev-lead">' + (ev.icon ? '<span class="ev-icon">' + esc(ev.icon) + '</span>' : '<span class="ev-dot"></span>') + '</span>'; }
    function badge(ev) { return phase(ev) === 'now' ? '<em class="cal-now">Now</em>' : ''; }
    function hidden(list) {
      try { if (list === undefined) return JSON.parse(localStorage.getItem('calendar-hidden') || '[]'); localStorage.setItem('calendar-hidden', JSON.stringify(list)); } catch (e) { return []; }
      document.dispatchEvent(new CustomEvent('calendar:filter'));
    }
    return { esc: esc, parse: parse, ymd: ymd, clock: clock, timeLabel: timeLabel, phase: phase, title: title, lead: lead, badge: badge, hidden: hidden };
  })();

  // The card: two weeks (Sunday start) with dots, then Today / Tomorrow / Next, or a tapped day.
  // The calendars dropdown filters it (same setting as the month view's chips).
  (function () {
    var U = window.CalendarUi, card = document.getElementById('cal'), list = document.getElementById('cal-list'), menu = document.getElementById('cal-menu'), pick = document.getElementById('cal-pick');
    var data = null, selected = null, byDay = {};
    function load(d) {
      data = d; byDay = {};
      data.events.forEach(function (ev) { ev.days.forEach(function (k) { (byDay[k] = byDay[k] || []).push(ev); }); });
      if (selected && (selected < data.weekStart || !(selected in byDay) && selected > add(13))) selected = null;
    }
    load(JSON.parse(document.getElementById('cal-data').textContent));
    function on(ev) { return U.hidden().indexOf(ev.calendarId) < 0; }
    function add(days) { var d = U.parse(data.today); d.setDate(d.getDate() + days); return U.ymd(d); }

    function renderWeeks() {
      var start = U.parse(data.weekStart), end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 13);
      var spans = end.getMonth() !== start.getMonth(), fmt = { month: spans ? 'short' : 'long' };
      var label = start.toLocaleDateString('en-CA', fmt).replace('.', '') + (spans ? ' – ' + end.toLocaleDateString('en-CA', fmt).replace('.', '') : '') + ' ' + end.getFullYear();
      document.getElementById('cal-month').textContent = label;
      var html = ['S', 'M', 'T', 'W', 'T', 'F', 'S'].map(function (x) { return '<span class="dow">' + x + '</span>'; }).join('');
      for (var i = 0; i < 14; i++) {
        var d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i), key = U.ymd(d), colors = [];
        (byDay[key] || []).forEach(function (ev) { if (on(ev) && colors.indexOf(ev.color) < 0 && colors.length < 3) colors.push(ev.color); });
        html += '<button type="button" class="cal-d' + (key < data.today ? ' past' : '') + (key === data.today ? ' today' : '') + (key === selected ? ' selected' : '') + '" data-day="' + key + '">' +
          '<span>' + d.getDate() + '</span><span class="dots">' + colors.map(function (c) { return '<i style="background:' + U.esc(c) + '"></i>'; }).join('') + '</span></button>';
      }
      document.getElementById('cal-weeks').innerHTML = html;
    }
    function row(ev, withDate) {
      var loc = ev.location ? ' · ' + ev.location.split(',')[0] : '';
      var date = withDate ? U.parse(ev.start).toLocaleDateString('en-CA', { weekday: 'short', month: 'short', day: 'numeric' }).replace(/\./g, '') + ' · ' : '';
      return '<button type="button" class="ev ' + U.phase(ev) + '" data-event="' + U.esc(ev.id) + '" data-day="' + U.esc(ev.days[0] || '') + '" style="--c:' + U.esc(ev.color) + '">' + U.lead(ev) + '<div><b>' + U.esc(ev.title) + U.badge(ev) + '</b><small>' + U.esc(date + U.timeLabel(ev) + loc) + '</small></div></button>';
    }
    function section(title, events, extra) {
      return '<p class="cal-day">' + title + (extra || '') + '</p>' + (events.length ? events.map(function (ev) { return row(ev); }).join('') : '<p class="cal-none">Nothing planned</p>');
    }
    function renderList() {
      var html;
      if (selected) {
        var d = U.parse(selected);
        html = section(U.esc(d.toLocaleDateString('en-CA', { weekday: 'long', month: 'long', day: 'numeric' })), (byDay[selected] || []).filter(on), '<button type="button" class="cal-back" id="cal-back">Back to today</button>');
      } else {
        html = section('Today', (byDay[data.today] || []).filter(on)) + section('Tomorrow', (byDay[add(1)] || []).filter(on));
        var next = data.next.filter(on).sort(function (a, b) { return a.start < b.start ? -1 : 1; })[0];
        if (next) html += '<p class="cal-day">Next</p>' + row(next, true);
      }
      list.innerHTML = html;
      // Never fade or cut an event: drop rows that don't fit and offer "+N more".
      var rows = list.querySelectorAll('.ev'), hiddenCount = 0;
      for (var i = rows.length - 1; i >= 0 && list.scrollHeight > list.clientHeight + 1; i--) { rows[i].remove(); hiddenCount++; }
      if (hiddenCount) {
        list.insertAdjacentHTML('beforeend', '<button type="button" class="cal-more" data-day="' + (selected || data.today) + '">+' + hiddenCount + ' more in the calendar</button>');
        while (list.scrollHeight > list.clientHeight + 1 && list.querySelectorAll('.ev').length) { list.querySelector('.ev:last-of-type').remove(); }
      }
    }
    function renderMenu() {
      var off = U.hidden();
      menu.innerHTML = data.calendars.map(function (c) {
        return '<label><input type="checkbox" data-cal="' + U.esc(c.id) + '"' + (off.indexOf(c.id) < 0 ? ' checked' : '') + '><i style="background:' + U.esc(c.color) + '"></i>' + U.esc((c.icon ? c.icon + ' ' : '') + c.name) + '</label>';
      }).join('') || '<label>No calendars yet</label>';
    }
    function render() { renderWeeks(); renderList(); }
    function openMonth(eventId, day) { if (window.CalendarSheet) CalendarSheet.open(eventId || null, day || selected || data.today); }

    render(); renderMenu();
    document.getElementById('cal-weeks').addEventListener('click', function (e) {
      var d = e.target.closest('[data-day]'); if (!d) return;
      selected = d.dataset.day === selected || d.dataset.day === data.today && !selected ? null : d.dataset.day; render();
    });
    list.addEventListener('click', function (e) {
      if (e.target.closest('#cal-back')) { selected = null; render(); return; }
      var ev = e.target.closest('[data-event]'); if (ev) { openMonth(ev.dataset.event, ev.dataset.day); return; }
      var more = e.target.closest('.cal-more'); if (more) openMonth(null, more.dataset.day);
    });
    document.getElementById('cal-open').addEventListener('click', function () { openMonth(); });
    document.getElementById('cal-month').addEventListener('click', function () { openMonth(); });
    pick.addEventListener('click', function (e) { e.stopPropagation(); renderMenu(); menu.hidden = !menu.hidden; pick.setAttribute('aria-expanded', String(!menu.hidden)); });
    menu.addEventListener('click', function (e) { e.stopPropagation(); });
    menu.addEventListener('change', function (e) {
      var box = e.target.closest('[data-cal]'); if (!box) return;
      var off = U.hidden().filter(function (id) { return id !== box.dataset.cal; });
      if (!box.checked) off.push(box.dataset.cal);
      U.hidden(off);
    });
    document.addEventListener('click', function () { if (!menu.hidden) { menu.hidden = true; pick.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('calendar:filter', function () { render(); renderMenu(); });
    document.addEventListener('dashboard:home', function () { selected = null; menu.hidden = true; render(); });
    // Stay current without a reload: new events, new calendars and a new day every 5 minutes.
    // The selected day and an open month view are left as they are.
    setInterval(function () {
      fetch(card.dataset.card, { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (d) { load(d); render(); if (menu.hidden) renderMenu(); })
        .catch(function () {}); // keep what's on screen
    }, 5 * 60 * 1000);
    // Reminders: at an event's reminder time (its own alarm, or the calendar's setting),
    // a banner drops in with a soft chime, and is said out loud if the calendar asks for it.
    // Tap to dismiss; it goes away by itself when the event starts.
    var banner = document.getElementById('cal-reminder'), showing = null;
    function done(list) { try { if (list === undefined) return JSON.parse(localStorage.getItem('calendar-reminded') || '{}'); localStorage.setItem('calendar-reminded', JSON.stringify(list)); } catch (e) { return {}; } }
    function chime() {
      try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)(), t = ctx.currentTime;
        [[880, 0], [1318.5, .18]].forEach(function (n) {
          var o = ctx.createOscillator(), g = ctx.createGain();
          o.type = 'sine'; o.frequency.value = n[0]; o.connect(g); g.connect(ctx.destination);
          g.gain.setValueAtTime(0, t + n[1]); g.gain.linearRampToValueAtTime(.25, t + n[1] + .02); g.gain.exponentialRampToValueAtTime(.001, t + n[1] + .9);
          o.start(t + n[1]); o.stop(t + n[1] + 1);
        });
        setTimeout(function () { ctx.close(); }, 1500);
      } catch (e) {}
    }
    function say(text) {
      if (window.fully && typeof fully.textToSpeech === 'function') { try { fully.textToSpeech(text); return; } catch (e) {} }
      if (window.speechSynthesis) { var u = new SpeechSynthesisUtterance(text); u.lang = 'en-CA'; speechSynthesis.speak(u); }
    }
    function remind(ev) {
      var start = U.parse(ev.start), mins = Math.max(1, Math.round((start - new Date()) / 60000));
      var when = mins >= 120 ? U.timeLabel(ev) : 'In ' + mins + ' min';
      showing = ev;
      banner.style.setProperty('--c', ev.color);
      banner.innerHTML = U.lead(ev) + '<div class="cal-reminder-text"><small>' + U.esc(when) + '</small><b>' + U.esc(ev.title) + '</b><span>' +
        U.esc(start.toLocaleDateString('en-CA', { weekday: 'short' }).replace('.', '') + ' ' + U.clock(start) + (ev.location ? ' · ' + ev.location.split(',')[0] : '')) + '</span></div>' +
        '<button type="button" aria-label="Dismiss">✕</button>';
      banner.hidden = false;
      document.dispatchEvent(new CustomEvent('dashboard:duck'));
      chime();
      var text = ev.title + ', ' + (mins >= 120 ? 'at ' + U.clock(start) : 'in ' + mins + ' minutes') + (ev.location ? ', at ' + ev.location.split(',')[0] : '');
      if (ev.speak) setTimeout(function () { say(text); }, 900);
      setTimeout(function () { document.dispatchEvent(new CustomEvent('dashboard:unduck')); }, ev.speak ? 1500 + text.length * 80 : 1500);
    }
    function checkReminders() {
      var now = new Date(), seen = done(), changed = false;
      if (showing && (U.parse(showing.start) <= now)) { banner.hidden = true; showing = null; }
      Object.keys(seen).forEach(function (k) { if (seen[k] < now.getTime() - 86400000) { delete seen[k]; changed = true; } });
      if (!showing) {
        data.events.concat(data.next).some(function (ev) {
          if (!ev.remind || ev.allDay || U.hidden().indexOf(ev.calendarId) >= 0) return false;
          var start = U.parse(ev.start), key = ev.id;
          if (seen[key] || start <= now || start - now > ev.remind * 60000) return false;
          seen[key] = start.getTime(); changed = true; remind(ev); return true;
        });
      }
      if (changed) done(seen);
    }
    banner.addEventListener('click', function () { banner.hidden = true; showing = null; });
    window.CalendarReminders = { check: checkReminders, show: remind };

    // Events turn "Now" and then past as time goes by (checked once a minute).
    var lastMinute = -1;
    document.addEventListener('dashboard:tick', function (e) { var m = e.detail.getMinutes(); if (m !== lastMinute) { lastMinute = m; renderList(); checkReminders(); } });
    // Past midnight the server data is a day old: reload the card's data with the page's next refresh.
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
    var hidden = window.CalendarUi.hidden;
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
      var U = window.CalendarUi;
      return '<button type="button" class="calx-ev ' + U.phase(ev) + '" data-id="' + esc(ev.id) + '" style="--c:' + esc(ev.color) + '"><i></i>' + U.lead(ev) +
        '<span class="t">' + esc(timeLabel(ev)) + '</span><span><b>' + mark(ev.title, term) + U.badge(ev) + '</b>' +
        (ev.location ? '<small>' + mark(ev.location, term) + '</small>' : '') + '</span></button>';
    }

    function renderToggles() {
      var off = hidden();
      document.getElementById('calx-toggles').innerHTML = data.calendars.map(function (c) {
        return '<button type="button" class="calx-chip" data-cal="' + esc(c.id) + '" aria-pressed="' + (off.indexOf(c.id) < 0) + '"><i style="background:' + esc(c.color) + '"></i>' + esc((c.icon ? c.icon + ' ' : '') + c.name) + '</button>';
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
        '<article class="calx-detail" style="--c:' + esc(ev.color) + '"><h2>' + esc(window.CalendarUi.title(ev)) + '</h2><p class="calx-meta">' + esc(ev.calendar) + '</p><dl>' +
        '<dt>When</dt><dd>' + esc(when) + '</dd>' +
        (ev.location ? '<dt>Where</dt><dd>📍 ' + esc(ev.location) + '</dd>' : '') +
        (ev.notes ? '<dt>Notes</dt><dd>' + esc(ev.notes) + '</dd>' : '') +
        (ev.organizer ? '<dt>Organizer</dt><dd>' + esc(ev.organizer) + '</dd>' : '') +
        (ev.attendees.length ? '<dt>Guests</dt><dd>' + esc(ev.attendees.join(', ')) + '</dd>' : '') +
        (ev.url ? '<dt>Link</dt><dd class="calx-url">' + esc(ev.url) + '</dd>' : '') +
        (ev.remind ? '<dt>Reminder</dt><dd>' + esc(remindLabel(ev.remind)) + ' before' + (ev.speak ? ', spoken' : '') + '</dd>' : '') + '</dl></article>';
      side.scrollTop = 0;
    }
    function remindLabel(m) { return m >= 1440 ? (m / 1440) + (m === 1440 ? ' day' : ' days') : m >= 60 ? (m / 60) + (m === 60 ? ' hour' : ' hours') : m + ' minutes'; }
    function back() { if (detailFrom === 'search' && q.value.trim()) renderSearch(); else renderDay(); }
    function select(key) {
      selected = key; var d = parse(key);
      if (d.getMonth() !== month.getMonth() || d.getFullYear() !== month.getFullYear()) month = new Date(d.getFullYear(), d.getMonth(), 1);
      q.value = ''; clear.hidden = true; renderMonth(); renderDay();
    }
    function resetIdle() { clearTimeout(idleTimer); idleTimer = setTimeout(close, 2 * 60 * 1000); }
    function open(eventId, day) {
      overlay.hidden = false; resetIdle(); side.innerHTML = '<p class="cal-none">Loading…</p>';
      fetch(card.dataset.events, { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (d) {
          data = d; byDay = {};
          d.events.forEach(function (ev) { ev.days.forEach(function (k) { (byDay[k] = byDay[k] || []).push(ev); }); });
          document.getElementById('calx-sample').hidden = !d.sample;
          renderToggles();
          var target = eventId && d.events.filter(function (e) { return e.id === eventId; })[0];
          var start = (target ? target.days[0] : null) || day || d.today;
          month = parse(start); month = new Date(month.getFullYear(), month.getMonth(), 1);
          select(start);
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

    window.CalendarSheet = { open: open };
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
