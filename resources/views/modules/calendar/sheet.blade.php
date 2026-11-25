{{-- The calendar sheet's content: filters, the next days, and each event's details (shown one at a time). --}}
@php($describe = function (string $text): string {
    $text = preg_replace('#<\s*(br|/p|/div|/li)\s*/?>#i', "\n", $text);
    $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5);

    return nl2br(e(trim($text)));
})
@php($dayLabel = fn (string $date) => $date === $today->toDateString() ? 'Today' : ($date === $today->addDay()->toDateString() ? 'Tomorrow' : \Carbon\CarbonImmutable::parse($date)->format('l, F j')))
@if ($sample)
  <p class="calx-sample">Sample events. <a href="{{ route('modules.calendar.settings') }}">Add your calendars</a> to see your own.</p>
@endif

<div class="calx-list">
  <div class="calx-filters" role="group" aria-label="Calendars">
    @foreach ($calendars as $c)
      <button type="button" class="calx-chip" data-cal-chip="{{ $c['id'] }}" aria-pressed="true"><i style="background:{{ $c['color'] }}"></i>{{ $c['name'] }}</button>
    @endforeach
  </div>

  @foreach ($days as $date => $events)
    <section class="calx-day" @if ($loop->index < 2) data-always @endif>
      <h3>{{ $dayLabel($date) }}</h3>
      @foreach ($events as $event)
        <button type="button" class="calx-ev" data-id="{{ $event['id'] }}" data-cal="{{ $event['calendarId'] }}">
          <i style="background:{{ $event['color'] }}"></i>
          <span class="t">@include('modules.calendar.time', ['event' => $event])</span>
          <span><b>{{ $event['title'] }}</b>@if ($event['location'])<small>{{ $event['location'] }}</small>@endif</span>
        </button>
      @endforeach
      <p class="cal-none calx-nothing" @if ($events !== []) hidden @endif>Nothing planned</p>
    </section>
  @endforeach
</div>

@foreach (collect($days)->flatten(1)->unique('id') as $event)
  <article class="calx-detail" data-detail="{{ $event['id'] }}" style="--c: {{ $event['color'] }}" hidden>
    <h2>{{ $event['title'] }}</h2>
    <p class="calx-meta">{{ $event['calendar'] }}</p>
    <dl>
      <dt>When</dt>
      <dd>
        @if ($event['allDay'])
          {{ $event['start']->format('l, F j') }}@if ($event['end']->subDay()->toDateString() !== $event['start']->toDateString()) – {{ $event['end']->subDay()->format('l, F j') }}@endif · All day
        @else
          {{ $event['start']->format('l, F j · g:i A') }} – {{ $event['start']->isSameDay($event['end']) ? $event['end']->format('g:i A') : $event['end']->format('l, F j · g:i A') }}
        @endif
      </dd>
      @if ($event['location'])<dt>Where</dt><dd>{{ $event['location'] }}</dd>@endif
      @if ($event['description'] !== '')<dt>Notes</dt><dd>{!! $describe($event['description']) !!}</dd>@endif
      @if ($event['attendees'] !== [])<dt>Guests</dt><dd>{{ implode(', ', $event['attendees']) }}</dd>@endif
    </dl>
  </article>
@endforeach
