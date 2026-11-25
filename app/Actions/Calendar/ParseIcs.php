<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Turn an iCal feed into the events between two moments, with recurring
 * events expanded (RRULE, EXDATE, moved instances) by sabre/vobject. Times are
 * in the home's time zone; all-day events keep their date.
 */
class ParseIcs
{
    public const TIMEZONE = 'America/Toronto';

    /**
     * @return list<array{uid: string, title: string, start: CarbonImmutable, end: CarbonImmutable, allDay: bool,
     *               location: string, description: string, attendees: list<string>}>|null null when the feed is not valid iCal
     */
    public function handle(string $ics, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        try {
            $calendar = Reader::read($ics, Reader::OPTION_FORGIVING);

            if (! $calendar instanceof VCalendar) {
                return null;
            }

            $tz = new DateTimeZone(self::TIMEZONE);
            $expanded = $calendar->expand(new DateTimeImmutable($from->utc()->toIso8601String()), new DateTimeImmutable($to->utc()->toIso8601String()), $tz);
        } catch (Throwable) {
            return null;
        }

        $events = [];

        foreach ($expanded->select('VEVENT') as $event) {
            if (strtoupper((string) ($event->STATUS ?? '')) === 'CANCELLED' || ! isset($event->DTSTART)) {
                continue;
            }

            $allDay = ! $event->DTSTART->hasTime();
            $start = $this->moment($event->DTSTART->getDateTime($tz), $allDay);
            $end = isset($event->DTEND)
                ? $this->moment($event->DTEND->getDateTime($tz), $allDay)
                : (isset($event->DURATION) ? $start->add($event->DURATION->getDateInterval()) : ($allDay ? $start->addDay() : $start));

            $events[] = [
                'uid' => (string) ($event->UID ?? sha1((string) $event->SUMMARY.$start)),
                'title' => trim((string) ($event->SUMMARY ?? '')) ?: '(No title)',
                'start' => $start,
                'end' => $end,
                'allDay' => $allDay,
                'location' => trim((string) ($event->LOCATION ?? '')),
                'description' => trim((string) ($event->DESCRIPTION ?? '')),
                'attendees' => $this->attendees($event),
            ];
        }

        return $events;
    }

    /** All-day dates are floating: keep the calendar date in the home time zone. */
    private function moment(\DateTimeInterface $value, bool $allDay): CarbonImmutable
    {
        return $allDay
            ? CarbonImmutable::parse($value->format('Y-m-d'), self::TIMEZONE)
            : CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE);
    }

    /** @return list<string> names, or e-mail addresses when there is no name */
    private function attendees(mixed $event): array
    {
        $people = [];

        foreach ($event->select('ATTENDEE') as $attendee) {
            $name = isset($attendee['CN']) ? (string) $attendee['CN'] : (string) preg_replace('/^mailto:/i', '', (string) $attendee);

            if ($name !== '') {
                $people[] = $name;
            }
        }

        return array_values(array_unique($people));
    }
}
