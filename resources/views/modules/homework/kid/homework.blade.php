{{-- Homework: pick today's sheets, then Print all. The worksheets are made only when Print all is tapped. --}}
<div class="kz kz-homework" data-screen="homework" data-print="{{ route('modules.homework.print', $kid['id']) }}">
  @include('modules.homework.kid._head', ['title' => 'Homework'])
  @if (! $schoolDay)
    <div class="kz-empty">
      <span class="kz-empty-icon">@include('modules.homework.kid._icon', ['name' => 'homework', 'size' => 44])</span>
      <b>No homework today</b>
      <p>Homework is Monday to Friday. Enjoy your day!</p>
    </div>
  @else
    <div class="kz-pick" data-pick>
      <p class="kz-hint">Tap what you’ll do today. Tap again to add one more.</p>
      <div class="kz-choices">
        @foreach ($choices as $choice)
          <button type="button" class="kz-choice" data-choice="{{ $choice['key'] }}" data-points="{{ $choice['points'] }}" data-left="{{ $choice['left'] }}" aria-pressed="false" @disabled($choice['left'] === 0)>
            <span class="kz-choice-icon">@include('modules.homework.kid._icon', ['name' => $choice['key'], 'size' => 36])</span>
            <span class="kz-choice-count" data-count hidden></span>
            <b>{{ $choice['name'] }}</b>
            <small>+{{ $choice['points'] }} points{{ $choice['key'] === 'reading' ? ' · '.$readingMinutes.' min' : '' }}</small>
            <small class="kz-choice-left">{{ $choice['left'] === 0 ? 'Done for today' : ($choice['already'] > 0 ? $choice['left'].' more today' : ($choice['max'] > 1 ? 'Up to '.$choice['max'].' today' : '1 today')) }}</small>
          </button>
        @endforeach
      </div>
      <div class="kz-printbar">
        <p class="kz-printsum" data-summary aria-live="polite">Pick at least one sheet</p>
        <p class="kz-error" role="alert" data-error hidden></p>
        <button type="button" class="kz-print" data-print-all disabled>
          @include('modules.homework.kid._icon', ['name' => 'printer', 'size' => 26])<span data-print-label>Print all</span>
        </button>
      </div>
    </div>
    <div class="kz-done" data-done hidden tabindex="-1">
      <span class="kz-done-icon">@include('modules.homework.kid._icon', ['name' => 'printer', 'size' => 52])</span>
      <b>Done! Your sheets are printing 🖨️</b>
      <p data-done-note>Do your sheets, then ask a parent to check them. Your points count once they’re checked.</p>
      <button type="button" class="kz-print" data-go="home">Back to my home</button>
    </div>
  @endif
</div>
