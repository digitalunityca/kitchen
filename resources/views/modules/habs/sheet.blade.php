{{-- Canadiens scores and stats, loaded into the sheet when it opens. Three tabs, switched in the browser. --}}
@if (! $available)
  <p class="habs-empty">Can’t reach the NHL right now. Try again in a few minutes.</p>
@else
<div class="habs-tabs" data-default="{{ $defaultTab }}">
  <div class="habs-segmented" role="tablist" aria-label="Canadiens">
    <button type="button" role="tab" data-tab="game">Game</button>
    <button type="button" role="tab" data-tab="standings">Standings</button>
    <button type="button" role="tab" data-tab="schedule">Schedule</button>
  </div>

  <div class="habs-tab" data-panel="game" role="tabpanel">
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
  </div>

  <div class="habs-tab" data-panel="standings" role="tabpanel" hidden>
    @if ($tables)
      <section class="habs-panel">
        <div class="habs-standings-head">
          @if ($standing)
            <p class="habs-record">{{ $standing['record'] }} <small>{{ $standing['points'] }} pts · Streak {{ $standing['streak'] }} · Last 10 {{ $standing['lastTen'] }}</small></p>
          @endif
          <div class="habs-segmented habs-small" role="tablist" aria-label="Standings">
            <button type="button" data-table="division">{{ $tables['divisionName'] }}</button>
            <button type="button" data-table="conference">{{ $tables['conferenceName'] }}</button>
            <button type="button" data-table="league">League</button>
          </div>
        </div>
        @foreach (['division', 'conference', 'league'] as $scope)
          <table class="habs-standings" data-scope="{{ $scope }}" @unless ($scope === 'division') hidden @endunless>
            <tr><th>#</th><th colspan="2">Team</th><th>GP</th><th>W</th><th>L</th><th>OTL</th><th>PTS</th><th>P%</th><th>Strk</th></tr>
            @foreach ($tables[$scope] as $t)
              <tr class="{{ $t['abbrev'] === 'MTL' ? 'habs-us' : '' }} {{ $cut[$scope] === $t['rank'] ? 'habs-cut' : '' }}">
                <td>{{ $t['rank'] }}</td>
                <td class="habs-cell-logo">@include('modules.habs.logo', ['team' => $t])</td>
                <td class="habs-team-name">{{ $t['name'] }}</td>
                <td>{{ $t['gp'] }}</td><td>{{ $t['w'] }}</td><td>{{ $t['l'] }}</td><td>{{ $t['otl'] }}</td>
                <td><b>{{ $t['pts'] }}</b></td><td>{{ ltrim(number_format($t['pct'], 3), '0') }}</td><td>{{ $t['streak'] }}</td>
              </tr>
            @endforeach
          </table>
        @endforeach
        <p class="habs-muted habs-cut-note">Dashed line: playoff cut (top 3 in the division, top 8 in the conference).</p>
      </section>
    @else
      <p class="habs-muted">Standings are not available right now.</p>
    @endif
  </div>

  <div class="habs-tab" data-panel="schedule" role="tabpanel" hidden>
    <div class="habs-sheet-grid habs-even">
      <section class="habs-panel">
        <p class="label">Last {{ count($recent) }}</p>
        @forelse ($recent as $r)
          @php($won = $r['mtl']['score'] > $r['opponent']['score'])
          <div class="habs-row">
            <span class="habs-date">{{ $r['start']->format('D M j') }}</span>
            @include('modules.habs.logo', ['team' => $r['opponent']])
            <span class="habs-row-main">{{ $r['atHome'] ? 'vs' : 'at' }} {{ $r['opponent']['name'] }}</span>
            <span class="habs-chip {{ $won ? 'habs-w' : 'habs-l' }}"><b>{{ $won ? 'W' : 'L' }}</b> {{ $r['mtl']['score'] }}–{{ $r['opponent']['score'] }}{{ $r['lastPeriodType'] && $r['lastPeriodType'] !== 'REG' ? ' '.$r['lastPeriodType'] : '' }}</span>
          </div>
        @empty
          <p class="habs-muted">No games played yet.</p>
        @endforelse
      </section>
      <section class="habs-panel">
        <p class="label">Next {{ count($next) }}</p>
        @forelse ($next as $n)
          <div class="habs-row">
            <span class="habs-date">{{ $n['start']->format('D M j') }}</span>
            @include('modules.habs.logo', ['team' => $n['opponent']])
            <span class="habs-row-main">{{ $n['atHome'] ? 'vs' : 'at' }} {{ $n['opponent']['name'] }}<small>{{ implode(' · ', array_filter([$n['start']->format('g:i A'), $n['venue'], $n['broadcasts'] === [] ? null : 'TV '.implode(', ', $n['broadcasts'])])) }}</small></span>
          </div>
        @empty
          <p class="habs-muted">No upcoming games.</p>
        @endforelse
      </section>
    </div>
  </div>
</div>
@endif
