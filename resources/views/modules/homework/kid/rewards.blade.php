{{-- Rewards: what the points can buy. A parent confirms each one with the parent PIN. --}}
<div class="kz kz-rewards" data-screen="rewards">
  @include('modules.homework.kid._head', ['title' => 'Rewards'])
  @isset($bought)
    <p class="kz-toast" role="status">Enjoy! {{ $bought }} is yours.</p>
  @endisset
  @if ($waiting > 0)
    <p class="kz-hint">+{{ $waiting }} points are waiting for a parent check.</p>
  @endif
  @if ($rewards === [])
    <div class="kz-empty">
      <span class="kz-empty-icon">@include('modules.homework.kid._icon', ['name' => 'rewards', 'size' => 44])</span>
      <b>Keep going!</b>
      @if ($next)
        <p>{{ $next['need'] - $balance }} more points to get “{{ $next['name'] }}”.</p>
        <span class="kz-bar kz-empty-bar" aria-hidden="true"><i style="width: {{ $next['progress'] }}%"></i></span>
      @else
        <p>No rewards to get right now. Come back tonight or this weekend.</p>
      @endif
    </div>
  @else
  <div class="kz-scroll">
    @foreach (['evening' => 'Tonight', 'weekend' => 'Weekend prizes'] as $when => $heading)
      @continue(collect($rewards)->where('when', $when)->isEmpty())
      <h3 class="kz-h">{{ $heading }}</h3>
      @if ($when === 'evening' && $homeworkFirst)
        <p class="kz-quiet">Tonight’s rewards open once today’s homework is checked.</p>
      @endif
      <ul class="kz-rewards-list">
        @foreach (collect($rewards)->where('when', $when) as $reward)
          <li class="kz-reward is-{{ $reward['state'] }}" data-state="{{ $reward['state'] }}">
            <span class="kz-reward-text">
              <b>{{ $reward['name'] }}</b>
              <small>
                @if ($reward['state'] === 'ready') You can get it now{{ $reward['note'] !== '' ? ' · '.$reward['note'] : '' }}
                @elseif ($reward['state'] === 'reach') Yours once your homework is checked
                @else {{ $reward['why'] }}{{ $reward['note'] !== '' && $reward['note'] !== $reward['why'] ? ' · '.$reward['note'] : '' }}
                @endif
              </small>
              @if ($reward['state'] !== 'ready')
                <span class="kz-bar" aria-hidden="true"><i style="width: {{ $reward['progress'] }}%"></i></span>
              @endif
            </span>
            @if ($reward['state'] === 'ready')
              <button type="button" class="kz-btn" data-parent-open="{{ route('modules.homework.redeem', [$kid['id'], $reward['id']]) }}" data-parent-title="{{ $reward['name'] }} · {{ $reward['cost'] }} points">Get · {{ $reward['cost'] }}</button>
            @else
              <span class="kz-cost">{{ $reward['need'] }}</span>
            @endif
          </li>
        @endforeach
      </ul>
    @endforeach
    @if ($next)
      <p class="kz-quiet kz-next">Next: {{ $next['name'] }} · {{ $next['need'] - $balance }} more points</p>
    @endif
  </div>
  @endif
  @include('modules.homework.kid._parent')
</div>
