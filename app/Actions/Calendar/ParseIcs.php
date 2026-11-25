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
     *               location: string, description: string, attendees: list<string>, url: string, organizer: string, alarm: int|null}>|null null when the feed is not valid iCal
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
                'url' => trim((string) ($event->URL ?? '')),
                'organizer' => $this->organizer($event),
                'alarm' => $this->alarmMinutes($event),
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

    private function organizer(mixed $event): string
    {
        if (! isset($event->ORGANIZER)) {
            return '';
        }

        return isset($event->ORGANIZER['CN']) ? (string) $event->ORGANIZER['CN'] : (string) preg_replace('/^mailto:/i', '', (string) $event->ORGANIZER);
    }

    /** Minutes before the start of the event's first alarm (VALARM with a relative trigger), if it has one. */
    private function alarmMinutes(mixed $event): ?int
    {
        foreach ($event->select('VALARM') as $alarm) {
            $trigger = (string) ($alarm->TRIGGER ?? '');

            if (preg_match('/^-?P/i', $trigger)) {
                try {
                    $interval = new \DateInterval(ltrim(strtoupper($trigger), '-'));

                    return $interval->d * 1440 + $interval->h * 60 + $interval->i + intdiv($interval->s, 60);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
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
