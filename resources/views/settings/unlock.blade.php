@extends('settings.layout')
@section('title', 'Settings')
@push('head')
<style>
  .pin { align-items: center; gap: 18px; padding: 28px; }
  .dots { display: flex; gap: 14px; height: 18px; }
  .dots i { width: 16px; height: 16px; border-radius: 999px; border: 2px solid var(--sub); }
  .dots i.on { background: var(--text); border-color: var(--text); }
  .keys { display: grid; grid-template-columns: repeat(3, 76px); gap: 14px; }
  .keys button { height: 76px; border-radius: 999px; background: var(--fill); font-size: 30px; font-weight: 400; color: var(--text); }
  .keys button:active { background: var(--line); }
  .err { color: #FF3B30; font-size: 15px; margin: 0; min-height: 20px; }
  .hint { color: var(--sub); margin: 0; font-size: 15px; }
</style>
@endpush
@section('content')
  <div>
    <p class="eyebrow">Kitchen hub</p>
    <h1>Settings</h1>
  </div>
  <form class="card pin" method="post" action="{{ route('settings.unlock.store') }}" id="pin-form">
    @csrf
    <input type="hidden" name="to" value="{{ $to }}">
    <input type="hidden" name="pin" id="pin" value="">
    <p class="hint">Enter the PIN</p>
    <div class="dots" id="dots"></div>
    <p class="err">{{ $error ?? '' }}</p>
    <div class="keys" id="keys">
      @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $n)<button type="button" data-k="{{ $n }}">{{ $n }}</button>@endforeach
      <button type="button" data-k="back" aria-label="Delete">⌫</button>
      <button type="button" data-k="0">0</button>
      <button type="submit" aria-label="Unlock">→</button>
    </div>
  </form>
  <p><a href="{{ route('dashboard') }}">← Back to the dashboard</a></p>
  <script>
    (function () {
      var pin = document.getElementById('pin'), dots = document.getElementById('dots');
      function draw() { dots.innerHTML = Array.from({ length: Math.max(4, pin.value.length) }, function (_, i) { return '<i class="' + (i < pin.value.length ? 'on' : '') + '"></i>'; }).join(''); }
      document.getElementById('keys').addEventListener('click', function (e) {
        var k = e.target.closest('[data-k]'); if (!k) return;
        pin.value = k.dataset.k === 'back' ? pin.value.slice(0, -1) : (pin.value + k.dataset.k).slice(0, 12);
        draw();
      });
      draw();
    })();
  </script>
@endsection
