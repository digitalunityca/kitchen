{{-- Kids' homework: an avatar button per kid in the side menu, and the kid's own area in an overlay (screens from KidController). --}}
@if ($kids !== [])
@push('menu-items')
  @foreach ($kids as $kid)
    <button type="button" class="kid-btn{{ $kid['photo'] ? ' has-photo' : '' }}" data-kid="{{ $kid['id'] }}" style="--kid: {{ $kid['color'] }}" aria-label="{{ $kid['name'] }}’s homework">@if ($kid['photo'])<img src="{{ $kid['photo'] }}" alt="" width="56" height="56">@else{{ $kid['badge'] }}@endif</button>
  @endforeach
  <span class="menu-divider" aria-hidden="true"></span>
@endpush

<div class="kid-overlay" id="kid-overlay" hidden role="dialog" aria-modal="true" data-csrf="{{ csrf_token() }}">
  @foreach ($kids as $kid)
    <section class="kid-sheet" data-kid-sheet="{{ $kid['id'] }}" style="--kid: {{ $kid['color'] }}; --kid-ink: {{ \App\Modules\Homework\HomeworkModule::ink($kid['color']) }}" hidden aria-label="{{ $kid['name'] }}’s homework"
      data-lock="{{ route('modules.homework.lock', $kid['id']) }}">
      <button type="button" class="kid-close" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <div class="kid-body" data-src="{{ route('modules.homework.sheet', $kid['id']) }}"><p class="kz-loading">Loading…</p></div>
    </section>
  @endforeach
</div>

@push('styles')
<style>
  .kid-btn {
    width: 56px; height: 56px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 600; color: #fff; background: var(--kid); box-shadow: 0 2px 8px rgba(0,0,0,.12);
    transition: transform .15s ease;
  }
  .kid-btn:active { transform: scale(.94); }
  .kid-btn.has-photo { padding: 0; overflow: hidden; box-shadow: 0 0 0 3px var(--kid), 0 2px 8px rgba(0,0,0,.12); }
  .kid-btn img { width: 100%; height: 100%; object-fit: cover; border-radius: 999px; display: block; }
  .kid-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; padding: 16px; }
  .kid-overlay[hidden], .kid-sheet[hidden] { display: none; }
  .kid-sheet { position: relative; width: min(1180px, 100%); height: 100%; background: var(--bg); border-radius: 28px; padding: 20px 24px; box-shadow: 0 30px 80px rgba(0,0,0,.3); display: flex; flex-direction: column; }
  body.night .kid-sheet { background: #0E0E10; box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
  .kid-close { position: absolute; top: 18px; right: 20px; z-index: 3; width: 52px; height: 52px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); display: flex; align-items: center; justify-content: center; color: var(--text); }
  .kid-close:active { transform: scale(.94); }
  .kid-body { flex: 1; min-height: 0; display: flex; flex-direction: column; }
  .kz-loading { color: var(--sub); text-align: center; padding: 30px 0; margin: auto; }

  /* Every screen */
  .kz { flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 14px; position: relative; }
  .kz-icon { flex: none; display: block; }
  .kz-head { display: flex; align-items: center; gap: 14px; min-height: 56px; padding-right: 72px; }
  .kz-head h2 { flex: 1; margin: 0; font-size: 28px; font-weight: 600; letter-spacing: -.02em; }
  .kz-avatar { width: 52px; height: 52px; border-radius: 999px; flex: none; display: flex; align-items: center; justify-content: center; background: var(--kid); color: #fff; font-size: 26px; font-weight: 600; }
  .kz-avatar.big { width: 160px; height: 160px; font-size: 64px; box-shadow: 0 8px 24px rgba(0,0,0,.14); }
  img.kz-avatar { object-fit: cover; background: var(--fill); border: 3px solid var(--kid); }
  img.kz-avatar.big { border-width: 5px; }
  .kz-back { width: 52px; height: 52px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); color: var(--text); display: flex; align-items: center; justify-content: center; flex: none; }
  .kz-points { display: inline-flex; align-items: center; gap: 6px; background: var(--card); box-shadow: var(--shadow); border-radius: 999px; padding: 10px 16px 10px 12px; color: var(--kid-ink); }
  body.night .kz-points { color: var(--kid); }
  .kz-points b { font-size: 20px; font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums; }
  .kz-h { margin: 6px 0 0; font-size: 20px; font-weight: 600; letter-spacing: -.01em; }
  .kz-hint, .kz-quiet { margin: 0; color: var(--sub); font-size: 17px; }
  .kz-quiet b { color: var(--text); font-weight: 600; }
  .kz-sub { margin: 0; color: var(--sub); font-size: 16px; }
  .kz-error { margin: 0; color: #D70015; font-size: 17px; font-weight: 500; }
  body.night .kz-error { color: #FF6961; }
  .kz-pick[hidden], .kz-error[hidden], [data-done][hidden], .kz-parent[hidden], .kz-choice-count[hidden], [data-decision][hidden] { display: none; }
  .kz-scroll { overflow-y: auto; min-height: 0; flex: 1; overscroll-behavior: contain; }
  .kz-btn { flex: none; min-height: 52px; padding: 0 22px; border-radius: 16px; background: var(--kid-ink); color: #fff; font-size: 18px; font-weight: 600; }
  .kz-btn.ghost { background: var(--fill); color: var(--text); }
  .kz-btn:active, .kz-tile:active, .kz-choice:active, .kz-print:active, .kz-keys button:active, .kz-back:active { transform: scale(.97); }
  .kz button { transition: transform .12s cubic-bezier(.2,.8,.2,1), background-color .2s ease, opacity .2s ease; }
  .kz-empty { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; padding: 24px; }
  .kz-empty-icon, .kz-done-icon { width: 96px; height: 96px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--card); box-shadow: var(--shadow); color: var(--kid-ink); margin-bottom: 6px; }
  body.night .kz-empty-icon, body.night .kz-done-icon { color: var(--kid); }
  .kz-empty b { font-size: 24px; font-weight: 600; }
  .kz-empty p { margin: 0; color: var(--sub); font-size: 18px; }

  /* PIN */
  .kz-pin { flex-direction: row; align-items: center; justify-content: center; gap: 72px; }
  .kz-pin-who { display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; max-width: 360px; }
  .kz-pin-who h2 { margin: 6px 0 0; font-size: 32px; font-weight: 600; letter-spacing: -.02em; }
  .kz-pin-who p { margin: 0; color: var(--sub); font-size: 19px; line-height: 1.4; }
  .kz-pin-who .kz-error { color: #D70015; min-height: 24px; }
  .kz-pad { display: flex; flex-direction: column; align-items: center; gap: 18px; }
  .kz-dots { display: flex; gap: 16px; height: 18px; align-items: center; justify-content: center; }
  .kz-dots i { width: 16px; height: 16px; border-radius: 999px; border: 2px solid var(--sub); transition: background-color .15s ease, border-color .15s ease, transform .15s ease; }
  .kz-dots i.on { background: var(--text); border-color: var(--text); transform: scale(1.1); }
  .kz-keys { display: grid; grid-template-columns: repeat(3, 84px); gap: 14px; }
  .kz-keys button { height: 84px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); color: var(--text); font-size: 32px; font-weight: 500; display: flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
  .kz-keys .kz-key-soft { background: transparent; box-shadow: none; color: var(--sub); }
  .kz-keys .kz-key-ok { background: var(--kid-ink); color: #fff; }
  .kz-keys .kz-key-ok:disabled { background: var(--fill); color: var(--sub); box-shadow: none; }
  .kz-keys button:disabled { cursor: default; }
  .kz-keys.locked button { opacity: .4; }
  @keyframes kz-shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-12px); } 40% { transform: translateX(10px); } 60% { transform: translateX(-6px); } 80% { transform: translateX(4px); } }
  .kz-dots.shake { animation: kz-shake .4s cubic-bezier(.36,.07,.19,.97); }
  .kz-dots.shake i { border-color: #FF3B30; }

  /* Home */
  .kz-home-grid { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(280px, 1fr) 2fr; gap: 18px; }
  .kz-score { background: var(--card); box-shadow: var(--shadow); border-radius: 26px; padding: 30px 28px; display: flex; flex-direction: column; justify-content: center; }
  .kz-score-num { font-size: 112px; line-height: .95; font-weight: 600; letter-spacing: -.04em; font-variant-numeric: tabular-nums; }
  .kz-score-label { font-size: 22px; color: var(--sub); font-weight: 500; margin-top: 4px; }
  .kz-today { margin-top: 30px; padding-top: 22px; border-top: 1px solid var(--line); display: flex; flex-direction: column; gap: 10px; }
  .kz-today p { margin: 0; font-size: 19px; }
  .kz-today .kz-sub { font-size: 16px; }
  .kz-today b { font-weight: 600; }
  .kz-steps { display: flex; gap: 6px; }
  .kz-steps i { flex: 1; height: 10px; border-radius: 99px; background: var(--fill); }
  .kz-steps i.on { background: var(--kid); }
  .kz-tiles { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: 18px; }
  .kz-tile { background: var(--card); box-shadow: var(--shadow); border-radius: 26px; padding: 24px; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-end; gap: 4px; text-align: left; color: var(--text); }
  .kz-tile-icon { width: 64px; height: 64px; border-radius: 20px; background: var(--kid); color: #fff; display: flex; align-items: center; justify-content: center; margin-bottom: auto; }
  .kz-tile b { font-size: 26px; font-weight: 600; letter-spacing: -.02em; }
  .kz-tile small { font-size: 17px; color: var(--sub); }

  /* Homework */
  .kz-pick { flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 14px; }
  .kz-choices { flex: 1; min-height: 0; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(196px, 1fr)); grid-auto-rows: minmax(230px, auto); gap: 14px; align-content: start; padding: 2px; }
  .kz-choice { position: relative; background: var(--card); box-shadow: var(--shadow); border-radius: 24px; padding: 20px; display: flex; flex-direction: column; align-items: flex-start; gap: 2px; text-align: left; color: var(--text); border: 3px solid transparent; }
  .kz-choice-icon { width: 68px; height: 68px; border-radius: 20px; background: var(--fill); color: var(--text); display: flex; align-items: center; justify-content: center; margin-bottom: auto; transition: background-color .2s ease, color .2s ease; }
  .kz-choice b { font-size: 24px; font-weight: 600; margin-top: 14px; }
  .kz-choice small { font-size: 16px; color: var(--sub); }
  .kz-choice .kz-choice-left { font-size: 15px; }
  .kz-choice[aria-pressed="true"] { border-color: var(--kid-ink); }
  body.night .kz-choice[aria-pressed="true"] { border-color: var(--kid); }
  .kz-choice[aria-pressed="true"] .kz-choice-icon { background: var(--kid-ink); color: #fff; }
  .kz-choice:disabled { opacity: .45; box-shadow: none; }
  .kz-choice-wrap { position: relative; display: flex; }
  .kz-choice-wrap .kz-choice { flex: 1; }
  .kz-minus { position: absolute; right: 14px; bottom: 14px; width: 52px; height: 52px; border-radius: 999px; background: var(--fill); color: var(--text); font-size: 30px; font-weight: 500; line-height: 1; display: flex; align-items: center; justify-content: center; }
  .kz-minus[hidden] { display: none; }
  .kz-minus:active { transform: scale(.94); }
  .kz-choice-count { position: absolute; top: 16px; right: 16px; min-width: 40px; height: 40px; padding: 0 10px; border-radius: 999px; background: var(--kid-ink); color: #fff; font-size: 20px; font-weight: 600; display: flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
  .kz-printbar { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; background: var(--card); box-shadow: var(--shadow); border-radius: 24px; padding: 14px 14px 14px 24px; }
  .kz-printsum { margin: 0; font-size: 19px; color: var(--sub); }
  .kz-printsum b { color: var(--text); font-weight: 600; }
  .kz-printbar .kz-error { flex-basis: 100%; order: 3; }
  .kz-print { margin-left: auto; min-height: 64px; padding: 0 30px; border-radius: 20px; background: var(--kid-ink); color: #fff; font-size: 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 12px; }
  .kz-print:disabled { background: var(--fill); color: var(--sub); }
  .kz-print.busy { opacity: .85; }
  .kz-print.busy .kz-icon { animation: kz-pulse 1.2s ease-in-out infinite; }
  @keyframes kz-pulse { 50% { transform: translateY(-3px); } }
  .kz-done { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; max-width: 520px; outline: none; animation: kz-in .35s cubic-bezier(.2,.8,.2,1); }
  .kz-done b { font-size: 30px; font-weight: 600; letter-spacing: -.02em; }
  .kz-done p { margin: 0 0 10px; font-size: 19px; color: var(--sub); line-height: 1.45; }
  .kz-done .kz-print { margin-left: 0; }
  @keyframes kz-in { from { opacity: 0; transform: translateY(12px) scale(.98); } }

  /* Rewards */
  .kz-toast { margin: 0; align-self: flex-start; background: rgba(52,199,89,.14); color: #1E7A34; border-radius: 14px; padding: 10px 16px; font-size: 18px; font-weight: 600; animation: kz-in .35s cubic-bezier(.2,.8,.2,1); }
  body.night .kz-toast { color: #4CD964; }
  .kz-rewards-list { list-style: none; margin: 10px 0 18px; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 12px; }
  .kz-reward { background: var(--card); box-shadow: var(--shadow); border-radius: 20px; padding: 16px 16px 16px 20px; display: flex; align-items: center; gap: 14px; min-height: 84px; border: 2px solid transparent; }
  .kz-reward-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
  .kz-reward-text b { font-size: 19px; font-weight: 600; }
  .kz-reward-text small { font-size: 15px; color: var(--sub); }
  .kz-reward.is-ready { border-color: #34C759; }
  .kz-reward.is-ready small { color: #1E7A34; font-weight: 500; }
  body.night .kz-reward.is-ready small { color: #4CD964; }
  .kz-reward.is-locked, .kz-reward.is-reach { opacity: .5; box-shadow: none; background: var(--fill); }
  .kz-hint b { color: var(--text); font-weight: 600; }
  .kz-cost { flex: none; font-size: 22px; font-weight: 600; font-variant-numeric: tabular-nums; padding-right: 6px; }
  .kz-bar { display: block; height: 8px; border-radius: 99px; background: var(--fill); margin-top: 6px; overflow: hidden; }
  .kz-bar i { display: block; height: 100%; background: var(--kid); border-radius: 99px; }

  /* History */
  .kz-cols { flex: 1; min-height: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
  .kz-col { background: var(--card); box-shadow: var(--shadow); border-radius: 24px; padding: 20px 22px; display: flex; flex-direction: column; gap: 10px; min-height: 0; }
  .kz-col .kz-h { margin: 0; }
  .kz-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
  .kz-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-top: 1px solid var(--line); }
  .kz-row:first-child { border-top: 0; }
  .kz-row-icon { width: 44px; height: 44px; border-radius: 14px; background: var(--fill); display: flex; align-items: center; justify-content: center; flex: none; }
  .kz-row-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
  .kz-row-text b { font-size: 19px; font-weight: 600; }
  .kz-row-text small { font-size: 15px; color: var(--sub); }
  .kz-day { margin: 14px 0 2px; font-size: 15px; font-weight: 600; color: var(--sub); }
  .kz-day:first-child { margin-top: 0; }
  .kz-line { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; font-size: 17px; border-top: 1px solid var(--line); }
  .kz-line:first-child { border-top: 0; }
  .kz-line b { font-weight: 600; font-variant-numeric: tabular-nums; }
  .kz-line .plus { color: #1E7A34; }
  body.night .kz-line .plus { color: #4CD964; }
  .kz-news-list:empty { display: none; }
  .kz-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .kz-chip { min-height: 48px; padding: 0 18px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); color: var(--text); font-size: 17px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
  .kz-chip span { min-width: 26px; height: 26px; padding: 0 7px; border-radius: 999px; background: var(--fill); font-size: 14px; display: inline-flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
  .kz-chip[aria-pressed="true"] { background: var(--kid-ink); color: #fff; }
  .kz-chip[aria-pressed="true"] span { background: rgba(255,255,255,.25); }
  .kz-week { margin-left: auto; }
  .kz-history-list { background: var(--card); box-shadow: var(--shadow); border-radius: 24px; padding: 6px 22px 16px; }
  .kz-history .kz-day { display: flex; justify-content: space-between; align-items: baseline; margin: 16px 0 2px; font-size: 16px; }
  .kz-day b { font-variant-numeric: tabular-nums; }
  .kz-day .plus, .kz-row-points.plus { color: #1E7A34; }
  .kz-day .minus, .kz-row-points.minus { color: var(--sub); }
  .kz-day-group[hidden], .kz-row[hidden], .kz-viewer[hidden], .kz-filter-empty[hidden] { display: none; }
  .kz-row { min-height: 72px; }
  .kz-row-points { font-size: 18px; font-variant-numeric: tabular-nums; padding-right: 4px; }
  .kz-no { display: inline-block; margin-right: 10px; padding: 1px 8px; border: 2px solid var(--text); border-radius: 8px; font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; vertical-align: 2px; }
  .kz-badge { flex: none; border-radius: 999px; padding: 6px 12px; font-size: 14px; font-weight: 600; background: var(--fill); color: var(--sub); }
  .kz-badge.is-check { background: rgba(255,149,0,.16); color: #A65A00; }
  .kz-badge.is-done { background: rgba(52,199,89,.16); color: #1E7A34; }
  body.night .kz-badge.is-check { color: #FFB340; }
  body.night .kz-badge.is-done { color: #4CD964; }
  .kz-approved { flex: none; display: inline-flex; align-items: center; gap: 6px; min-height: 48px; padding: 0 16px; border-radius: 14px; background: rgba(52,199,89,.14); color: #1E7A34; font-size: 16px; font-weight: 600; font-variant-numeric: tabular-nums; }
  body.night .kz-approved { color: #4CD964; }
  .kz-row.is-done .kz-row-icon { background: #34C759; color: #fff; }
  .kz-row.is-check .kz-row-icon { background: rgba(255,149,0,.18); color: #A65A00; }
  .kz-row.is-missed .kz-row-text b { color: var(--sub); text-decoration: line-through; text-decoration-thickness: 1.5px; }
  .kz-row-actions { display: flex; gap: 8px; flex: none; }
  .kz-row-actions .kz-btn { min-height: 48px; padding: 0 16px; font-size: 16px; }
  .kz-viewer { position: absolute; inset: -20px -24px; z-index: 5; border-radius: 28px; overflow: hidden; background: var(--bg); display: flex; flex-direction: column; animation: kz-fade .2s ease; }
  .kz-viewer-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px 10px 26px; }
  .kz-viewer-bar b { font-size: 22px; font-weight: 600; }
  .kz-viewer iframe { flex: 1; border: 0; width: 100%; background: #f2f2f7; }

  /* Parent check */
  .kz-parent { position: absolute; inset: -20px -24px; border-radius: 28px; background: rgba(0,0,0,.32); display: flex; align-items: center; justify-content: center; z-index: 4; animation: kz-fade .2s ease; }
  @keyframes kz-fade { from { opacity: 0; } }
  .kz-parent-card { background: var(--bg); border-radius: 28px; padding: 22px 30px 20px; display: flex; flex-direction: column; align-items: center; gap: 10px; box-shadow: 0 24px 60px rgba(0,0,0,.3); animation: kz-in .25s cubic-bezier(.2,.8,.2,1); }
  body.night .kz-parent-card { background: #1C1C1E; }
  .kz-parent-lock { color: var(--sub); }
  .kz-parent-card h3 { margin: 0; font-size: 21px; font-weight: 600; text-align: center; max-width: 340px; }
  .kz-parent-card .kz-keys { grid-template-columns: repeat(3, 72px); gap: 10px; }
  .kz-parent-card .kz-keys button { height: 72px; font-size: 28px; }
  .kz-parent-actions { display: flex; gap: 10px; margin-top: 4px; }

  .kz button:focus-visible, .kid-close:focus-visible { outline: 3px solid var(--kid-ink); outline-offset: 3px; }
  @media (prefers-reduced-motion: reduce) { .kz *, .kz { animation: none !important; transition: none !important; } }
</style>
@endpush

@push('scripts')
<script>
  (function () {
    var overlay = document.getElementById('kid-overlay'), csrf = overlay.dataset.csrf, idle = 0;

    function sheet() { return overlay.querySelector('[data-kid-sheet]:not([hidden])'); }
    function body() { var s = sheet(); return s && s.querySelector('.kid-body'); }
    function post(url, data) {
      var form = new FormData(); Object.keys(data || {}).forEach(function (k) { form.append(k, data[k]); });
      return fetch(url, { method: 'POST', body: form, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json, text/html' } });
    }
    function show(html) {
      var el = body(); if (!el) { return; }
      el.innerHTML = html;
      var screen = el.querySelector('[data-screen]');
      if (screen && screen.dataset.screen === 'homework') { tally(screen); }
      if (screen && screen.dataset.screen === 'history') { filter(screen, screen.dataset.filter); }
    }
    function go(screen) {
      var el = body(); if (!el) { return; }
      var url = el.dataset.src + (screen ? '?screen=' + screen : '');
      return fetch(url, { headers: { 'Accept': 'text/html' } }).then(function (r) { return r.text(); }).then(show)
        .catch(function () { el.innerHTML = '<p class="kz-loading">Couldn’t load. Check the Wi-Fi and try again.</p>'; });
    }
    function open(id) {
      overlay.querySelectorAll('[data-kid-sheet]').forEach(function (s) { s.hidden = s.dataset.kidSheet !== id; });
      overlay.hidden = false; body().innerHTML = '<p class="kz-loading">Loading…</p>'; poke(); go();
    }
    /** ✕, Home, a tap outside or 5 minutes without a tap: close and sign the kid out. */
    function close() {
      var s = sheet(); clearTimeout(idle);
      if (overlay.hidden) { return; }
      overlay.hidden = true;
      if (s) { post(s.dataset.lock).catch(function () {}); s.querySelector('.kid-body').innerHTML = ''; }
    }
    function poke() { clearTimeout(idle); idle = setTimeout(close, 5 * 60 * 1000); }

    /* Keypads (the kid's PIN and the parent check) */
    function pad(el) { return el.closest('[data-pad]'); }
    function digits(p) { return p.dataset.pin || ''; }
    function setPin(p, value) {
      p.dataset.pin = value;
      var dots = p.querySelector('[data-dots]');
      while (dots.children.length < Math.max(4, value.length)) { dots.appendChild(document.createElement('i')); }
      while (dots.children.length > Math.max(4, value.length)) { dots.lastElementChild.remove(); }
      Array.prototype.forEach.call(dots.children, function (dot, i) { dot.classList.toggle('on', i < value.length); });
      var ok = p.querySelector('[data-key="ok"]'); ok.disabled = value.length < 4;
    }
    function wrong(p, message, wait) {
      var dots = p.querySelector('[data-dots]'), err = p.querySelector('[data-error]'), keys = p.querySelector('.kz-keys');
      setPin(p, '');
      dots.classList.remove('shake'); void dots.offsetWidth; dots.classList.add('shake');
      err.hidden = false; err.textContent = message;
      if (wait) {
        keys.classList.add('locked'); keys.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
        var left = wait, timer = setInterval(function () {
          left--; err.textContent = 'Too many tries. Wait ' + left + ' seconds.';
          if (left <= 0 || !keys.isConnected) {
            clearInterval(timer); keys.classList.remove('locked'); err.hidden = true;
            keys.querySelectorAll('button').forEach(function (b) { b.disabled = false; }); setPin(p, '');
          }
        }, 1000);
      }
    }
    function key(p, k) {
      var value = digits(p);
      if (k === 'del') { setPin(p, value.slice(0, -1)); return; }
      if (k === 'ok') { submit(p); return; }
      var max = +(p.dataset.max || 8);
      if (value.length >= max) { return; }
      setPin(p, value + k); p.querySelector('[data-error]').hidden = true;
      // A kid's PIN is exactly 4 digits: check it as soon as the 4th is typed.
      if (p.dataset.screen === 'pin' && max === 4 && value.length + 1 === 4) { submit(p); }
    }
    function submit(p) {
      if (digits(p).length < 4 || p.dataset.busy) { return; }
      if (p.dataset.screen === 'pin') { unlock(p); } else { decide(p, p.dataset.decision || 'approve'); }
    }
    function unlock(p) {
      p.dataset.busy = '1';
      post(p.dataset.submit, { pin: digits(p) }).then(function (r) {
        if (r.ok) { return r.text().then(show); }
        return r.json().then(function (j) { wrong(p, j.error || 'That’s not the right PIN.', j.wait); });
      }).catch(function () { wrong(p, 'Couldn’t check. Try again.'); })
        .finally(function () { delete p.dataset.busy; });
    }

    /* Parent check over Rewards and History */
    function openParent(btn) {
      var screen = btn.closest('[data-screen]'), p = screen.querySelector('[data-parent]');
      p.dataset.url = btn.dataset.parentOpen;
      p.querySelector('[data-parent-title]').textContent = btn.dataset.parentTitle || 'Parent check';
      p.dataset.decision = btn.dataset.parentDecision || 'approve';
      p.querySelector('[data-error]').hidden = true;
      setPin(p, ''); p.hidden = false;
    }
    function decide(p, decision) {
      if (decision === 'cancel') { p.hidden = true; setPin(p, ''); return; }
      if (digits(p).length < 4) { wrong(p, 'Type the parent PIN first.'); return; }
      p.dataset.busy = '1';
      post(p.dataset.url, { pin: digits(p), decision: decision }).then(function (r) {
        if (r.ok) { return r.text().then(show); }
        return r.json().then(function (j) {
          if (r.status === 401) { return go(); }
          wrong(p, j.error || 'Wrong parent PIN.');
        });
      }).catch(function () { wrong(p, 'Something went wrong. Try again.'); })
        .finally(function () { delete p.dataset.busy; });
    }

    /* Homework: pick, then Print all */
    function tally(screen) {
      var sheets = 0, points = 0;
      screen.querySelectorAll('[data-choice]').forEach(function (c) {
        var n = +(c.dataset.count || 0), badge = c.querySelector('[data-count]');
        sheets += n; points += n * +c.dataset.points;
        c.setAttribute('aria-pressed', n > 0 ? 'true' : 'false');
        badge.hidden = n === 0; badge.textContent = '×' + n;
        var minus = c.parentNode.querySelector('[data-minus]'); if (minus) { minus.hidden = n === 0; }
      });
      var sum = screen.querySelector('[data-summary]'), btn = screen.querySelector('[data-print-all]');
      if (!sum) { return; }
      sum.innerHTML = sheets === 0 ? 'Pick at least one sheet' : '<b>' + sheets + ' ' + (sheets === 1 ? 'sheet' : 'sheets') + '</b> · +' + points + ' points once checked';
      btn.disabled = sheets === 0 || !!screen.dataset.busy;
    }
    function total(screen) { var t = 0; screen.querySelectorAll('[data-choice]').forEach(function (c) { t += +(c.dataset.count || 0); }); return t; }
    function pick(c, step) {
      var screen = c.closest('[data-screen]'); if (screen.dataset.busy) { return; }
      if (step > 0 && total(screen) >= +screen.dataset.max) { return; }
      c.dataset.count = Math.max(0, +(c.dataset.count || 0) + step); tally(screen);
    }
    function printAll(screen) {
      var btn = screen.querySelector('[data-print-all]'), label = btn.querySelector('[data-print-label]'), err = screen.querySelector('[data-error]'), data = {};
      screen.querySelectorAll('[data-choice]').forEach(function (c) { if (+(c.dataset.count || 0) > 0) { data['counts[' + c.dataset.choice + ']'] = c.dataset.count; } });
      screen.dataset.busy = '1'; btn.disabled = true; btn.classList.add('busy'); err.hidden = true;
      label.textContent = 'Making your sheets…';
      post(screen.dataset.print, data).then(function (r) {
        return r.json().then(function (j) {
          if (r.status === 401) { return go(); }
          if (!r.ok) { throw new Error(j.error || 'Couldn’t print. Try again.'); }
          screen.querySelector('[data-pick]').hidden = true;
          var done = screen.querySelector('[data-done]'); done.hidden = false; done.focus();
        });
      }).catch(function (e) {
        err.hidden = false; err.textContent = e.message && e.message.indexOf('Failed to fetch') === -1 ? e.message : 'Couldn’t reach the hub. Try again.';
      }).finally(function () {
        delete screen.dataset.busy; btn.classList.remove('busy'); label.textContent = 'Print all'; tally(screen);
      });
    }

    /* History: status filter and the worksheet preview */
    function filter(screen, key) {
      screen.dataset.filter = key;
      screen.querySelectorAll('[data-filter-chip]').forEach(function (c) { c.setAttribute('aria-pressed', c.dataset.filterChip === key ? 'true' : 'false'); });
      var any = false;
      screen.querySelectorAll('[data-day]').forEach(function (day) {
        var shown = 0;
        day.querySelectorAll('[data-status]').forEach(function (row) { row.hidden = key !== 'all' && row.dataset.status !== key; if (!row.hidden) { shown++; } });
        day.hidden = shown === 0; any = any || shown > 0;
      });
      var empty = screen.querySelector('[data-filter-empty]'); if (empty) { empty.hidden = any || !screen.querySelector('[data-day]'); }
    }
    function preview(btn) {
      var v = btn.closest('[data-screen]').querySelector('[data-viewer]');
      v.querySelector('[data-viewer-title]').textContent = btn.dataset.previewTitle;
      v.querySelector('[data-viewer-frame]').src = btn.dataset.preview; v.hidden = false;
    }

    document.addEventListener('click', function (e) { var b = e.target.closest('[data-kid]'); if (b) { open(b.dataset.kid); } });
    overlay.addEventListener('click', function (e) {
      poke();
      if (e.target === overlay || e.target.closest('.kid-close')) { close(); return; }
      var t;
      if ((t = e.target.closest('[data-key]'))) { key(pad(t), t.dataset.key); return; }
      if ((t = e.target.closest('[data-go]'))) { go(t.dataset.go); return; }
      if ((t = e.target.closest('[data-minus]'))) { pick(t.parentNode.querySelector('[data-choice]'), -1); return; }
      if ((t = e.target.closest('[data-choice]'))) { pick(t, 1); return; }
      if ((t = e.target.closest('[data-print-all]'))) { printAll(t.closest('[data-screen]')); return; }
      if ((t = e.target.closest('[data-parent-open]'))) { openParent(t); return; }
      if ((t = e.target.closest('[data-filter-chip]'))) { filter(t.closest('[data-screen]'), t.dataset.filterChip); return; }
      if ((t = e.target.closest('[data-preview]'))) { preview(t); return; }
      if ((t = e.target.closest('[data-viewer-close]'))) { var v = t.closest('[data-viewer]'); v.hidden = true; v.querySelector('iframe').removeAttribute('src'); return; }
      if ((t = e.target.closest('[data-decision]'))) { decide(pad(t), t.dataset.decision); }
    });
    document.addEventListener('keydown', function (e) {
      if (overlay.hidden) { return; }
      poke();
      var p = overlay.querySelector('.kz-parent:not([hidden])') || overlay.querySelector('[data-screen="pin"]');
      if (e.key === 'Escape') { close(); return; }
      if (!p) { return; }
      if (/^[0-9]$/.test(e.key)) { key(p, e.key); } else if (e.key === 'Backspace') { key(p, 'del'); } else if (e.key === 'Enter') { key(p, 'ok'); }
    });
    document.addEventListener('dashboard:home', close);
  })();
</script>
@endpush
@endif
