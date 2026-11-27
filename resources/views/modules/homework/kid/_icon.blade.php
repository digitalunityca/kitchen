{{-- Line icons for the kid's area (24px grid, 2px stroke). --}}
<svg class="kz-icon" width="{{ $size ?? 24 }}" height="{{ $size ?? 24 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
  @case('homework')<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>@break
  @case('rewards')<rect x="3" y="8" width="18" height="5" rx="1"/><path d="M5 13v7a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-7M12 8v13M12 8c-1.5-3-5.5-4-5.5-1.5S10 8 12 8zm0 0c1.5-3 5.5-4 5.5-1.5S14 8 12 8z"/>@break
  @case('history')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
  @case('chores')<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M12 19.5s-3.5-2.2-3.5-4.6a1.9 1.9 0 0 1 3.5-1 1.9 1.9 0 0 1 3.5 1c0 2.4-3.5 4.6-3.5 4.6z"/>@break
  @case('news')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>@break
  @case('back')<path d="M15 18l-6-6 6-6"/>@break
  @case('delete')<path d="M21 5H9l-6 7 6 7h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1z"/><path d="m16 9-4 6m0-6 4 6"/>@break
  @case('ok')<path d="M5 12.5l4.5 4.5L19 7.5"/>@break
  @case('printer')<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/>@break
  @case('star')<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.5 2.9 1-6.1L3.2 9.5l6.1-.9z"/>@break
  @case('lock')<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>@break
  @case('addition')<path d="M12 5v14M5 12h14"/>@break
  @case('multiplication')<path d="m6 6 12 12M18 6 6 18"/>@break
  @case('division')<path d="M5 12h14"/><circle cx="12" cy="6.5" r="1.3" fill="currentColor"/><circle cx="12" cy="17.5" r="1.3" fill="currentColor"/>@break
  @case('writing')<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>@break
  @case('grammar')<path d="M4 19 9 5l5 14M5.8 14h6.4"/><path d="M15 19v-6.5a2.5 2.5 0 0 1 5 0V19M15 15.5h5"/>@break
  @case('reading')<path d="M2 5h6a4 4 0 0 1 4 4v11a3 3 0 0 0-3-3H2zM22 5h-6a4 4 0 0 0-4 4v11a3 3 0 0 1 3-3h7z"/>@break
@endswitch
</svg>
