{{-- Homework: pick today's sheets, then Print all. The worksheets are made only when Print all is tapped. --}}
<div class="kz kz-homework" data-screen="homework" data-print="{{ route('modules.homework.print', $kid['id']) }}" data-max="{{ \App\Actions\Homework\AddHomeworkTasks::MAX_PER_PRINT }}">
  @include('modules.homework.kid._head', ['title' => 'Homework'])
  @if (! $schoolDay)
    <div class="kz-empty">
      <span class="kz-empty-icon">@include('modules.homework.kid._icon', ['name' => 'homework', 'size' => 44])</span>
      <b>{{ __('No homework today') }}</b>
      <p>{{ __('Homework is Monday to Friday. Enjoy your day!') }}</p>
    </div>
  @else
    <div class="kz-pick" data-pick>
      <p class="kz-hint">{{ __('Tap a sheet to add it. Tap again for one more, − to take one off.') }}</p>
      <div class="kz-choices">
        @foreach ($choices as $choice)
          <div class="kz-choice-wrap">
            <button type="button" class="kz-choice" data-choice="{{ $choice['key'] }}" data-points="{{ $choice['points'] }}" aria-pressed="false">
              <span class="kz-choice-icon">@include('modules.homework.kid._icon', ['name' => $choice['key'], 'size' => 36])</span>
              <span class="kz-choice-count" data-count hidden></span>
              <b>{{ __($choice['name']) }}</b>
              <small>+{{ $choice['points'] }} {{ __('points') }}{{ $choice['key'] === 'reading' ? ' · '.$readingMinutes.' min' : '' }}</small>
              @if ($choice['already'] > 0)<small class="kz-choice-left">{{ __(':n printed today', ['n' => $choice['already']]) }}</small>@endif
            </button>
            <button type="button" class="kz-minus" data-minus="{{ $choice['key'] }}" aria-label="{{ __('One less :name', ['name' => __($choice['name'])]) }}" hidden>−</button>
          </div>
        @endforeach
      </div>
      <div class="kz-printbar">
        <p class="kz-printsum" data-summary aria-live="polite">{{ __('Pick at least one sheet') }}</p>
        <p class="kz-error" role="alert" data-error hidden></p>
        <button type="button" class="kz-print" data-print-all disabled>
          @include('modules.homework.kid._icon', ['name' => 'printer', 'size' => 26])<span data-print-label>{{ __('Print all') }}</span>
        </button>
      </div>
    </div>
    <div class="kz-done" data-done hidden tabindex="-1">
      <span class="kz-done-icon">@include('modules.homework.kid._icon', ['name' => 'printer', 'size' => 52])</span>
      <b>{{ __('Done! Your sheets are printing 🖨️') }}</b>
      <p data-done-note>{{ __('Do your sheets, then ask a parent to check them. Your points count once they’re checked.') }}</p>
      <button type="button" class="kz-print" data-go="home">{{ __('Back to my home') }}</button>
    </div>
  @endif
</div>
