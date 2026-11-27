{{-- The kid's home: the points (once), one "Aujourd'hui" line, and the places to go. --}}
<div class="kz kz-home" data-screen="home">
  @include('modules.homework.kid._head')
  <div class="kz-home-grid">
    <section class="kz-score" aria-label="{{ __('Points') }}">
      <span class="kz-score-star" aria-hidden="true">@include('modules.homework.kid._icon', ['name' => 'star', 'size' => 30])</span>
      <b class="kz-score-num">{{ $balance }}</b>
      <span class="kz-score-label">{{ __('points') }}</span>
      <div class="kz-today">
        <p class="kz-today-title">{{ __('Today') }}</p>
        @if (! $schoolDay)
          <p>{{ __('No homework today. Enjoy your weekend!') }}</p>
        @elseif ($todayTotal === 0)
          <p>{{ __('Nothing picked yet today.') }}</p>
        @else
          <p>{!! trans_choice('{1} <b>:done of :total</b> sheet checked|[2,*] <b>:done of :total</b> sheets checked', $todayTotal, ['done' => $todayChecked, 'total' => $todayTotal]) !!}</p>
          <div class="kz-steps" aria-hidden="true">
            @for ($i = 0; $i < $todayTotal; $i++)<i class="{{ $i < $todayChecked ? 'on' : '' }}"></i>@endfor
          </div>
        @endif
        @if ($choresWaiting + $choresDone > 0)
          <p class="kz-sub">{{ trans_choice('{1} :n chore done|[2,*] :n chores done', $choresWaiting + $choresDone, ['n' => $choresWaiting + $choresDone]) }}{{ $choresWaiting > 0 ? ' · '.__(':n waiting for a parent', ['n' => $choresWaiting]) : '' }}</p>
        @endif
      </div>
    </section>
    <nav class="kz-tiles" aria-label="{{ __(':name’s homework', ['name' => $kid['name']]) }}">
      <button type="button" class="kz-tile kz-tile-main" data-go="homework">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'homework', 'size' => 34])</span>
        @if ($todaySheets !== [])
          <span class="kz-tile-sheets" aria-label="{{ trans_choice('{1} :n sheet today|[2,*] :n sheets today', count($todaySheets), ['n' => count($todaySheets)]) }}">
            @foreach ($todaySheets as $sheet)<i class="{{ $sheet['checked'] ? 'checked' : '' }}">@include('modules.homework.kid._icon', ['name' => $sheet['template'], 'size' => 20])</i>@endforeach
          </span>
        @endif
        <b>{{ __('Homework') }}</b><small>{{ $schoolDay ? __('Pick and print today’s sheets') : __('Monday to Friday') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="chores">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'chores', 'size' => 34])</span>
        <b>{{ __('Help at home') }}</b><small>{{ __('Earn points by helping') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="rewards">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 34])</span>
        <b>{{ __('Rewards') }}</b><small>{{ __('Spend your points') }}</small>
      </button>
      <button type="button" class="kz-tile" data-go="history">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'history', 'size' => 34])</span>
        <b>{{ __('History') }}</b><small>{{ $toCheck > 0 ? __(':n to check', ['n' => $toCheck]) : __('Your points, day by day') }}</small>
      </button>
      <button type="button" class="kz-tile kz-tile-small" data-go="news">
        <span class="kz-tile-icon">@include('modules.homework.kid._icon', ['name' => 'news', 'size' => 24])</span>
        <b>{{ __('News') }}</b><small>{{ __('Messages from your teachers') }}</small>
      </button>
    </nav>
  </div>
</div>
