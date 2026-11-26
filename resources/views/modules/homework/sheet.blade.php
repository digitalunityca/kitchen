{{-- Inside a kid's homework sheet: pick today's homework, the picked tasks, and the evening rewards lit up by points. Fetched when the sheet opens and after each tap. --}}
<div class="hw" data-balance="{{ $balance }}" data-pending="{{ $pending }}" data-add="{{ route('modules.homework.add', $kid['id']) }}">
  <div class="hw-top">
    <span class="hw-points">{{ $balance }} points</span>
    @if ($tasks !== [])
      <span class="hw-lock {{ $left === 0 ? 'open' : '' }}">{{ $left === 0 ? 'Evening rewards unlocked' : 'Rewards unlock when all tasks are checked ('.$left.' left)' }}</span>
    @endif
    @if ($schoolDay)
      <span class="hw-addbar">
        <span class="hw-sel">Picked <b>+<span data-selected>0</span></b></span>
        <button type="button" class="hw-btn" data-add-go disabled>Next</button>
      </span>
    @endif
  </div>
  <p class="hw-error" data-add-error hidden></p>

  @if (! $schoolDay && $tasks === [])
    <div class="kid-empty">
      <span aria-hidden="true">🎉</span>
      <b>No homework today</b>
      <p>Homework is Monday to Friday. Enjoy your day!</p>
    </div>
  @else
    @if ($schoolDay)
      <h3 class="hw-h">Pick today’s homework</h3>
      <div class="hw-choices">
        @foreach ($choices as $choice)
          <div class="hw-choice {{ $choice['left'] === 0 ? 'full' : '' }}" data-template="{{ $choice['key'] }}" data-points="{{ $choice['points'] }}" data-left="{{ $choice['left'] }}">
            <span class="hw-text"><b>{{ $choice['name'] }}</b><small>+{{ $choice['points'] }} pts{{ $choice['key'] === 'reading' ? ' · '.$readingMinutes.' min' : '' }}</small></span>
            @if ($choice['left'] === 0)
              <span class="hw-max">✓</span>
            @else
              <span class="hw-step">
                <button type="button" data-step="-1" aria-label="Less">−</button>
                <output>0</output>
                <button type="button" data-step="1" aria-label="More">+</button>
              </span>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if ($tasks !== [])
      <div class="hw-list-head">
        <h3 class="hw-h">My homework</h3>
        @if ($left > 0)
          <a class="hw-btn ghost" data-open-packet href="{{ route('modules.homework.packet', [$kid['id'], $date], absolute: false) }}">Open worksheets</a>
        @endif
      </div>
      <ul class="hw-list">
        @foreach ($tasks as $task)
          @php($t = $templates[$task['template']])
          <li class="hw-task is-{{ $task['status'] }}" data-task="{{ $task['id'] }}">
            <span class="hw-text">
              <b>{{ $t['name'] }}</b>
              <small>{{ $task['template'] === 'reading' ? $readingMinutes.' min of reading' : 'Worksheet' }} · {{ $task['points'] }} points</small>
            </span>
            @if ($task['status'] === 'todo')
              <button type="button" class="hw-x" data-action="{{ route('modules.homework.remove', [$kid['id'], $task['id']]) }}" aria-label="Remove">✕</button>
              <button type="button" class="hw-btn" data-action="{{ route('modules.homework.done', [$kid['id'], $task['id']]) }}">I’m done</button>
            @elseif ($task['status'] === 'done')
              <span class="hw-wait">
                <small>Waiting for an adult</small>
                <button type="button" class="hw-btn ghost" data-review="{{ route('modules.homework.approve', [$kid['id'], $task['id']]) }}">Check ✓</button>
              </span>
            @else
              <span class="hw-ok">✓ +{{ $task['points'] }}</span>
            @endif
          </li>
        @endforeach
      </ul>

    @endif
  @endif

  <div class="hw-stats">
    <span><b>{{ $stats['weekTasks'] }}</b> tasks this week</span>
    <span><b>+{{ $stats['weekPoints'] }}</b> points this week</span>
    <span><b>{{ $stats['totalTasks'] }}</b> tasks in total</span>
    <span><b>{{ $stats['earned'] }}</b> earned · <b>{{ $stats['spent'] }}</b> spent</span>
  </div>

  @foreach (['evening' => 'Tonight', 'weekend' => 'Weekend prizes (save up!)'] as $when => $title)
    <h3 class="hw-h">{{ $title }}</h3>
    <div class="hw-rewards">
      @foreach (collect($rewards)->where('when', $when) as $reward)
        <div class="hw-reward {{ $reward['state'] === 'ready' ? 'ready' : '' }}" data-need="{{ $reward['need'] }}">
          <span class="hw-text"><b>{{ $reward['name'] }}</b><small>{{ $reward['note'] }}</small>
            @if ($when === 'weekend')<span class="hw-bar"><i style="width: {{ min(100, (int) round($balance * 100 / $reward['cost'])) }}%"></i></span>@endif
          </span>
          @if ($reward['state'] === 'ready')
            <button type="button" class="hw-btn" data-review="{{ route('modules.homework.redeem', [$kid['id'], $reward['id']]) }}" data-buy="{{ $reward['name'] }} · {{ $reward['cost'] }} points">Get · {{ $reward['cost'] }}</button>
          @else
            <span class="hw-cost">{{ isset($reward['after']) ? '+' : '' }}{{ $reward['cost'] }}</span>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach
  <p class="hw-legend"><span class="sw ready"></span> can get it now <span class="sw reach"></span> reachable once your picked homework is checked</p>
  <div class="hw-review" hidden>
    <p class="hw-review-title">Adult check</p>
    <input type="password" inputmode="numeric" autocomplete="off" class="hw-pin" placeholder="PIN" hidden>
    <div class="hw-review-actions">
      <button type="button" class="hw-btn" data-decision="approve">Approve</button>
      <button type="button" class="hw-btn ghost" data-decision="redo">Redo</button>
      <button type="button" class="hw-btn ghost" data-decision="cancel">Cancel</button>
    </div>
    <p class="hw-error" hidden></p>
  </div>
</div>
