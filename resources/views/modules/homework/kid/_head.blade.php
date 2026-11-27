{{-- Top of every screen after the PIN: back to the kid's home, the screen's title, and the points. --}}
<header class="kz-head">
  @if ($screen === 'home')
    @include('modules.homework.kid._avatar')
    <h2>{{ __('Hi :name!', ['name' => $kid['name']]) }}</h2>
  @else
    <button type="button" class="kz-back" data-go="home" aria-label="{{ __('Back to :name’s home', ['name' => $kid['name']]) }}">@include('modules.homework.kid._icon', ['name' => 'back', 'size' => 26])</button>
    <h2>{{ __($title) }}</h2>
  @endif
  {{-- The points show once: big on the home screen, in this pill on the others. --}}
  @if ($screen !== 'home')
    <span class="kz-points" aria-label="{{ $balance }} {{ __('points') }}">@include('modules.homework.kid._icon', ['name' => 'star', 'size' => 20])<b>{{ $balance }}</b></span>
  @endif
</header>
