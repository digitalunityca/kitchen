{{-- "All day", "8:00 – 8:30 AM", "11:30 AM – 1:00 PM", "10:00 PM – 1:00 AM". --}}
@php
    if ($event['allDay']) {
        $label = 'All day';
    } else {
        $sameHalf = $event['start']->format('A') === $event['end']->format('A') && $event['start']->isSameDay($event['end']);
        $label = $event['start']->format($sameHalf ? 'g:i' : 'g:i A').($event['end'] > $event['start'] ? ' – '.$event['end']->format('g:i A') : '');
        if ($event['end'] <= $event['start']) {
            $label = $event['start']->format('g:i A');
        }
    }
@endphp
{{ $label }}
