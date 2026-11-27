@extends('settings.layout')
@section('title', __('Progress'))
@push('head')
<style>
  h2 { font-size: 22px; margin: 18px 0 6px; display: flex; align-items: center; gap: 10px; }
  h3 { font-size: 15px; color: var(--sub); margin: 14px 0 4px; font-weight: 600; }
  .avatar { width: 34px; height: 34px; border-radius: 999px; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; }
  table { width: 100%; border-collapse: collapse; font-size: 15px; }
  th { text-align: left; font-size: 12px; font-weight: 600; color: var(--sub); padding: 6px 8px; text-transform: uppercase; letter-spacing: .02em; }
  td { padding: 9px 8px; border-top: 1px solid var(--line); vertical-align: top; }
  td small { display: block; color: var(--sub); font-size: 12px; }
  .badge { display: inline-block; border-radius: 999px; padding: 3px 10px; font-size: 13px; font-weight: 600; background: var(--fill); color: var(--sub); white-space: nowrap; }
  .badge.en_cours { background: rgba(0,113,227,.12); color: #0058B0; }
  .badge.maitrise { background: rgba(52,199,89,.16); color: #1E7A34; }
  .badge.a_revoir { background: rgba(255,59,48,.12); color: #B3261E; }
  .bar { display: inline-flex; gap: 3px; vertical-align: middle; }
  .bar i { width: 12px; height: 8px; border-radius: 3px; background: var(--fill); }
  .bar i.on { background: var(--accent); }
  .month { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 0; padding: 0; list-style: none; }
  .month li { background: var(--fill); border-radius: 10px; padding: 5px 10px; font-size: 14px; }
  .month li.sheet { background: rgba(0,113,227,.10); }
  .hint { color: var(--sub); margin: 0; line-height: 1.5; }
  .stuck { color: #B3261E; font-weight: 600; }
  .scroll { overflow-x: auto; }
  .page { max-width: 1040px; }
  td:first-child { min-width: 260px; }
</style>
@endpush
@php($statuses = ['nouveau' => __('Not started'), 'en_cours' => __('In progress'), 'maitrise' => __('Mastered'), 'a_revoir' => __('To go over again')])
@php($scores = ['bien' => __('Good'), 'erreurs' => __('A few mistakes'), 'revoir' => __('Go over again')])
@php($subjects = ['math' => __('Math'), 'francais' => __('French')])
@php($monthName = fn (?int $m): string => $m ? \Carbon\CarbonImmutable::create(2026, $m, 1)->translatedFormat('M') : '')
@section('content')
  <h1>{{ __('Progress') }}</h1>
  <p class="hint">{{ __('Skills come from Quebec’s Progression des apprentissages. → learns it with the teacher this year, ★ does it alone by June, ↺ reuses it. The hub picks each sheet’s skill and level; your score after a check moves the kid up (2 × Good), keeps the level, or steps back (Go over again). A mastered skill comes back for a review after :a days, then :b days.', ['a' => $reviewDays[1], 'b' => $reviewDays[2]]) }}</p>

  @foreach ($kids as $k)
    <h2><span class="avatar" style="background: {{ $k['kid']->color }}">{{ $k['kid']->badge }}</span>{{ $k['kid']->name }} <small style="color: var(--sub); font-size: 15px; font-weight: 400">{{ __(':grade grade', ['grade' => $k['kid']->grade]) }}</small></h2>

    <h3>{{ __('This month in :grade grade', ['grade' => $k['kid']->grade]) }}</h3>
    <ul class="month">
      @forelse ($k['thisMonth'] as $m)
        <li class="{{ $m['practised'] ? 'sheet' : '' }}" title="{{ $m['practised'] ? __('Practised on the sheets') : __('Not on the sheets yet') }}">{{ \App\Models\SkillGrade::SYMBOLS[$m['expectation']] }} {{ $m['name'] }}</li>
      @empty
        <li>{{ __('Nothing new this month: review and upkeep.') }}</li>
      @endforelse
    </ul>

    <div class="card scroll">
      <table>
        <thead><tr><th>{{ __('Skill') }}</th><th>{{ __('This year') }}</th><th>{{ __('Level') }}</th><th>{{ __('Status') }}</th><th>{{ __('Last score') }}</th><th>{{ __('Next review') }}</th></tr></thead>
        <tbody>
          @foreach ($k['rows'] as $r)
            <tr>
              <td>{{ $r['name'] }}<small>{{ $subjects[$r['subject']] ?? $r['subject'] }} · {{ $r['domain'] }} · {{ $r['code'] }}</small>@if ($r['stuck'])<small class="stuck">{{ __('Stuck: do one together') }}</small>@endif</td>
              <td>{{ $r['expectation'] ? \App\Models\SkillGrade::SYMBOLS[$r['expectation']] : '–' }}@if ($r['window'] && $r['window'][0])<small>{{ $monthName($r['window'][0]) }} – {{ $monthName($r['window'][1]) }}</small>@endif</td>
              <td><span class="bar" aria-label="{{ __('Level :n of :top', ['n' => $r['level'], 'top' => $r['top']]) }}">@for ($i = 1; $i <= $r['top']; $i++)<i class="{{ $i <= $r['level'] ? 'on' : '' }}"></i>@endfor</span><small>{{ $r['level'] }}/{{ $r['top'] }}</small></td>
              <td><span class="badge {{ $r['status'] }}">{{ $statuses[$r['status']] ?? $r['status'] }}</span>@if ($r['attempts'])<small>{{ trans_choice('{1} :n sheet|[2,*] :n sheets', $r['attempts'], ['n' => $r['attempts']]) }}</small>@endif</td>
              <td>{{ $r['score'] ? $scores[$r['score']] : '–' }}@if ($r['seen'])<small>{{ $r['seen']->translatedFormat('j M') }}</small>@endif</td>
              <td>{{ $r['review'] ? $r['review']->translatedFormat('j M') : '–' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endforeach
  <p><a href="{{ route('settings') }}">← {{ __('Settings') }}</a></p>
@endsection
