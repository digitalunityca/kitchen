@extends('access.layout')

@section('title', __('Try again later'))

@section('content')
  <h1>{{ __('Too many tries') }}</h1>
  <p>{{ trans_choice('{1} For safety, PIN entry is paused. Try again in :n minute.|[2,*] For safety, PIN entry is paused. Try again in :n minutes.', $minutes, ['n' => $minutes]) }}</p>
@endsection
