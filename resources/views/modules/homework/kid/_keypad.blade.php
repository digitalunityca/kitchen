{{-- A numeric keypad. Digits go to the closest [data-pad]; OK submits it. --}}
<div class="kz-dots" data-dots aria-hidden="true"><i></i><i></i><i></i><i></i></div>
<div class="kz-keys" role="group" aria-label="PIN keypad">
  @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
    <button type="button" data-key="{{ $digit }}">{{ $digit }}</button>
  @endforeach
  <button type="button" data-key="del" class="kz-key-soft" aria-label="Delete">@include('modules.homework.kid._icon', ['name' => 'delete', 'size' => 28])</button>
  <button type="button" data-key="0">0</button>
  <button type="button" data-key="ok" class="kz-key-ok" aria-label="{{ $okLabel ?? 'OK' }}" disabled>@include('modules.homework.kid._icon', ['name' => 'ok', 'size' => 30])</button>
</div>
