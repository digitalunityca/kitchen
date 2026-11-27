{{-- Aider à la maison: today's chores. The kid taps "I'm done", a parent checks it in History. --}}
<div class="kz kz-chores" data-screen="chores">
  @include('modules.homework.kid._head', ['title' => 'Help at home'])
  @isset($thanks)
    <p class="kz-toast" role="status">{{ __('Thanks for helping! A parent will check it.') }}</p>
  @endisset
  <div class="kz-scroll">
    @if ($chores === [])
      <p class="kz-quiet">{{ __('Nothing to do at home today.') }}</p>
    @else
      <ul class="kz-rewards-list">
        @foreach ($chores as $chore)
          @php($state = $chore['left'] > 0 ? 'ready' : ($chore['waiting'] > 0 ? 'waiting' : 'done'))
          <li class="kz-reward kz-chore is-{{ $state }}" data-chore="{{ $chore['id'] }}">
            <span class="kz-chore-emoji" aria-hidden="true">{{ $chore['emoji'] }}</span>
            <span class="kz-reward-text">
              <b>{{ $chore['name'] }}</b>
              <small>
                +{{ $chore['points'] }} {{ __('points') }}
                @if ($chore['waiting'] > 0 && $state !== 'waiting') · {{ __('Waiting for a parent') }}@endif
                @if ($chore['approved'] > 0) · {{ __('Approved') }}{{ $chore['approved'] > 1 ? ' ×'.$chore['approved'] : '' }}@endif
                @if ($chore['perDay'] > 1 && $chore['left'] > 0) · {{ __(':n more times today', ['n' => $chore['left']]) }}@endif
              </small>
            </span>
            @if ($chore['left'] > 0)
              <button type="button" class="kz-btn" data-chore-done="{{ route('modules.homework.chore.done', [$kid['id'], $chore['id']]) }}">{{ __('I’m done') }}</button>
            @elseif ($state === 'waiting')
              <span class="kz-badge is-check">{{ __('Waiting for a parent') }}</span>
            @else
              <span class="kz-chore-check" aria-label="{{ __('Done for today') }}">@include('modules.homework.kid._icon', ['name' => 'ok', 'size' => 26])</span>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
