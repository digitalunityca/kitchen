{{-- History: one list of the printed sheets and rewards, newest day first, filtered by status. --}}
@php($dayLabel = function (string $date): string {
    $day = \Carbon\CarbonImmutable::parse($date, \App\Modules\Homework\HomeworkModule::TIMEZONE);
    $today = \Carbon\CarbonImmutable::now(\App\Modules\Homework\HomeworkModule::TIMEZONE);

    return match (true) {
        $day->isSameDay($today) => __('Today'),
        $day->isSameDay($today->subDay()) => __('Yesterday'),
        default => ucfirst($day->isoFormat('dddd D MMMM')),
    };
})
@php($badges = ['check' => __('Waiting for a parent'), 'done' => __('Approved'), 'missed' => __('Not done')])
@php($scores = ['bien' => __('Good'), 'erreurs' => __('A few mistakes'), 'revoir' => __('Go over again')])
<div class="kz kz-history" data-screen="history" data-filter="{{ $filter }}">
  @include('modules.homework.kid._head', ['title' => 'History'])
  <div class="kz-filters" role="group" aria-label="{{ __('Show') }}">
    @foreach (['check' => __('To check'), 'done' => __('Approved'), 'missed' => __('Not done'), 'all' => __('All')] as $key => $label)
      <button type="button" class="kz-chip" data-filter-chip="{{ $key }}" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }} <span>{{ $counts[$key] }}</span></button>
    @endforeach
    <p class="kz-quiet kz-week">{{ __('This week:') }} <b>{{ $stats['weekTasks'] }}</b> {{ $stats['weekTasks'] === 1 ? __('sheet') : __('sheets') }}, <b>+{{ $stats['weekPoints'] }}</b> {{ __('points') }}</p>
  </div>
  @if ($stuck !== [])
    <p class="kz-hint kz-stuck" role="note">{{ __('For a parent: :name had to go over this twice in a row. Do one together:', ['name' => $kid['name']]) }} <b>{{ implode(', ', $stuck) }}</b></p>
  @endif
  <div class="kz-scroll kz-history-list">
    @forelse ($days as $day)
      <section class="kz-day-group" data-day>
        <h3 class="kz-day"><span>{{ $dayLabel($day['date']) }}</span>@if ($day['points'] !== 0)<b class="{{ $day['points'] > 0 ? 'plus' : 'minus' }}">{{ $day['points'] > 0 ? '+' : '−' }}{{ abs($day['points']) }} {{ __('points') }}</b>@endif</h3>
        <ul class="kz-list">
          @foreach ($day['rows'] as $row)
            <li class="kz-row is-{{ $row['status'] }}" data-status="{{ $row['status'] }}">
              @if ($row['kind'] === 'chore')
                <span class="kz-row-icon kz-chore-emoji" aria-hidden="true">{{ $row['emoji'] }}</span>
                <span class="kz-row-text"><b>{{ $row['name'] }}</b><small>{{ __('Help at home') }} · +{{ $row['points'] }} {{ __('points') }}</small></span>
                @if ($row['status'] === 'done')
                  <span class="kz-approved">@include('modules.homework.kid._icon', ['name' => 'ok', 'size' => 22]){{ __('Approved') }} · +{{ $row['points'] }}</span>
                @elseif ($row['status'] === 'missed')
                  <span class="kz-badge is-missed">{{ __('Not done · no points') }}</span>
                @else
                  <span class="kz-badge is-check">{{ $badges['check'] }}</span>
                  <span class="kz-row-actions">
                    @php($check = route('modules.homework.chore.check', [$kid['id'], $row['id']]))
                    <button type="button" class="kz-btn ghost" data-parent-open="{{ $check }}" data-parent-decision="missed" data-parent-title="{{ __('Not done') }} : {{ $row['name'] }}">{{ __('Not done') }}</button>
                    <button type="button" class="kz-btn" data-parent-open="{{ $check }}" data-parent-decision="approve" data-parent-title="{{ __('Approve') }} {{ $row['name'] }} · +{{ $row['points'] }}">{{ __('Approve') }}</button>
                  </span>
                @endif
              @elseif ($row['kind'] === 'reward')
                <span class="kz-row-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 22])</span>
                <span class="kz-row-text"><b>{{ __($row['name']) }}</b><small>{{ __('Reward') }}{{ $row['time'] ? ' · '.\App\Support\TimeFormat::hm(\Carbon\CarbonImmutable::parse($row['time'])->setTimezone(\App\Modules\Homework\HomeworkModule::TIMEZONE)) : '' }}</small></span>
                <b class="kz-row-points minus">−{{ abs($row['points']) }}</b>
              @else
                <span class="kz-row-icon">@include('modules.homework.kid._icon', ['name' => $row['template'], 'size' => 22])</span>
                <span class="kz-row-text">
                  <b>@if ($row['number'])<span class="kz-no">{{ $row['number'] }}</span>@endif{{ __($row['name']) }}</b>
                  <small>{{ $row['printed'] ? __('Printed :time', ['time' => \App\Support\TimeFormat::hm(\Carbon\CarbonImmutable::parse($row['printed'])->setTimezone(\App\Modules\Homework\HomeworkModule::TIMEZONE))]).' · ' : '' }}+{{ $row['points'] }} {{ __('points') }}</small>
                </span>
                @if ($row['status'] === 'done')
                  <span class="kz-approved">@include('modules.homework.kid._icon', ['name' => 'ok', 'size' => 22]){{ __('Approved') }}@if ($row['score'] ?? null) <span class="kz-score-tag">· {{ $scores[$row['score']] }}</span>@endif · +{{ $row['earned'] ?? $row['points'] }}</span>
                @elseif ($row['status'] === 'missed')
                  <span class="kz-badge is-missed">{{ __('Not done · no points') }}</span>
                @else
                  <span class="kz-badge is-check">{{ $badges['check'] }}</span>
                @endif
                <span class="kz-row-actions">
                  @if ($row['preview'])
                    <button type="button" class="kz-btn ghost" data-preview="{{ route('modules.homework.preview', [$kid['id'], $day['date'], $row['id']]) }}" data-preview-title="{{ $row['number'] }} {{ __($row['name']) }}">{{ __('Preview') }}</button>
                  @endif
                  @if ($row['status'] === 'check')
                    @php($check = route('modules.homework.check', [$kid['id'], $day['date'], $row['id']]))
                    <button type="button" class="kz-btn ghost" data-parent-open="{{ $check }}" data-parent-decision="missed" data-parent-title="{{ __('Not done') }} : {{ $row['number'] }} {{ __($row['name']) }}">{{ __('Not done') }}</button>
                    <button type="button" class="kz-btn" data-parent-open="{{ $check }}" data-parent-scores="1" data-parent-points="{{ $row['points'] }}" data-parent-title="{{ __('Check') }} {{ $row['number'] }} {{ __($row['name']) }}">{{ __('Approve') }}</button>
                  @endif
                </span>
              @endif
            </li>
          @endforeach
        </ul>
      </section>
    @empty
      <p class="kz-quiet">{{ __('Your sheets and points will show here.') }}</p>
    @endforelse
    <p class="kz-quiet kz-filter-empty" data-filter-empty hidden>{{ __('Nothing here.') }}</p>
  </div>
  <div class="kz-viewer" data-viewer hidden role="dialog" aria-modal="true" aria-label="{{ __('Worksheet preview') }}">
    <div class="kz-viewer-bar"><b data-viewer-title></b><button type="button" class="kz-back" data-viewer-close aria-label="{{ __('Close preview') }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
    <iframe data-viewer-frame title="{{ __('Worksheet') }}"></iframe>
  </div>
  @include('modules.homework.kid._parent')
</div>
