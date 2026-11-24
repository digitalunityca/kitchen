@php($game = $item['game'])
<span class="habs-item">
  @if ($item['kind'] === 'live')
    <span class="habs-live"><i></i>Live</span>
    @include('modules.habs.logo', ['team' => $game['mtl']])
    <b class="habs-score">{{ $game['mtl']['score'] }} – {{ $game['opponent']['score'] }}</b>
    @include('modules.habs.logo', ['team' => $game['opponent']])
    <span>{{ $item['status'] }}</span>
    <span class="habs-sep">·</span>
    <span>{{ $game['mtl']['name'] }} {{ $game['atHome'] ? 'vs' : 'at' }} {{ $game['opponent']['name'] }}</span>
  @elseif ($item['kind'] === 'final')
    @include('modules.habs.logo', ['team' => $game['mtl']])
    <b class="habs-score">{{ $game['mtl']['score'] }} – {{ $game['opponent']['score'] }}</b>
    @include('modules.habs.logo', ['team' => $game['opponent']])
    <span>{{ $item['status'] }}</span>
    <span class="habs-result habs-{{ strtolower($item['result']) }}">{{ $item['result'] }}</span>
    <span class="habs-sep">·</span>
    <span>{{ $game['mtl']['name'] }} {{ $game['atHome'] ? 'vs' : 'at' }} {{ $game['opponent']['name'] }}</span>
  @else
    @include('modules.habs.logo', ['team' => $game['mtl']])
    <b>{{ $game['mtl']['name'] }} {{ $game['atHome'] ? 'vs' : 'at' }} {{ $game['opponent']['name'] }}</b>
    @include('modules.habs.logo', ['team' => $game['opponent']])
    <span>{{ $item['when'] }}</span>
    @if ($game['venue'])<span class="habs-sep">·</span><span>{{ $game['venue'] }}</span>@endif
    @if ($game['broadcasts'] !== [])<span class="habs-sep">·</span><span>TV {{ implode(', ', $game['broadcasts']) }}</span>@endif
  @endif
  <span class="habs-gap" aria-hidden="true"></span>
</span>
