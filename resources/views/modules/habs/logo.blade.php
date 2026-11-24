{{-- A team logo from the NHL's asset CDN; the dark variant shows in night mode. --}}
<span class="habs-logo {{ $class ?? '' }}" role="img" aria-label="{{ $team['name'] ?: $team['abbrev'] }}">
  <img class="habs-logo-light" src="{{ $team['logo'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">
  <img class="habs-logo-dark" src="{{ $team['darkLogo'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">
</span>
