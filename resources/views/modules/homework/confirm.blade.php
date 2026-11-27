<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('Confirm homework') }}</title>
  <style>
    :root { color-scheme: light dark; font: 17px/1.45 system-ui, sans-serif; }
    body { margin: 0; background: light-dark(#f2f2f7, #151518); color: light-dark(#202124, #f2f2f7); }
    main { max-width: 26rem; margin: clamp(1rem, 10vh, 6rem) auto 0; padding: 1.5rem; }
    h1 { font-size: 1.65rem; margin: 0 0 .5rem; }
    form { display: grid; gap: .65rem; margin-top: 1.5rem; }
    input, button { box-sizing: border-box; width: 100%; font: inherit; border-radius: .65rem; padding: .7rem .8rem; }
    input { border: 1px solid light-dark(#777, #aaa); }
    button { border: 0; background: #006dce; color: white; font-weight: 650; cursor: pointer; }
    .error { color: light-dark(#b51915, #ffaaa7); }
  </style>
</head>
<body>
  <main>
    <h1>{{ $task['status'] === 'approved' ? __('Homework confirmed') : __('Confirm homework') }}</h1>
    <p>{{ $kid['name'] }} · {{ __(\App\Modules\Homework\HomeworkModule::TEMPLATES[$task['template']]['name']) }} · <time datetime="{{ $date }}">{{ $date }}</time></p>
    @if ($task['status'] === 'approved')
      <p>{{ __(':n points awarded.', ['n' => $task['points']]) }}</p>
    @else
      <p>{{ __('After checking the worksheet, enter your parent PIN to award :n points.', ['n' => $task['points']]) }}</p>
      @if ($error)
        <p class="error" id="pin-error" role="alert">{{ $error }}</p>
      @endif
      <form method="post" action="{{ request()->getRequestUri() }}">
        @csrf
        <label for="parent-pin">{{ __('Parent PIN') }}</label>
        <input id="parent-pin" name="pin" type="password" inputmode="numeric" autocomplete="off" maxlength="64" required @if ($error) aria-invalid="true" aria-describedby="pin-error" @endif>
        <button type="submit">{{ __('Confirm completion') }}</button>
      </form>
    @endif
  </main>
</body>
</html>
