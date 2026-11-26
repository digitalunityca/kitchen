{{-- History: one list of the printed sheets and rewards, newest day first, filtered by status. --}}
@php($dayLabel = function (string $date): string {
    $day = \Carbon\CarbonImmutable::parse($date, \App\Modules\Homework\HomeworkModule::TIMEZONE);
    $today = \Carbon\CarbonImmutable::now(\App\Modules\Homework\HomeworkModule::TIMEZONE);

    return match (true) {
        $day->isSameDay($today) => 'Today',
        $day->isSameDay($today->subDay()) => 'Yesterday',
        default => $day->isoFormat('dddd D MMMM'),
    };
})
@php($badges = ['check' => 'To check', 'done' => 'Done', 'missed' => 'Not done'])
<div class="kz kz-history" data-screen="history" data-filter="{{ $filter }}">
  @include('modules.homework.kid._head', ['title' => 'History'])
  <div class="kz-filters" role="group" aria-label="Show">
    @foreach (['check' => 'To check', 'done' => 'Done', 'missed' => 'Not done', 'all' => 'All'] as $key => $label)
      <button type="button" class="kz-chip" data-filter-chip="{{ $key }}" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }} <span>{{ $counts[$key] }}</span></button>
    @endforeach
    <p class="kz-quiet kz-week">This week: <b>{{ $stats['weekTasks'] }}</b> {{ $stats['weekTasks'] === 1 ? 'sheet' : 'sheets' }}, <b>+{{ $stats['weekPoints'] }}</b> points</p>
  </div>
  <div class="kz-scroll kz-history-list">
    @forelse ($days as $day)
      <section class="kz-day-group" data-day>
        <h3 class="kz-day"><span>{{ $dayLabel($day['date']) }}</span>@if ($day['points'] !== 0)<b class="{{ $day['points'] > 0 ? 'plus' : 'minus' }}">{{ $day['points'] > 0 ? '+' : '−' }}{{ abs($day['points']) }} points</b>@endif</h3>
        <ul class="kz-list">
          @foreach ($day['rows'] as $row)
            <li class="kz-row is-{{ $row['status'] }}" data-status="{{ $row['status'] }}">
              @if ($row['kind'] === 'reward')
                <span class="kz-row-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 22])</span>
                <span class="kz-row-text"><b>{{ $row['name'] }}</b><small>Reward{{ $row['time'] ? ' · '.\Carbon\CarbonImmutable::parse($row['time'])->setTimezone(\App\Modules\Homework\HomeworkModule::TIMEZONE)->format('g:i A') : '' }}</small></span>
                <b class="kz-row-points minus">−{{ abs($row['points']) }}</b>
              @else
                <span class="kz-row-icon">@include('modules.homework.kid._icon', ['name' => $row['template'], 'size' => 22])</span>
                <span class="kz-row-text">
                  <b>@if ($row['number'])<span class="kz-no">{{ $row['number'] }}</span>@endif{{ $row['name'] }}</b>
                  <small>{{ $row['printed'] ? 'Printed '.\Carbon\CarbonImmutable::parse($row['printed'])->setTimezone(\App\Modules\Homework\HomeworkModule::TIMEZONE)->format('g:i A').' · ' : '' }}+{{ $row['points'] }} points</small>
                </span>
                <span class="kz-badge is-{{ $row['status'] }}">{{ $badges[$row['status']] }}</span>
                <span class="kz-row-actions">
                  @if ($row['preview'])
                    <button type="button" class="kz-btn ghost" data-preview="{{ route('modules.homework.preview', [$kid['id'], $day['date'], $row['id']]) }}" data-preview-title="{{ $row['number'] }} {{ $row['name'] }}">Preview</button>
                  @endif
                  @if ($row['status'] === 'check')
                    @php($check = route('modules.homework.check', [$kid['id'], $day['date'], $row['id']]))
                    <button type="button" class="kz-btn ghost" data-parent-open="{{ $check }}" data-parent-decision="missed" data-parent-title="Not done: {{ $row['number'] }} {{ $row['name'] }}">Not done</button>
                    <button type="button" class="kz-btn" data-parent-open="{{ $check }}" data-parent-decision="approve" data-parent-title="Approve {{ $row['number'] }} {{ $row['name'] }} · +{{ $row['points'] }}">Approve</button>
                  @endif
                </span>
              @endif
            </li>
          @endforeach
        </ul>
      </section>
    @empty
      <p class="kz-quiet">Your sheets and points will show here.</p>
    @endforelse
    <p class="kz-quiet kz-filter-empty" data-filter-empty hidden>Nothing here.</p>
  </div>
  <div class="kz-viewer" data-viewer hidden role="dialog" aria-modal="true" aria-label="Worksheet preview">
    <div class="kz-viewer-bar"><b data-viewer-title></b><button type="button" class="kz-back" data-viewer-close aria-label="Close preview"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
    <iframe data-viewer-frame title="Worksheet"></iframe>
  </div>
  @include('modules.homework.kid._parent')
</div>
