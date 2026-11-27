<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Calendars') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
  body { overflow: auto; -webkit-user-select: auto; user-select: auto; }
  @media (prefers-color-scheme: dark) { body { --bg:#000; --card:#1C1C1E; --text:#F5F5F7; --sub:#A1A1A6; --line:#2C2C2E; --fill:#2C2C2E; --accent:#0A84FF; --shadow:none; } }
  .page { max-width: 640px; margin: 0 auto; padding: 40px 16px 64px; display: flex; flex-direction: column; gap: 24px; }
  .card { padding: 24px; gap: 16px; justify-content: flex-start; }
  h1 { font-size: 30px; }
  p { margin: 0; line-height: 1.5; }
  label { font-size: 13px; font-weight: 600; color: var(--sub); display: block; margin-bottom: 6px; }
  input[type=text], input[type=url] { width: 100%; font: inherit; font-size: 17px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 12px 14px; }
  input:focus { outline: none; border-color: var(--accent); }
  .btn { background: var(--accent); color: #fff; border-radius: 12px; padding: 12px 18px; font-weight: 600; font-size: 16px; }
  .err { color: #FF3B30; font-size: 14px; margin-top: 6px; }
  .ok { color: #34C759; font-weight: 500; }
  .swatches { display: flex; gap: 10px; flex-wrap: wrap; }
  .swatches label { margin: 0; }
  .swatches input { position: absolute; opacity: 0; }
  .swatches span { display: block; width: 34px; height: 34px; border-radius: 999px; border: 3px solid transparent; box-shadow: 0 0 0 1px var(--line); }
  .swatches input:checked + span { border-color: var(--card); box-shadow: 0 0 0 2px var(--text); }
  select { width: 100%; font: inherit; font-size: 17px; color: var(--text); background: var(--fill); border: 1px solid transparent; border-radius: 12px; padding: 12px 14px; }
  .check { display: flex; align-items: center; gap: 10px; margin-top: 10px; font-size: 15px; color: var(--text); font-weight: 500; }
  .check input { width: 20px; height: 20px; accent-color: var(--accent); }
  .icons { display: flex; gap: 8px; flex-wrap: wrap; }
  .icons label { margin: 0; }
  .icons input { position: absolute; opacity: 0; }
  .icons span { display: flex; align-items: center; justify-content: center; min-width: 44px; height: 44px; padding: 0 10px; border-radius: 12px; background: var(--fill); font-size: 22px; color: var(--text); }
  .icons input:checked + span { box-shadow: 0 0 0 2px var(--accent); }
  ul { list-style: none; margin: 0; padding: 0; }
  li { padding: 14px 0; border-top: 1px solid var(--line); display: flex; flex-direction: column; gap: 10px; }
  li:first-child { border-top: 0; }
  .line { display: flex; align-items: center; gap: 10px; }
  .dot { width: 14px; height: 14px; border-radius: 999px; flex: none; }
  .name { flex: 1; min-width: 0; font-weight: 500; }
  .name small { display: block; color: var(--sub); font-size: 12px; font-weight: 400; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .ghost { color: var(--sub); font-size: 14px; padding: 6px 10px; border-radius: 8px; background: var(--fill); }
  .danger { color: #FF3B30; }
  .off { opacity: .5; }
  details summary { cursor: pointer; color: var(--accent); font-size: 14px; }
  details form { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
  a { color: var(--accent); }
  ol { margin: 0; padding-left: 20px; line-height: 1.6; color: var(--sub); font-size: 15px; }
</style>
</head>
<body>
<div class="page">
  <div>
    <p class="eyebrow">{{ __('Calendar') }}</p>
    <h1>{{ __('Kitchen calendars') }}</h1>
  </div>

  @if (session('status')) <p class="ok">{{ session('status') }}</p> @endif

  <form class="card" method="post" action="{{ route('modules.calendar.calendars.store') }}">
    @csrf
    <p>{{ __('Paste a calendar’s private iCal (.ics) link. The kitchen screen shows today and tomorrow, and the next two weeks in the calendar view.') }}</p>
    <div>
      <label for="name">{{ __('Name') }}</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('Family') }}" maxlength="40" required>
      @error('name') <p class="err">{{ $message }}</p> @enderror
    </div>
    <div>
      <label>{{ __('Icon before each event (optional)') }}</label>
      <div class="icons">
        <label><input type="radio" name="icon" value="" @checked(old('icon', '') === '')><span>{{ __('None') }}</span></label>
        @foreach ($icons as $icon)
          <label><input type="radio" name="icon" value="{{ $icon }}" @checked(old('icon') === $icon)><span>{{ $icon }}</span></label>
        @endforeach
      </div>
    </div>
    <div>
      <label>{{ __('Colour') }}</label>
      <div class="swatches">
        @foreach ($colors as $i => $color)
          <label><input type="radio" name="color" value="{{ $color }}" @checked(old('color', $colors[count($calendars) % count($colors)]) === $color)><span style="background:{{ $color }}"></span></label>
        @endforeach
      </div>
    </div>
    <div>
      <label for="reminder">{{ __('Reminder on the kitchen screen') }}</label>
      <select id="reminder" name="reminder">
        <option value="">{{ __('None') }}</option>
        @foreach ($reminders as $minutes => $label)<option value="{{ $minutes }}" @selected((string) old('reminder') === (string) $minutes)>{{ __(':time before', ['time' => __($label)]) }}</option>@endforeach
      </select>
      <label class="check"><input type="checkbox" name="speak" value="1" @checked(old('speak'))> {{ __('Also say it out loud') }}</label>
    </div>
    <div>
      <label for="url">{{ __('iCal link') }}</label>
      <input type="url" id="url" name="url" placeholder="https://calendar.google.com/calendar/ical/…/basic.ics" required autocomplete="off">
      @error('url') <p class="err">{{ $message }}</p> @enderror
    </div>
    <div><button class="btn" type="submit">{{ __('Add calendar') }}</button></div>
  </form>

  <div class="card">
    <p class="label">{{ __('Calendars') }} ({{ count($calendars) }})</p>
    @if (count($calendars))
      <ul>
        @foreach ($calendars as $i => $c)
          <li class="{{ $c['visible'] ? '' : 'off' }}">
            <div class="line">
              <span class="dot" style="background:{{ $c['color'] }}"></span>
              <div class="name">@if (($c['icon'] ?? '') !== ''){{ $c['icon'] }} @endif{{ $c['name'] }}<small>{{ \App\Modules\Calendar\CalendarStore::mask($c['url']) }}@if (! empty($c['reminder'])) · {{ __('reminder :time before', ['time' => __(\App\Modules\Calendar\CalendarStore::REMINDERS[$c['reminder']] ?? '')]) }}{{ ($c['speak'] ?? false) ? __(', spoken') : '' }}@endif</small></div>
              @if ($i > 0)
                <form method="post" action="{{ route('modules.calendar.calendars.up', $c['id']) }}">@csrf<button class="ghost" aria-label="{{ __('Move up') }}">↑</button></form>
              @endif
              <form method="post" action="{{ route('modules.calendar.calendars.update', $c['id']) }}">@csrf<input type="hidden" name="visible" value="{{ $c['visible'] ? 0 : 1 }}"><button class="ghost">{{ $c['visible'] ? __('Hide') : __('Show') }}</button></form>
              <form method="post" action="{{ route('modules.calendar.calendars.destroy', $c['id']) }}">@csrf<button class="ghost danger">{{ __('Remove') }}</button></form>
            </div>
            <details>
              <summary>{{ __('Rename, icon, colour or reminder') }}</summary>
              <form method="post" action="{{ route('modules.calendar.calendars.update', $c['id']) }}">
                @csrf
                <input type="text" name="name" value="{{ $c['name'] }}" maxlength="40" required aria-label="{{ __('Name') }}">
                <div class="icons">
                  <label><input type="radio" name="icon" value="" @checked(($c['icon'] ?? '') === '')><span>{{ __('None') }}</span></label>
                  @foreach ($icons as $icon)
                    <label><input type="radio" name="icon" value="{{ $icon }}" @checked(($c['icon'] ?? '') === $icon)><span>{{ $icon }}</span></label>
                  @endforeach
                </div>
                <div class="swatches">
                  @foreach ($colors as $color)
                    <label><input type="radio" name="color" value="{{ $color }}" @checked($c['color'] === $color)><span style="background:{{ $color }}"></span></label>
                  @endforeach
                </div>
                <select name="reminder" aria-label="{{ __('Reminder') }}">
                  <option value="">{{ __('No reminder') }}</option>
                  @foreach ($reminders as $minutes => $label)<option value="{{ $minutes }}" @selected((string) ($c['reminder'] ?? '') === (string) $minutes)>{{ __('Remind :time before', ['time' => __($label)]) }}</option>@endforeach
                </select>
                <label class="check"><input type="checkbox" name="speak" value="1" @checked($c['speak'] ?? false)> {{ __('Say reminders out loud') }}</label>
                <div><button class="btn" type="submit">{{ __('Save') }}</button></div>
              </form>
            </details>
          </li>
        @endforeach
      </ul>
    @else
      <p style="color:var(--sub)">{{ __('No calendars yet. The screen shows sample events until you add one.') }}</p>
    @endif
  </div>

  <div class="card">
    <p class="label">{{ __('Where to find the iCal link') }}</p>
    <p><b>Google Calendar</b> {{ __('(on a computer)') }}</p>
    <ol>
      <li>{{ __('Open calendar.google.com › Settings (gear).') }}</li>
      <li>{{ __('Under “Settings for my calendars”, pick the calendar › Integrate calendar.') }}</li>
      <li>{!! __('Copy <b>Secret address in iCal format</b>.') !!}</li>
    </ol>
    <p style="color:var(--sub);font-size:14px">{{ __('A public Google calendar’s embed link (calendar.google.com/calendar/embed?src=…) also works.') }}</p>
    <p><b>iCloud</b> (iPhone)</p>
    <ol>
      <li>{{ __('Calendar app › Calendars › tap ⓘ next to the calendar.') }}</li>
      <li>{!! __('Turn on <b>Public Calendar</b>, then Share Link › Copy (webcal:// links work).') !!}</li>
    </ol>
    <p><b>Outlook</b> : {{ __('Settings › Calendar › Shared calendars › Publish a calendar › copy the ICS link.') }}</p>
  </div>

  <p><a href="{{ route('settings') }}">← {{ __('Settings') }}</a> · <a href="{{ route('dashboard') }}">{{ __('Back to the dashboard') }}</a></p>
</div>
@include('settings.close-script')
</body>
</html>
