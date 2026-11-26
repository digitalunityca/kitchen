{{-- The kid's home: points, today's progress, and the four places to go. --}}
<div class="kz kz-home" data-screen="home">
  @include('modules.homework.kid._head')
  <div class="kz-home-grid">
    <section class="kz-score" aria-label="Points">
      <b class="kz-score-num">{{ $balance }}</b>
      <span class="kz-score-label">points</span>
      <div class="kz-today">
        @if (! $schoolDay)
          <p>No homework today. Enjoy your weekend!</p>
        @elseif ($todayTotal === 0)
          <p>Nothing picked yet today.</p>
        @else
          <p><b>{{ $todayChecked }} of {{ $todayTotal }}</b> checked today</p>
          <div class="kz-steps" aria-hidden="true">
            @for ($i = 0; $i < $todayTotal; $i++)<i class="{{ $i < $todayChecked ? 'on' : '' }}"></i>@endfor
          </div>
        @endif
        @if ($toCheck > 0)
          <p class="kz-sub">{{ $toCheck }} {{ $toCheck === 1 ? 'sheet is' : 'sheets are' }} waiting for a parent check</p>
        @endif
      </div>
    </section>
    <nav class="kz-tiles" aria-label="{{ $kid['name'] }}’s homework">
      <button type="button" class="kz-tile" data-go="homework">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'homework', 'size' => 34])</span>
        <b>Homework</b><small>{{ $schoolDay ? 'Pick and print today’s sheets' : 'Monday to Friday' }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="rewards">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 34])</span>
        <b>Rewards</b><small>Spend your points</small>
      </button>
      <button type="button" class="kz-tile" data-go="history">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'history', 'size' => 34])</span>
        <b>History</b><small>{{ $toCheck > 0 ? $toCheck.' to check' : 'Your points, day by day' }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="news">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'news', 'size' => 34])</span>
        <b>News</b><small>Messages from your teachers</small>
      </button>
    </nav>
  </div>
</div>
