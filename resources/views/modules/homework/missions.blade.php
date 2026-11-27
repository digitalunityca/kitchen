@extends('settings.layout')
@section('title', __('Rewards from a parent'))
@push('head')
<style>
  .row { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
  label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; font-weight: 600; color: var(--sub); }
  label.inline { flex-direction: row; align-items: center; gap: 6px; font-size: 15px; font-weight: 500; color: var(--text); }
  input[type=text], input[type=number], input[type=date], select { font: inherit; font-size: 16px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 10px 12px; }
  input.title { width: 280px; } input.num { width: 80px; }
  .kids { display: flex; gap: 14px; align-items: center; margin-top: 10px; }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 11px 18px; font-weight: 600; font-size: 16px; }
  .ghost { background: var(--fill); color: var(--text); border-radius: 12px; padding: 9px 14px; font-size: 14px; }
  .ok { color: #248A3D; font-weight: 500; margin: 0; }
  .err { color: #D70015; font-size: 14px; margin: 0; }
  .hint { color: var(--sub); margin: 0; line-height: 1.5; }
  h2 { font-size: 20px; margin: 8px 0 0; }
  .mission { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
  .mission:first-child { border-top: 0; }
  .mission .grow { flex: 1; }
  .mission small { color: var(--sub); display: block; }
  .waiting { color: #A65A00; font-weight: 600; font-size: 13px; }
</style>
@endpush
@section('content')
  <h1>{{ __('Rewards from a parent') }}</h1>
  @if (session('status')) <p class="ok" role="status">{{ session('status') }}</p> @endif
  @if ($errors->any()) <p class="err" role="alert">{{ $errors->first() }}</p> @endif
  <p class="hint">{{ __('A mission is something to do: the kid taps “I’m done”, then you check it in History (Bien, Quelques erreurs or À revoir). A bonus gives points right away for something already done. Both count toward rewards.') }}</p>

  <h2>{{ __('Give a mission') }}</h2>
  <form method="post" action="{{ route('modules.homework.missions.store') }}" class="card">
    @csrf
    <div class="row">
      <label>{{ __('What to do') }}<input class="title" type="text" name="title" value="{{ old('title') }}" maxlength="80" placeholder="{{ __('Help tidy the garage') }}" required></label>
      <label>{{ __('Points') }}<input class="num" type="number" name="points" value="{{ old('points', 20) }}" min="1" max="100" required></label>
      <label>{{ __('By (optional)') }}<input type="date" name="due" value="{{ old('due') }}"></label>
      <button class="btn">{{ __('Give') }}</button>
    </div>
    <div class="kids">
      @foreach ($kids as $kid)<label class="inline"><input type="checkbox" name="kids[]" value="{{ $kid->id }}"> {{ $kid->name }}</label>@endforeach
    </div>
  </form>

  <h2>{{ __('Bonus now') }}</h2>
  <form method="post" action="{{ route('modules.homework.missions.bonus') }}" class="card">
    @csrf
    <div class="row">
      <label>{{ __('Kid') }}<select name="kid">@foreach ($kids as $kid)<option value="{{ $kid->id }}">{{ $kid->name }}</option>@endforeach</select></label>
      <label>{{ __('For') }}<input class="title" type="text" name="reason" maxlength="80" placeholder="{{ __('Helped grandma without being asked') }}" required></label>
      <label>{{ __('Points') }}<input class="num" type="number" name="points" value="10" min="1" max="100" required></label>
      <button class="btn">{{ __('Give') }}</button>
    </div>
  </form>

  <h2>{{ __('Open missions') }}</h2>
  <div class="card">
    @forelse ($open as $m)
      <div class="mission">
        <span class="grow"><b>{{ $m['title'] }}</b><small>{{ $m['kid'] }} · +{{ $m['points'] }} {{ __('points') }}{{ $m['due'] ? ' · '.__('by :date', ['date' => \Carbon\CarbonImmutable::parse($m['due'])->translatedFormat('j F')]) : '' }}</small></span>
        @if ($m['waiting'])<span class="waiting">{{ __('Waiting for a parent') }}</span>@endif
        <form method="post" action="{{ route('modules.homework.missions.cancel', $m['id']) }}">@csrf<button class="ghost">{{ __('Remove') }}</button></form>
      </div>
    @empty
      <p class="hint">{{ __('No open missions.') }}</p>
    @endforelse
  </div>
  <p><a href="{{ route('settings') }}">← {{ __('Settings') }}</a></p>
@endsection
