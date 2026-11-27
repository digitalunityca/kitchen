@extends('settings.layout')
@section('title', __('Help at home'))
@push('head')
<style>
  .chore { padding: 16px 0; border-top: 1px solid var(--line); }
  .chore:first-child { border-top: 0; }
  .chore.off { opacity: .55; }
  .row { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
  label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; font-weight: 600; color: var(--sub); }
  label.inline { flex-direction: row; align-items: center; gap: 6px; font-size: 15px; font-weight: 500; color: var(--text); }
  input[type=text], input[type=number], select { font: inherit; font-size: 16px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 10px 12px; }
  input.name { width: 230px; } input.emoji { width: 64px; text-align: center; } input.num { width: 76px; }
  .kids { display: flex; gap: 14px; align-items: center; margin-top: 10px; }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 11px 18px; font-weight: 600; font-size: 16px; }
  .ok { color: #248A3D; font-weight: 500; margin: 0; }
  .err { color: #D70015; font-size: 14px; margin: 0; }
  .hint { color: var(--sub); margin: 0; line-height: 1.5; }
  h2 { font-size: 20px; margin: 8px 0 0; }
</style>
@endpush
@php($dayLabels = ['all' => __('Every day'), 'school' => __('School days'), 'weekend' => __('Weekend')])
@section('content')
  <h1>{{ __('Help at home') }}</h1>
  @if (session('status')) <p class="ok" role="status">{{ session('status') }}</p> @endif
  <p class="hint">{{ __('Kids tap “I’m done” in their area, then a parent approves it in History with the parent PIN. The points count like homework points.') }}</p>
  @if ($errors->any()) <p class="err" role="alert">{{ $errors->first() }}</p> @endif

  <div class="card">
    @foreach ($chores as $chore)
      <form method="post" action="{{ route('modules.homework.chores.update', $chore->id) }}" class="chore {{ $chore->active ? '' : 'off' }}">
        @csrf
        <div class="row">
          <label>{{ __('Emoji') }}<input class="emoji" type="text" name="emoji" value="{{ $chore->emoji }}" maxlength="16" required></label>
          <label>{{ __('Chore') }}<input class="name" type="text" name="name" value="{{ $chore->name_fr }}" maxlength="80" required></label>
          <label>{{ __('Points') }}<input class="num" type="number" name="points" value="{{ $chore->points }}" min="1" max="100" required></label>
          <label>{{ __('Times a day') }}<input class="num" type="number" name="per_day" value="{{ $chore->per_day }}" min="1" max="5" required></label>
          <label>{{ __('Days') }}
            <select name="days">@foreach ($dayLabels as $value => $label)<option value="{{ $value }}" @selected($chore->days === $value)>{{ $label }}</option>@endforeach</select>
          </label>
          <button class="btn">{{ __('Save') }}</button>
        </div>
        <div class="kids">
          @foreach ($kids as $kid)
            <label class="inline"><input type="checkbox" name="kids[]" value="{{ $kid->id }}" @checked($chore->kids->contains('id', $kid->id))> {{ $kid->name }}</label>
          @endforeach
          <label class="inline"><input type="checkbox" name="active" value="1" @checked($chore->active)> {{ __('On') }}</label>
        </div>
      </form>
    @endforeach
  </div>

  <h2>{{ __('Add a chore') }}</h2>
  <form method="post" action="{{ route('modules.homework.chores.store') }}" class="card">
    @csrf
    <div class="row">
      <label>{{ __('Emoji') }}<input class="emoji" type="text" name="emoji" value="{{ old('emoji', '⭐') }}" maxlength="16" required></label>
      <label>{{ __('Chore') }}<input class="name" type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('Water the plants') }}" required></label>
      <label>{{ __('Points') }}<input class="num" type="number" name="points" value="{{ old('points', 5) }}" min="1" max="100" required></label>
      <label>{{ __('Times a day') }}<input class="num" type="number" name="per_day" value="{{ old('per_day', 1) }}" min="1" max="5" required></label>
      <label>{{ __('Days') }}
        <select name="days">@foreach ($dayLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
      </label>
      <button class="btn">{{ __('Add') }}</button>
    </div>
    <div class="kids">
      @foreach ($kids as $kid)
        <label class="inline"><input type="checkbox" name="kids[]" value="{{ $kid->id }}" checked> {{ $kid->name }}</label>
      @endforeach
      <input type="hidden" name="active" value="1">
    </div>
  </form>
  <p><a href="{{ route('settings') }}">← {{ __('Settings') }}</a></p>
@endsection
