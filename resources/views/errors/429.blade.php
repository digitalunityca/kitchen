@extends('access.layout')

@section('title', 'Slow down')
{{-- Try again by itself, so the tablet never stays stuck on this page. --}}
@push('head')<meta http-equiv="refresh" content="30">@endpush

@section('content')
  <h1>Slow down</h1>
  <p>Too many requests. This page tries again by itself in 30 seconds.</p>
@endsection
