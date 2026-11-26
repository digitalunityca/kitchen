{{-- A parent's check: the parent PIN, then the decision. Opened over Rewards and History. --}}
<div class="kz-parent" data-parent data-pad hidden role="dialog" aria-modal="true" aria-labelledby="kz-parent-title-{{ $kid['id'] }}">
  <div class="kz-parent-card">
    <span class="kz-parent-lock">@include('modules.homework.kid._icon', ['name' => 'lock', 'size' => 22])</span>
    <h3 id="kz-parent-title-{{ $kid['id'] }}" data-parent-title>Parent check</h3>
    <p class="kz-sub">Parent PIN, then ✓</p>
    <p class="kz-error" role="alert" data-error hidden></p>
    @include('modules.homework.kid._keypad', ['okLabel' => 'Confirm'])
    <div class="kz-parent-actions">
      <button type="button" class="kz-btn ghost" data-decision="cancel">Cancel</button>
    </div>
  </div>
</div>
