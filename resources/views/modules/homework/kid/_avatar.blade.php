{{-- The kid's face when a parent added a photo, else their initial on their colour. --}}
@if ($kid['photo'] ?? null)
  <img class="kz-avatar{{ ($big ?? false) ? ' big' : '' }}" src="{{ $kid['photo'] }}" alt="" width="{{ ($big ?? false) ? 160 : 52 }}" height="{{ ($big ?? false) ? 160 : 52 }}">
@else
  <span class="kz-avatar{{ ($big ?? false) ? ' big' : '' }}" aria-hidden="true">{{ $kid['badge'] }}</span>
@endif
