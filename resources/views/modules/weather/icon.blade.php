{{-- Weather icon by name (App\Modules\Weather\WeatherCodes::icon). Expects $icon and $size. --}}
@php($cloud = '<path d="M8 20h9a4 4 0 0 0 0-8 5 5 0 0 0-9.6 1.5A3.3 3.3 0 0 0 8 20z" stroke="var(--sub)" fill="var(--card)"/>')
@php($cloudHigh = '<path d="M7.5 16h9a3.8 3.8 0 0 0 0-7.6 4.8 4.8 0 0 0-9.2 1.4A3.1 3.1 0 0 0 7.5 16z" stroke="var(--sub)" fill="var(--card)"/>')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($icon)
  @case('clear')
    <g stroke="#F5A524"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/></g>
    @break
  @case('clear-night')
    <path d="M19 14.5A7.5 7.5 0 0 1 9.5 5a7.5 7.5 0 1 0 9.5 9.5z" stroke="#F5A524"/>
    @break
  @case('partly')
    <g stroke="#F5A524"><circle cx="9" cy="9" r="3.5"/><path d="M9 2.5v1.5M3.5 9H2M4.4 4.4l1 1M13.6 4.4l-1 1"/></g>{!! $cloud !!}
    @break
  @case('partly-night')
    <path d="M12.5 8.2A4.5 4.5 0 0 1 6.8 2.5a4.5 4.5 0 1 0 5.7 5.7z" stroke="#F5A524"/>{!! $cloud !!}
    @break
  @case('fog')
    <g stroke="var(--sub)"><path d="M4 9h16M3 13h18M5 17h14"/></g>
    @break
  @case('rain')
    {!! $cloudHigh !!}<g stroke="var(--accent)"><path d="M9 18.5l-1 2.5M13 18.5l-1 2.5M17 18.5l-1 2.5"/></g>
    @break
  @case('snow')
    {!! $cloudHigh !!}<g fill="var(--sub)" stroke="none"><circle cx="8.5" cy="19.5" r="1"/><circle cx="12.5" cy="21" r="1"/><circle cx="16.5" cy="19.5" r="1"/></g>
    @break
  @case('thunder')
    {!! $cloudHigh !!}<path d="M12.5 16.5l-2 3h3l-2 3" stroke="#F5A524"/>
    @break
  @default
    <path d="M6.5 19h11a4.5 4.5 0 0 0 0-9 5.5 5.5 0 0 0-10.6 1.7A3.7 3.7 0 0 0 6.5 19z" stroke="var(--sub)" fill="var(--card)"/>
@endswitch
</svg>
