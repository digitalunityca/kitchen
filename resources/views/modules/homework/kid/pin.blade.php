{{-- The kid's sign-in: their own 4-digit PIN, or a parent's PIN. --}}
<div class="kz kz-pin" data-screen="pin" data-pad data-submit="{{ route('modules.homework.unlock', $kid['id']) }}">
  <div class="kz-pin-who">
    @include('modules.homework.kid._avatar', ['big' => true])
    <h2>Hi {{ $kid['name'] }}!</h2>
    @if ($hasPin)
      <p>Type your PIN</p>
    @else
      <p>Ask a parent to set your PIN in Settings.<br>A parent can open it now with their PIN.</p>
    @endif
    <p class="kz-error" role="alert" data-error hidden></p>
  </div>
  <div class="kz-pad">
    @include('modules.homework.kid._keypad', ['okLabel' => 'Open'])
  </div>
</div>
