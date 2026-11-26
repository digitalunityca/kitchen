{{-- Top of every screen after the PIN: back to the kid's home, the screen's title, and the points. --}}
<header class="kz-head">
  @if ($screen === 'home')
    @include('modules.homework.kid._avatar')
    <h2>Hi {{ $kid['name'] }}!</h2>
  @else
    <button type="button" class="kz-back" data-go="home" aria-label="Back to {{ $kid['name'] }}’s home">@include('modules.homework.kid._icon', ['name' => 'back', 'size' => 26])</button>
    <h2>{{ $title }}</h2>
  @endif
  <span class="kz-points" aria-label="{{ $balance }} points">@include('modules.homework.kid._icon', ['name' => 'star', 'size' => 20])<b>{{ $balance }}</b></span>
</header>
