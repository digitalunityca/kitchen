{{-- Canadiens scores and stats, loaded into the sheet when it opens. --}}
@if (! $available)
  <p class="habs-empty">Can’t reach the NHL right now. Try again in a few minutes.</p>
@else
<div class="habs-sheet-grid">
  <section class="habs-panel habs-game">
    @if ($focus)
      @php($d = $details)
      @php($g = $d['game'] ?? $focus)
      @php($away = $g['atHome'] ? $g['opponent'] : $g['mtl'])
      @php($home = $g['atHome'] ? $g['mtl'] : $g['opponent'])
      <p class="label">
        @if ($g['live']) <span class="habs-live"><i></i>Live</span> {{ \App\Actions\Habs\FetchGameDetails::periodLabel($g['period'], $g['periodType']) }}{{ ($d['intermission'] ?? false) ? ' intermission' : (($d['clock'] ?? null) ? ' · '.$d['clock'] : '') }}
        @else {{ $g['start']->format('l, F j') }} · Final{{ $g['lastPeriodType'] && $g['lastPeriodType'] !== 'REG' ? '/'.$g['lastPeriodType'] : '' }}
        @endif
      </p>
      <div class="habs-scoreline">
        <div class="habs-team">@include('modules.habs.logo', ['team' => $away, 'class' => 'big'])<span>{{ $away['name'] }}</span></div>
        <b class="habs-big">{{ $away['score'] }}<small>–</small>{{ $home['score'] }}</b>
        <div class="habs-team">@include('modules.habs.logo', ['team' => $home, 'class' => 'big'])<span>{{ $home['name'] }}</span></div>
      </div>

      @if ($d)
        <table class="habs-table">
          <tr><th></th>@foreach ($d['periods'] as $p)<th>{{ $p['label'] }}</th>@endforeach<th>T</th><th>SOG</th><th>PP</th></tr>
          <tr><td>{{ $away['abbrev'] }}</td>@foreach ($d['periods'] as $p)<td>{{ $p['away'] }}</td>@endforeach<td><b>{{ $away['score'] }}</b></td><td>{{ $d['shots']['away'] ?? '–' }}</td><td>{{ $d['powerPlay']['away'] ?? '–' }}</td></tr>
          <tr><td>{{ $home['abbrev'] }}</td>@foreach ($d['periods'] as $p)<td>{{ $p['home'] }}</td>@endforeach<td><b>{{ $home['score'] }}</b></td><td>{{ $d['shots']['home'] ?? '–' }}</td><td>{{ $d['powerPlay']['home'] ?? '–' }}</td></tr>
        </table>

        <div class="habs-goals">
          @forelse ($d['goals'] as $goal)
            <div class="habs-goal {{ $goal['team'] === 'MTL' ? 'mtl' : '' }}">
              <span class="habs-when">{{ $goal['period'] }} {{ $goal['time'] }}</span>
              <span class="habs-goal-team">{{ $goal['team'] }}</span>
              <span><b>{{ $goal['scorer'] }}</b>@if ($goal['total']) ({{ $goal['total'] }})@endif
                @if ($goal['strength'] !== 'EV')<em>{{ $goal['strength'] }}</em>@endif
                <small>{{ $goal['assists'] === [] ? 'Unassisted' : implode(', ', $goal['assists']) }}</small></span>
            </div>
          @empty
            <p class="habs-muted">No goals yet.</p>
          @endforelse
        </div>

        @if ($d['goalies'] !== [])
          <div class="habs-goalies">
            @foreach ($d['goalies'] as $goalie)
              <span>{{ $goalie['side'] === 'home' ? $home['abbrev'] : $away['abbrev'] }} · <b>{{ $goalie['name'] }}</b> {{ $goalie['saves'] }}/{{ $goalie['shots'] }} saves @if ($goalie['decision'])<em>{{ $goalie['decision'] }}</em>@endif</span>
            @endforeach
          </div>
        @endif
      @else
        <p class="habs-muted">Game details are not available right now.</p>
      @endif
    @else
      <p class="habs-muted">No games played yet this season.</p>
    @endif
  </section>

  <aside class="habs-side">
    @if ($standing)
      <section class="habs-panel">
        <p class="label">Standing</p>
        <p class="habs-record">{{ $standing['record'] }} <small>{{ $standing['points'] }} pts</small></p>
        <p class="habs-muted">{{ $standing['division'] }} #{{ $standing['divisionRank'] }} · {{ $standing['conference'] }} #{{ $standing['conferenceRank'] }}</p>
        <p class="habs-muted">Streak {{ $standing['streak'] }} · Last 10 {{ $standing['lastTen'] }}</p>
      </section>
    @endif

    @if ($recent !== [])
      <section class="habs-panel">
        <p class="label">Last {{ count($recent) }}</p>
        <div class="habs-chips">
          @foreach ($recent as $r)
            @php($won = $r['mtl']['score'] > $r['opponent']['score'])
            <span class="habs-chip {{ $won ? 'habs-w' : 'habs-l' }}"><b>{{ $won ? 'W' : 'L' }}</b> {{ $r['mtl']['score'] }}–{{ $r['opponent']['score'] }} {{ $r['atHome'] ? 'vs' : '@' }} {{ $r['opponent']['abbrev'] }}{{ $r['lastPeriodType'] && $r['lastPeriodType'] !== 'REG' ? ' '.$r['lastPeriodType'] : '' }}</span>
          @endforeach
        </div>
      </section>
    @endif

    @if ($next !== [])
      <section class="habs-panel">
        <p class="label">Next games</p>
        @foreach ($next as $n)
          <div class="habs-next">
            @include('modules.habs.logo', ['team' => $n['opponent']])
            <span><b>{{ $n['atHome'] ? 'vs' : 'at' }} {{ $n['opponent']['name'] }}</b><small>{{ $n['when'] }}</small></span>
          </div>
        @endforeach
      </section>
    @endif
  </aside>
</div>
@endif
