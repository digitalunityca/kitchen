{{-- History: the sheets waiting for a parent check, and the points day by day. --}}
@php($dayLabel = function (string $date): string {
    $day = \Carbon\CarbonImmutable::parse($date, \App\Modules\Homework\HomeworkModule::TIMEZONE);
    $today = \Carbon\CarbonImmutable::now(\App\Modules\Homework\HomeworkModule::TIMEZONE);

    return match (true) {
        $day->isSameDay($today) => 'Today',
        $day->isSameDay($today->subDay()) => 'Yesterday',
        default => $day->isoFormat('dddd D MMMM'),
    };
})
<div class="kz kz-history" data-screen="history">
  @include('modules.homework.kid._head', ['title' => 'History'])
  <div class="kz-cols">
    <section class="kz-col" aria-labelledby="kz-check-{{ $kid['id'] }}">
      <h3 class="kz-h" id="kz-check-{{ $kid['id'] }}">To check</h3>
      @if ($toCheck === [])
        <p class="kz-quiet">Nothing to check. Nice work!</p>
      @else
        <ul class="kz-list">
          @foreach ($toCheck as $task)
            <li class="kz-row">
              <span class="kz-row-icon">@include('modules.homework.kid._icon', ['name' => $task['template'], 'size' => 22])</span>
              <span class="kz-row-text"><b>{{ $task['name'] }}</b><small>{{ $dayLabel($task['date']) }} · +{{ $task['points'] }} points</small></span>
              <button type="button" class="kz-btn" data-parent-open="{{ route('modules.homework.check', [$kid['id'], $task['date'], $task['id']]) }}" data-parent-title="{{ $task['name'] }} · +{{ $task['points'] }} points" data-parent-remove="1">Check</button>
            </li>
          @endforeach
        </ul>
      @endif
    </section>
    <section class="kz-col" aria-labelledby="kz-points-{{ $kid['id'] }}">
      <h3 class="kz-h" id="kz-points-{{ $kid['id'] }}">Points</h3>
      <p class="kz-quiet">This week: <b>{{ $stats['weekTasks'] }}</b> {{ $stats['weekTasks'] === 1 ? 'sheet' : 'sheets' }}, <b>+{{ $stats['weekPoints'] }}</b> points</p>
      @if ($days === [])
        <p class="kz-quiet">Your points will show here.</p>
      @else
        <div class="kz-scroll">
          @foreach ($days as $date => $entries)
            <h4 class="kz-day">{{ $dayLabel($date) }}</h4>
            <ul class="kz-list">
              @foreach ($entries as $entry)
                <li class="kz-line"><span>{{ $entry['reason'] }}</span><b class="{{ $entry['points'] > 0 ? 'plus' : 'minus' }}">{{ $entry['points'] > 0 ? '+' : '−' }}{{ abs($entry['points']) }}</b></li>
              @endforeach
            </ul>
          @endforeach
        </div>
      @endif
    </section>
  </div>
  @include('modules.homework.kid._parent')
</div>
