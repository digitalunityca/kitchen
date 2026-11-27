{{-- Canadiens: on game days the game side of the weather card (refreshed every 2 minutes), a side-menu button and the stats sheet. --}}
<div class="habs" id="habs" hidden data-ticker="{{ route('modules.habs.ticker') }}" data-sheet="{{ route('modules.habs.sheet') }}" @if ($demo) data-demo="{{ $demo }}" @endif data-title="{{ $title }}">
  <template id="habs-face-html">@if ($today)@include('modules.habs.face', ['item' => $today])@endif</template>
</div>

<div class="habs-overlay" id="habs-overlay" hidden role="dialog" aria-modal="true" aria-label="{{ __('Canadiens scores and stats') }}">
  <div class="habs-sheet">
    <header class="habs-sheet-head">
      <span class="habs-sheet-title">
        <img class="habs-logo-light" src="https://assets.nhle.com/logos/nhl/svg/MTL_light.svg" alt="" width="40" height="40">
        <img class="habs-logo-dark" src="https://assets.nhle.com/logos/nhl/svg/MTL_dark.svg" alt="" width="40" height="40">
        Canadiens
      </span>
      <button type="button" class="habs-close" id="habs-close" aria-label="{{ __('Close') }}">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </header>
    <div class="habs-sheet-body" id="habs-sheet-body"><div class="habs-loading" role="status" aria-label="{{ __('Loading') }}"><span></span></div></div>
  </div>
</div>

@push('menu-items')
<button type="button" class="habs-open menu-button" id="habs-open" aria-label="{{ __('Canadiens scores and stats') }}">
  <img class="habs-logo-light" src="https://assets.nhle.com/logos/nhl/svg/MTL_light.svg" alt="" width="36" height="36">
  <img class="habs-logo-dark" src="https://assets.nhle.com/logos/nhl/svg/MTL_dark.svg" alt="" width="36" height="36">
</button>
@endpush

@push('styles')
<style>
  .habs-logo { display: inline-flex; width: 30px; height: 30px; flex: none; }
  .habs-logo img { width: 100%; height: 100%; object-fit: contain; }
  .habs-logo.big { width: 72px; height: 72px; }
  .habs-logo-dark, body.night .habs-logo-light { display: none; }
  body.night .habs-logo-dark { display: block; }

  /* Game days: the weather card flips between the game (front) and the weather (back). */
  .habs-flip { flex: 1; width: 100%; min-width: 0; height: 100%; align-self: stretch; perspective: 1600px; }
  .habs-flip-inner { position: relative; height: 100%; transform-style: preserve-3d; transition: transform .7s cubic-bezier(.3,.9,.3,1); }
  .habs-flip.turned .habs-flip-inner { transform: rotateY(180deg); }
  .habs-flip-front, .habs-flip-back { position: absolute; inset: 0; display: flex; -webkit-backface-visibility: hidden; backface-visibility: hidden; }
  .habs-flip-back { transform: rotateY(180deg); }
  .habs-flip-front > *, .habs-flip-back > * { flex: 1; min-width: 0; }
  .habs-face { cursor: pointer; }
  .habs-turn { display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 14px 0 12px; border-radius: 999px; background: var(--fill); color: var(--text); font-size: 15px; font-weight: 600; flex: none; }
  .habs-turn:active { transform: scale(.95); }
  .habs-face-main { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: 12px; }
  .habs-logo.face { width: 80px; height: 80px; justify-self: center; }
  .habs-face-mid { text-align: center; }
  .habs-face-score { white-space: nowrap; font-size: 60px; font-weight: 600; letter-spacing: -.03em; font-variant-numeric: tabular-nums; line-height: 1; }
  .habs-face-score span { color: var(--sub); margin: 0 .12em; font-weight: 400; }
  .habs-face-time { white-space: nowrap; font-size: 52px; font-weight: 600; letter-spacing: -.03em; font-variant-numeric: tabular-nums; line-height: 1; }
  .habs-face-time small { font-size: 22px; font-weight: 600; margin-left: 4px; color: var(--sub); }
  .habs-face-teams { margin: 0; text-align: center; font-size: 19px; font-weight: 600; }
  .habs-face-foot { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 6px 8px; border-top: 1px solid var(--line); padding-top: 14px; font-size: 16px; color: var(--sub); }
  .habs-sep { color: var(--sub); }
  .habs-live { display: inline-flex; align-items: center; gap: 6px; color: #FF3B30; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; }
  .habs-live i { width: 8px; height: 8px; border-radius: 999px; background: #FF3B30; animation: habs-blink 1.4s ease-in-out infinite; }
  .habs-result { font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 999px; color: #fff; }
  .habs-w { background: #34C759; } .habs-l { background: #8E8E93; }
  @keyframes habs-scroll { to { transform: translateX(-50%); } }
  @keyframes habs-blink { 50% { opacity: .3; } }

  .habs-open { width: 64px; height: 64px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--card); box-shadow: var(--shadow); transition: transform .15s ease; }
  .habs-open:active { transform: scale(.94); }
  .habs-open img { width: 42px; height: 42px; object-fit: contain; }

  /* The stats sheet over the dashboard. */
  .habs-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; padding: 32px 120px 32px 36px; animation: habs-fade .2s ease-out; }
  .habs-overlay[hidden] { display: none; }
  @keyframes habs-fade { from { opacity: 0; } }
  /* Opens at its full size straight away (whatever is loading or which tab is shown); content scrolls inside. */
  .habs-sheet { width: 100%; height: 100%; display: flex; flex-direction: column; overflow: hidden; background: var(--bg); border-radius: 28px; padding: 24px 28px 28px; box-shadow: 0 30px 80px rgba(0,0,0,.3); }
  .habs-sheet-body { flex: 1; min-height: 0; overflow: auto; }
  .habs-loading { height: 100%; display: flex; align-items: center; justify-content: center; }
  .habs-loading span { width: 36px; height: 36px; border-radius: 999px; border: 3px solid var(--line); border-top-color: var(--sub); animation: habs-spin .8s linear infinite; }
  @keyframes habs-spin { to { transform: rotate(360deg); } }
  body.night .habs-sheet { background: #0E0E10; box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 30px 80px rgba(0,0,0,.6); }
  .habs-sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .habs-sheet-title { display: flex; align-items: center; gap: 12px; font-size: 26px; font-weight: 600; letter-spacing: -.02em; }
  .habs-close { width: 48px; height: 48px; border-radius: 999px; background: var(--fill); display: flex; align-items: center; justify-content: center; }
  .habs-sheet-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 20px; }
  .habs-side { display: flex; flex-direction: column; gap: 20px; }
  .habs-panel { background: var(--card); border-radius: 22px; padding: 20px 22px; box-shadow: var(--shadow); }
  .habs-panel .label { margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
  .habs-scoreline { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px; margin: 4px 0 16px; }
  .habs-team { display: flex; flex-direction: column; align-items: center; gap: 6px; font-weight: 500; }
  .habs-big { font-size: 56px; font-weight: 300; letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
  .habs-big small { color: var(--sub); margin: 0 12px; font-size: 40px; }
  .habs-table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; margin-bottom: 14px; }
  .habs-table th { font-size: 12px; color: var(--sub); font-weight: 600; padding: 4px 6px; text-align: center; }
  .habs-table td { padding: 6px; text-align: center; border-top: 1px solid var(--line); }
  .habs-table td:first-child, .habs-table th:first-child { text-align: left; font-weight: 600; }
  .habs-goals { display: flex; flex-direction: column; gap: 2px; border-top: 1px solid var(--line); padding-top: 10px; }
  .habs-goal { display: grid; grid-template-columns: 92px 48px 1fr; gap: 8px; padding: 6px 0; font-size: 15px; }
  .habs-goal.mtl .habs-goal-team { color: #AF1E2D; font-weight: 700; }
  body.night .habs-goal.mtl .habs-goal-team { color: #FF6B6B; }
  .habs-goal small { display: block; color: var(--sub); font-size: 13px; }
  .habs-goal em, .habs-goalies em { font-style: normal; font-size: 11px; font-weight: 700; color: var(--accent); margin-left: 6px; }
  .habs-when, .habs-goal-team { color: var(--sub); font-variant-numeric: tabular-nums; }
  .habs-goalies { display: flex; flex-wrap: wrap; gap: 6px 18px; border-top: 1px solid var(--line); padding-top: 10px; margin-top: 6px; font-size: 14px; }
  .habs-record { margin: 0; font-size: 34px; font-weight: 300; letter-spacing: -.02em; }
  .habs-record small { font-size: 16px; color: var(--sub); font-weight: 500; }
  .habs-muted, .habs-empty { margin: 4px 0 0; color: var(--sub); font-size: 15px; }
  .habs-chips { display: flex; flex-wrap: wrap; gap: 8px; }
  .habs-chip { font-size: 14px; padding: 6px 10px; border-radius: 12px; background: var(--fill); font-variant-numeric: tabular-nums; }
  .habs-chip b { color: #fff; border-radius: 6px; padding: 1px 6px; margin-right: 4px; font-size: 12px; }
  .habs-chip.habs-w, .habs-chip.habs-l { background: var(--fill); }
  .habs-chip.habs-w b { background: #34C759; } .habs-chip.habs-l b { background: #8E8E93; }
  .habs-next { display: flex; align-items: center; gap: 12px; padding: 6px 0; }
  .habs-next small { display: block; color: var(--sub); font-size: 13px; }

  /* Tabs: Apple-style segmented control. */
  .habs-segmented { display: inline-flex; padding: 3px; border-radius: 12px; background: var(--fill); gap: 2px; margin-bottom: 16px; }
  .habs-segmented button { padding: 8px 22px; border-radius: 9px; font-size: 15px; font-weight: 500; color: var(--sub); }
  .habs-segmented button[aria-selected="true"] { background: var(--card); color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,.12); }
  .habs-segmented.habs-small { margin: 0; }
  .habs-segmented.habs-small button { padding: 6px 14px; font-size: 14px; }
  .habs-tab[hidden] { display: none; }
  .habs-tab[data-panel="game"] .habs-panel { max-width: none; }
  .habs-standings-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 12px; }
  .habs-standings { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; font-size: 15px; }
  .habs-standings[hidden] { display: none; }
  .habs-standings th { font-size: 12px; color: var(--sub); font-weight: 600; padding: 6px; text-align: center; }
  .habs-standings td { padding: 5px 6px; text-align: center; border-top: 1px solid var(--line); }
  .habs-standings td:first-child { color: var(--sub); width: 32px; }
  .habs-standings .habs-cell-logo { width: 34px; padding: 3px 0; }
  .habs-standings .habs-cell-logo .habs-logo { width: 28px; height: 28px; vertical-align: middle; }
  .habs-standings .habs-team-name { text-align: left; font-weight: 500; }
  .habs-standings tr.habs-us td { background: rgba(175, 30, 45, .08); }
  body.night .habs-standings tr.habs-us td { background: rgba(255, 107, 107, .14); }
  .habs-standings tr.habs-us .habs-team-name { color: #AF1E2D; font-weight: 700; }
  body.night .habs-standings tr.habs-us .habs-team-name { color: #FF6B6B; }
  .habs-standings tr.habs-cut td { border-bottom: 2px dashed var(--accent); }
  .habs-cut-note { font-size: 13px; margin-top: 10px; }
  .habs-even { grid-template-columns: 1fr 1fr; }
  .habs-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-top: 1px solid var(--line); }
  .habs-row:first-of-type { border-top: 0; }
  .habs-date { width: 92px; color: var(--sub); font-size: 14px; font-variant-numeric: tabular-nums; }
  .habs-row-main { flex: 1; font-weight: 500; }
  .habs-row-main small { display: block; color: var(--sub); font-weight: 400; font-size: 13px; }

  @media (prefers-reduced-motion: reduce) {
    .habs-flip-inner { transition: none; }
    .habs-live i { animation: none; }
    .habs-overlay { animation: none; }
    .habs-loading span { animation-duration: 2s; }
  }
</style>
@endpush

@push('scripts')
<script>
// Canadiens strip, action bar note and stats sheet.
(function () {
  var root = document.getElementById('habs'), overlay = document.getElementById('habs-overlay'), body = document.getElementById('habs-sheet-body');
  var IDLE_MS = 2 * 60 * 1000, idleTimer = 0;
  var demo = root.dataset.demo ? '?habs=' + encodeURIComponent(root.dataset.demo) : '';

  function setNote(text) { if (window.Dashboard && Dashboard.setTitlePart) Dashboard.setTitlePart('habs', text || ''); }
  setNote(root.dataset.title);

  // Game days: wrap the weather card in a flip card with the game in front. "Turn" flips
  // it; it turns back to the game after 2 minutes, and on non-game days it's just the weather.
  var slot = document.querySelector('.slot[data-module="weather"]'), flip = null, turnBack = 0;
  var turnSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>';
  function setFace(html) {
    if (!slot) return;
    if (!html) {
      if (flip) { var weather = flip.querySelector('.habs-flip-back > *'); var btn = weather.querySelector('[data-habs-turn]'); if (btn) btn.remove(); slot.replaceChildren(weather); flip = null; }
      return;
    }
    if (!flip) {
      var card = slot.firstElementChild;
      flip = document.createElement('div'); flip.className = 'habs-flip';
      flip.innerHTML = '<div class="habs-flip-inner"><div class="habs-flip-front"></div><div class="habs-flip-back"></div></div>';
      flip.querySelector('.habs-flip-back').appendChild(card);
      var back = document.createElement('button'); back.type = 'button'; back.className = 'habs-turn'; back.setAttribute('data-habs-turn', ''); back.setAttribute('aria-label', 'Show the Canadiens game'); back.innerHTML = turnSvg + 'Turn';
      var row = card.querySelector('.row'); (row || card).appendChild(back);
      slot.replaceChildren(flip);
    }
    var front = flip.querySelector('.habs-flip-front');
    front.innerHTML = '<section class="card">' + html + '</section>';
  }
  function turn() {
    if (!flip) return;
    var turned = flip.classList.toggle('turned');
    clearTimeout(turnBack);
    if (turned) turnBack = setTimeout(function () { flip && flip.classList.remove('turned'); }, IDLE_MS);
  }
  setFace(document.getElementById('habs-face-html').innerHTML.trim());

  function refresh() {
    fetch(root.dataset.ticker + demo, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (t) { setFace(t.face); setNote(t.title); })
      .catch(function () {});
  }
  setInterval(refresh, 2 * 60 * 1000);

  function resetIdle() { clearTimeout(idleTimer); idleTimer = setTimeout(close, IDLE_MS); }
  function open() {
    overlay.hidden = false; resetIdle();
    body.innerHTML = '<div class="habs-loading" role="status" aria-label="' + __('Loading') + '"><span></span></div>';
    fetch(root.dataset.sheet, { cache: 'no-store' })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then(function (html) { body.innerHTML = html; setupTabs(); })
      .catch(function () { body.innerHTML = '<p class="habs-empty">' + __('Can’t reach the NHL right now.') + '</p>'; });
  }
  function close() { overlay.hidden = true; clearTimeout(idleTimer); }

  // Tabs remember the last choice for this browser session; otherwise Game on
  // game days and Standings on other days (data-default from the server).
  function remember(key, value) { try { if (value === undefined) return sessionStorage.getItem(key); sessionStorage.setItem(key, value); } catch (e) { return null; } }
  function select(group, attr, value) {
    group.querySelectorAll('[' + attr + ']').forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute(attr) === value ? 'true' : 'false'); });
  }
  function showTab(name) {
    var tabs = body.querySelector('.habs-tabs'); if (!tabs) return;
    select(tabs.querySelector('.habs-segmented'), 'data-tab', name);
    tabs.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    remember('habs-tab', name);
  }
  function showTable(scope) {
    var head = body.querySelector('.habs-standings-head'); if (!head) return;
    select(head, 'data-table', scope);
    body.querySelectorAll('.habs-standings').forEach(function (t) { t.hidden = t.dataset.scope !== scope; });
    remember('habs-table', scope);
  }
  function setupTabs() {
    var tabs = body.querySelector('.habs-tabs'); if (!tabs) return;
    showTab(remember('habs-tab') || tabs.dataset.default);
    showTable(remember('habs-table') || 'division');
  }
  body.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-tab]'), table = e.target.closest('[data-table]');
    if (tab) showTab(tab.dataset.tab);
    if (table) showTable(table.dataset.table);
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-habs-turn]')) { turn(); return; }
    if (e.target.closest('.habs-face') || e.target.closest('#habs-open')) open();
  });
  document.getElementById('habs-close').addEventListener('click', close);
  document.addEventListener('dashboard:home', close);
  overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
  overlay.addEventListener('pointerdown', resetIdle);
  overlay.addEventListener('scroll', resetIdle, true);
})();
</script>
@endpush
