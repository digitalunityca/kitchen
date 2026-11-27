{{-- The game side of the weather card on Canadiens game days: logos, time or score, and the details. --}}
@php($game = $item['game'])
<div class="habs-face habs-face-{{ $item['kind'] }}" role="button" tabindex="0" aria-label="{{ __('Canadiens game: open scores and stats') }}">
  <div class="row">
    <p class="label">
      @if ($item['kind'] === 'live')<span class="habs-live"><i></i>{{ __('Live') }}</span>
      @elseif ($item['kind'] === 'final') Canadiens · {{ __('Final') }}
      @else Canadiens · {{ __('Game day') }}
      @endif
    </p>
    <button type="button" class="habs-turn" data-habs-turn aria-label="{{ __('Show the weather') }}">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>{{ __('Turn') }}
    </button>
  </div>
  <div class="habs-face-main">
    @include('modules.habs.logo', ['team' => $game['mtl'], 'class' => 'face'])
    <div class="habs-face-mid">
      @if ($item['kind'] === 'upcoming')
        @php($start = $game['start']->setTimezone(\App\Actions\Habs\FetchHabsSchedule::TIMEZONE))
        @if (\App\Support\TimeFormat::french())
          <b class="habs-face-time">{{ $start->format('G') }}<small>h</small>{{ $start->format('i') }}</b>
        @else
          <b class="habs-face-time">{{ $start->format('g:i') }}<small>{{ $start->format('A') }}</small></b>
        @endif
      @else
        <b class="habs-face-score">{{ $game['mtl']['score'] }}<span>–</span>{{ $game['opponent']['score'] }}</b>
      @endif
    </div>
    @include('modules.habs.logo', ['team' => $game['opponent'], 'class' => 'face'])
  </div>
  <p class="habs-face-teams">{{ $game['mtl']['name'] }} {{ $game['atHome'] ? __('vs') : __('at') }} {{ $game['opponent']['name'] }}</p>
  <div class="habs-face-foot">
    @if ($item['kind'] === 'live')
      <span>{{ $item['status'] }}</span>
    @elseif ($item['kind'] === 'final')
      <span>{{ $item['status'] }}</span><span class="habs-result habs-{{ strtolower($item['result']) }}">{{ $item['result'] === 'W' ? __('Win') : __('Loss') }}</span>
    @else
      <span>{{ $item['when'] }}</span>
    @endif
    @if ($game['venue'])<span class="habs-sep">·</span><span>{{ $game['venue'] }}</span>@endif
    @if ($item['kind'] === 'upcoming' && $game['broadcasts'] !== [])<span class="habs-sep">·</span><span>{{ implode(', ', $game['broadcasts']) }}</span>@endif
  </div>
</div>
