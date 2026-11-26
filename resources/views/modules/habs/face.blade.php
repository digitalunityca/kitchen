{{-- The game side of the weather card on Canadiens game days: logos, time or score, and the details. --}}
@php($game = $item['game'])
<div class="habs-face habs-face-{{ $item['kind'] }}" role="button" tabindex="0" aria-label="Canadiens game: open scores and stats">
  <div class="row">
    <p class="label">
      @if ($item['kind'] === 'live')<span class="habs-live"><i></i>Live</span>
      @elseif ($item['kind'] === 'final') Canadiens · Final
      @else Canadiens · Game day
      @endif
    </p>
    <button type="button" class="habs-turn" data-habs-turn aria-label="Show the weather">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>Turn
    </button>
  </div>
  <div class="habs-face-main">
    @include('modules.habs.logo', ['team' => $game['mtl'], 'class' => 'face'])
    <div class="habs-face-mid">
      @if ($item['kind'] === 'upcoming')
        <b class="habs-face-time">{{ $game['start']->setTimezone(\App\Actions\Habs\FetchHabsSchedule::TIMEZONE)->format('g:i') }}<small>{{ $game['start']->setTimezone(\App\Actions\Habs\FetchHabsSchedule::TIMEZONE)->format('A') }}</small></b>
      @else
        <b class="habs-face-score">{{ $game['mtl']['score'] }}<span>–</span>{{ $game['opponent']['score'] }}</b>
      @endif
    </div>
    @include('modules.habs.logo', ['team' => $game['opponent'], 'class' => 'face'])
  </div>
  <p class="habs-face-teams">{{ $game['mtl']['name'] }} {{ $game['atHome'] ? 'vs' : 'at' }} {{ $game['opponent']['name'] }}</p>
  <div class="habs-face-foot">
    @if ($item['kind'] === 'live')
      <span>{{ $item['status'] }}</span>
    @elseif ($item['kind'] === 'final')
      <span>{{ $item['status'] }}</span><span class="habs-result habs-{{ strtolower($item['result']) }}">{{ $item['result'] === 'W' ? 'Win' : 'Loss' }}</span>
    @else
      <span>{{ $item['when'] }}</span>
    @endif
    @if ($game['venue'])<span class="habs-sep">·</span><span>{{ $game['venue'] }}</span>@endif
    @if ($item['kind'] === 'upcoming' && $game['broadcasts'] !== [])<span class="habs-sep">·</span><span>{{ implode(', ', $game['broadcasts']) }}</span>@endif
  </div>
</div>
