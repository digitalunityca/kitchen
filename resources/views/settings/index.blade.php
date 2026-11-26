@extends('settings.layout')
@section('title', 'Settings')
@push('head')
<style>
  .item { display: flex; align-items: center; gap: 14px; padding: 16px 4px; border-top: 1px solid var(--line); text-decoration: none; color: var(--text); }
  .item:first-child { border-top: 0; }
  .item b { display: block; font-size: 18px; font-weight: 600; }
  .item small { display: block; color: var(--sub); font-size: 14px; }
  .item span:first-child { font-size: 26px; width: 36px; text-align: center; }
  .item::after { content: '›'; margin-left: auto; color: var(--sub); font-size: 26px; }
  .row-actions { display: flex; gap: 12px; }
  .ghost { color: var(--text); font-size: 15px; padding: 10px 16px; border-radius: 12px; background: var(--fill); text-decoration: none; }
</style>
@endpush
@section('content')
  <div>
    <p class="eyebrow">Kitchen hub</p>
    <h1>Settings</h1>
  </div>
  <div class="card">
    @foreach ($sections as $key => [$title, $hint, $route])
      <a class="item" href="{{ route($route) }}"><span>{{ ['calendar' => '📅', 'youtube-music' => '🎵', 'homework' => '📚', 'kids' => '🧒'][$key] ?? '⚙️' }}</span><span><b>{{ $title }}</b><small>{{ $hint }}</small></span></a>
    @endforeach
  </div>
  <div class="row-actions">
    <a class="ghost" href="{{ route('dashboard') }}">⌂ Home</a>
    <form method="post" action="{{ route('settings.lock') }}">@csrf<button class="ghost">🔒 Lock settings</button></form>
  </div>
@endsection
