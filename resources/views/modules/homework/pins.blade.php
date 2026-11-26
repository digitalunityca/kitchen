@extends('settings.layout')
@section('title', 'Kids')
@push('head')
<style>
  .kid { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 12px 14px; padding: 18px 0; border-top: 1px solid var(--line); }
  .kid:first-child { border-top: 0; }
  .avatar { width: 44px; height: 44px; border-radius: 999px; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; }
  .kid b { font-size: 18px; font-weight: 600; display: block; }
  .kid small { color: var(--sub); font-size: 14px; }
  .kid small.set { color: #248A3D; }
  .kid form.set-pin { grid-column: 1 / -1; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .pin { font: inherit; font-size: 22px; letter-spacing: .4em; width: 140px; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--line); background: var(--fill); color: var(--text); }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 12px 18px; font-weight: 600; font-size: 16px; }
  .ghost { background: var(--fill); color: var(--text); border-radius: 12px; padding: 12px 16px; font-size: 15px; }
  .ok { color: #248A3D; font-weight: 500; margin: 0; }
  .err { color: #D70015; font-size: 14px; margin: 0; width: 100%; }
  .hint { color: var(--sub); margin: 0; line-height: 1.5; }
</style>
@endpush
@section('content')
  <h1>Kids</h1>
  @if (session('status')) <p class="ok" role="status">{{ session('status') }}</p> @endif
  <p class="hint">Each kid opens their homework with their own 4-digit PIN. Your parent PIN opens any kid, and it is still needed to check homework and to buy rewards.</p>
  <div class="card">
    @foreach ($kids as $kid)
      <div class="kid">
        <span class="avatar" style="background: {{ $kid['color'] }}">{{ $kid['badge'] }}</span>
        <span><b>{{ $kid['name'] }}</b><small class="{{ $kid['hasPin'] ? 'set' : '' }}">{{ $kid['hasPin'] ? 'PIN set' : 'No PIN yet: only the parent PIN opens it' }}</small></span>
        <form method="post" class="set-pin" action="{{ route('modules.homework.pin', $kid['id']) }}">
          @csrf
          <input class="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" placeholder="••••" aria-label="New PIN for {{ $kid['name'] }}">
          <button class="btn">{{ $kid['hasPin'] ? 'Change PIN' : 'Set PIN' }}</button>
          @if ($kid['hasPin'])
            <button class="ghost" name="clear" value="1" formnovalidate>Clear</button>
          @endif
        </form>
      </div>
    @endforeach
    @error('pin') <p class="err">{{ $message }}</p> @enderror
  </div>
  <p><a href="{{ route('settings') }}">← Settings</a></p>
@endsection
