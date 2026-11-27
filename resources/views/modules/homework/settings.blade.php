<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Homework') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
  body { overflow: auto; -webkit-user-select: auto; user-select: auto; }
  @media (prefers-color-scheme: dark) { body { --bg:#000; --card:#1C1C1E; --text:#F5F5F7; --sub:#A1A1A6; --line:#2C2C2E; --fill:#2C2C2E; --accent:#0A84FF; --shadow:none; } }
  .page { max-width: 680px; margin: 0 auto; padding: 40px 16px 64px; display: flex; flex-direction: column; gap: 24px; }
  .card { padding: 24px; gap: 14px; justify-content: flex-start; }
  h1 { font-size: 30px; }
  h2 { margin: 0; font-size: 22px; display: flex; align-items: center; gap: 10px; }
  h3 { margin: 6px 0 0; font-size: 15px; color: var(--sub); font-weight: 600; }
  p { margin: 0; line-height: 1.5; }
  .avatar { width: 34px; height: 34px; border-radius: 999px; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 17px; }
  .total { margin-left: auto; font-size: 17px; font-weight: 600; }
  ul { list-style: none; margin: 0; padding: 0; }
  li { padding: 10px 0; border-top: 1px solid var(--line); display: flex; align-items: center; gap: 10px; font-size: 15px; }
  li:first-child { border-top: 0; }
  li .grow { flex: 1; min-width: 0; }
  li small { color: var(--sub); }
  a { color: var(--accent); }
  .links a { margin-left: 12px; white-space: nowrap; }
  textarea { width: 100%; min-height: 160px; font: inherit; font-size: 16px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 12px 14px; }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 12px 18px; font-weight: 600; font-size: 16px; align-self: flex-start; }
  .ok { color: #34C759; font-weight: 500; }
  .err { color: #FF3B30; font-size: 14px; }
  .plus { color: #248A3D; font-weight: 600; }
</style>
</head>
<body>
<div class="page">
  <div>
    <p class="eyebrow">{{ __('Homework') }}</p>
    <h1>{{ __('Kids’ homework') }}</h1>
  </div>

  @if (session('status')) <p class="ok">{{ session('status') }}</p> @endif
  <p>{{ __('Open a worksheet on this computer and press Print (⌘P) to print it. The answer key shows the same sheet with the answers.') }}</p>

  @foreach ($kids as $kid)
    <div class="card">
      <h2><span class="avatar" style="background: {{ $kid['color'] }}">{{ $kid['badge'] }}</span>{{ $kid['name'] }} <small style="color: var(--sub); font-size: 15px; font-weight: 400">{{ __(':grade grade', ['grade' => $kid['grade']]) }}</small><span class="total">⭐ {{ __(':n points', ['n' => $kid['balance']]) }}</span></h2>

      <h3>{{ __('Today') }}</h3>
      @if ($kid['tasks'] === [])
        <p>{{ __('No homework today (Monday to Friday only).') }}</p>
      @else
        <ul>
          @foreach ($kid['tasks'] as $task)
            <li>
              <span>{{ $templates[$task['template']]['emoji'] }}</span>
              <span class="grow">{{ __($templates[$task['template']]['name']) }} <small>· {{ $task['points'] }} pts · {{ __(['todo' => 'to do', 'done' => 'waiting for a check', 'approved' => 'approved', 'missed' => 'not done'][$task['status']] ?? $task['status']) }}</small></span>
              @if (isset($task['worksheet']) || $templates[$task['template']]['printable'])
                <span class="links">
                  @if (isset($task['worksheet']) || $task['status'] === 'approved')
                    <a href="{{ route('modules.homework.worksheet', [$kid['id'], $today, $task['id']]) }}" target="_blank">{{ __('Worksheet') }}</a>
                    <a href="{{ route('modules.homework.answers', [$kid['id'], $today, $task['id']]) }}" target="_blank">{{ __('Answer key') }}</a>
                  @else
                    <a href="{{ route('modules.homework.packet', [$kid['id'], $today]) }}">{{ __('Open worksheets') }}</a>
                  @endif
                </span>
              @endif
            </li>
          @endforeach
        </ul>
      @endif

      @if ($kid['history'] !== [])
        <h3>{{ __('Recent points') }}</h3>
        <ul>
          @foreach ($kid['history'] as $entry)
            <li><span class="grow">{{ __($entry['reason']) }}</span><span class="plus">{{ $entry['points'] > 0 ? '+' : '' }}{{ $entry['points'] }}</span></li>
          @endforeach
        </ul>
      @endif

    </div>
  @endforeach

  <p><a href="{{ route('settings') }}">← {{ __('Settings') }}</a> · <a href="{{ route('dashboard') }}">{{ __('Back to the dashboard') }}</a></p>
</div>
</body>
</html>
