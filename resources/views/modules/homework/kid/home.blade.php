{{-- The kid's home: points, today's progress, and the four places to go. --}}
<div class="kz kz-home" data-screen="home">
  @include('modules.homework.kid._head')
  <div class="kz-home-grid">
    <section class="kz-score" aria-label="{{ __('Points') }}">
      <b class="kz-score-num">{{ $balance }}</b>
      <span class="kz-score-label">{{ __('points') }}</span>
      <div class="kz-today">
        @if (! $schoolDay)
          <p>{{ __('No homework today. Enjoy your weekend!') }}</p>
        @elseif ($todayTotal === 0)
          <p>{{ __('Nothing picked yet today.') }}</p>
        @else
          <p>{!! __('<b>:done of :total</b> checked today', ['done' => $todayChecked, 'total' => $todayTotal]) !!}</p>
          <div class="kz-steps" aria-hidden="true">
            @for ($i = 0; $i < $todayTotal; $i++)<i class="{{ $i < $todayChecked ? 'on' : '' }}"></i>@endfor
          </div>
        @endif
        @if ($toCheck > 0)
          <p class="kz-sub">{{ trans_choice('{1} :n sheet is waiting for a parent check|[2,*] :n sheets are waiting for a parent check', $toCheck, ['n' => $toCheck]) }}</p>
        @endif
      </div>
    </section>
    <nav class="kz-tiles" aria-label="{{ __(':name’s homework', ['name' => $kid['name']]) }}">
      <button type="button" class="kz-tile" data-go="homework">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'homework', 'size' => 34])</span>
        <b>{{ __('Homework') }}</b><small>{{ $schoolDay ? __('Pick and print today’s sheets') : __('Monday to Friday') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="rewards">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 34])</span>
        <b>{{ __('Rewards') }}</b><small>{{ __('Spend your points') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="history">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'history', 'size' => 34])</span>
        <b>{{ __('History') }}</b><small>{{ $toCheck > 0 ? __(':n to check', ['n' => $toCheck]) : __('Your points, day by day') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="news">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'news', 'size' => 34])</span>
        <b>{{ __('News') }}</b><small>{{ __('Messages from your teachers') }}</small>
      </button>
    </nav>
  </div>
</div>
