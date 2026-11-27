@extends('access.layout')

@section('title', __('Enter PIN'))

@section('content')
  @if ($enabled)
    <h1>{{ __('Enter PIN') }}</h1>
    <p>{{ __('You’re outside the home network. Enter the PIN to open :title.', ['title' => config('dashboard.title')]) }}</p>
    <form method="post" action="{{ url('/unlock') }}">
      @csrf
      <input type="hidden" name="to" value="{{ $to }}">
      <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="current-password"
             aria-label="{{ __('PIN') }}" required autofocus>
      @isset($error)<p class="error" role="alert">{{ $error }}</p>@endisset
      <button type="submit">{{ __('Unlock') }}</button>
    </form>
  @else
    <h1>{{ __('Not available') }}</h1>
    <p>{{ __(':title can only be opened from home.', ['title' => config('dashboard.title')]) }}</p>
  @endif
@endsection
