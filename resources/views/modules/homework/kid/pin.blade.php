{{-- The kid's sign-in: their own 4-digit PIN, or a parent's PIN. --}}
<div class="kz kz-pin" data-screen="pin" data-pad data-max="{{ $hasPin ? 4 : 8 }}" data-submit="{{ route('modules.homework.unlock', $kid['id']) }}">
  <div class="kz-pin-who">
    @include('modules.homework.kid._avatar', ['big' => true])
    <h2>{{ __('Hi :name!', ['name' => $kid['name']]) }}</h2>
    @if ($hasPin)
      <p>{{ __('Type your PIN') }}</p>
    @else
      <p>{{ __('Ask a parent to set your PIN in Settings.') }}<br>{{ __('A parent can open it now with their PIN.') }}</p>
    @endif
    <p class="kz-error" role="alert" data-error hidden></p>
  </div>
  <div class="kz-pad">
    @include('modules.homework.kid._keypad', ['okLabel' => 'Open'])
  </div>
</div>
