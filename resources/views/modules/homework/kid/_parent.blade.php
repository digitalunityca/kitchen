{{-- A parent's check: the parent PIN, then the decision. Opened over Rewards and History. --}}
<div class="kz-parent" data-parent data-pad hidden role="dialog" aria-modal="true" aria-labelledby="kz-parent-title-{{ $kid['id'] }}">
  <div class="kz-parent-card">
    <span class="kz-parent-lock">@include('modules.homework.kid._icon', ['name' => 'lock', 'size' => 22])</span>
    <h3 id="kz-parent-title-{{ $kid['id'] }}" data-parent-title>{{ __('Parent check') }}</h3>
    <p class="kz-sub" data-parent-hint>{{ __('Parent PIN, then ✓') }}</p>
    <p class="kz-error" role="alert" data-error hidden></p>
    @include('modules.homework.kid._keypad', ['okLabel' => 'Confirm'])
    {{-- A sheet's approval: after the PIN, how it went (sets the points and, later, the next level). --}}
    <div class="kz-scores" data-scores hidden>
      <button type="button" class="kz-score-btn is-bien" data-decision="bien"><b>{{ __('Good') }}</b><small data-score-points="bien"></small></button>
      <button type="button" class="kz-score-btn is-erreurs" data-decision="erreurs"><b>{{ __('A few mistakes') }}</b><small data-score-points="erreurs"></small></button>
      <button type="button" class="kz-score-btn is-revoir" data-decision="revoir"><b>{{ __('Go over again') }}</b><small data-score-points="revoir"></small></button>
    </div>
    <div class="kz-parent-actions">
      <button type="button" class="kz-btn ghost" data-decision="cancel">{{ __('Cancel') }}</button>
    </div>
  </div>
</div>
