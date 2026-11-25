{{-- Kids' homework: avatar buttons in the side menu and a sheet per kid (placeholder for now). --}}
@if ($kids !== [])
@push('menu-items')
  @foreach ($kids as $kid)
    <button type="button" class="kid-btn" data-kid="{{ $kid['id'] }}" style="--kid: {{ $kid['color'] }}" aria-label="{{ $kid['name'] }}’s homework">{{ $kid['badge'] }}</button>
  @endforeach
  <span class="menu-divider" aria-hidden="true"></span>
@endpush

<div class="kid-overlay" id="kid-overlay" hidden role="dialog" aria-modal="true">
  @foreach ($kids as $kid)
    <section class="kid-sheet" data-kid-sheet="{{ $kid['id'] }}" style="--kid: {{ $kid['color'] }}" hidden aria-label="{{ $kid['name'] }}’s homework">
      <div class="kid-head">
        <span class="kid-avatar">{{ $kid['badge'] }}</span>
        <h2>{{ $kid['name'] }}’s homework</h2>
        <button type="button" class="kid-close" aria-label="Close">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>
      <div class="kid-empty">
        <span aria-hidden="true">📚</span>
        <b>Coming soon</b>
        <p>{{ $kid['name'] }}’s homework and school tasks will show up here.</p>
      </div>
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
  .kid-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; padding: 40px 120px 40px 40px; }
  .kid-overlay[hidden], .kid-sheet[hidden] { display: none; }
  .kid-sheet { width: min(760px, 100%); max-height: 100%; background: var(--bg); border-radius: 28px; padding: 22px 26px 30px; box-shadow: 0 30px 80px rgba(0,0,0,.3); }
  body.night .kid-sheet { background: #0E0E10; box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
  .kid-sheet .kid-head { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
  .kid-avatar { width: 48px; height: 48px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--kid); color: #fff; font-size: 22px; font-weight: 600; flex: none; }
  .kid-sheet h2 { flex: 1; margin: 0; font-size: 26px; font-weight: 600; letter-spacing: -.02em; }
  .kid-close { width: 48px; height: 48px; border-radius: 999px; background: var(--fill); display: flex; align-items: center; justify-content: center; color: var(--text); }
  .kid-empty { background: var(--card); border-radius: 22px; padding: 48px 24px; box-shadow: var(--shadow); text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px; }
  .kid-empty span { font-size: 56px; }
  .kid-empty b { font-size: 22px; font-weight: 600; }
  .kid-empty p { margin: 0; color: var(--sub); font-size: 16px; }
</style>
@endpush

@push('scripts')
<script>
  (function () {
    var overlay = document.getElementById('kid-overlay'), idle = 0;
    function close() { overlay.hidden = true; clearTimeout(idle); }
    function open(id) {
      overlay.querySelectorAll('[data-kid-sheet]').forEach(function (s) { s.hidden = s.dataset.kidSheet !== id; });
      overlay.hidden = false; clearTimeout(idle); idle = setTimeout(close, 2 * 60 * 1000);
    }
    document.addEventListener('click', function (e) { var b = e.target.closest('[data-kid]'); if (b) open(b.dataset.kid); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay || e.target.closest('.kid-close')) close(); });
    document.addEventListener('dashboard:home', close);
  })();
</script>
@endpush
@endif
