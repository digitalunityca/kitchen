@extends('access.layout')

@section('title', 'Enter PIN')

@section('content')
  @if ($enabled)
    <h1>Enter PIN</h1>
    <p>You’re outside the home network. Enter the PIN to open {{ config('dashboard.title') }}.</p>
    <form method="post" action="{{ url('/unlock') }}">
      @csrf
      <input type="hidden" name="to" value="{{ $to }}">
      <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="current-password"
             aria-label="PIN" required autofocus>
      @isset($error)<p class="error" role="alert">{{ $error }}</p>@endisset
      <button type="submit">Unlock</button>
    </form>
  @else
    <h1>Not available</h1>
    <p>{{ config('dashboard.title') }} can only be opened from home.</p>
  @endif
@endsection
