@extends('access.layout')

@section('title', 'Try again later')

@section('content')
  <h1>Too many tries</h1>
  <p>For safety, PIN entry is paused. Try again in {{ $minutes }} {{ $minutes === 1 ? 'minute' : 'minutes' }}.</p>
@endsection
