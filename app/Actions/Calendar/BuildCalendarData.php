<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use Carbon\CarbonImmutable;

/**
 * Everything the calendar sheet needs, as plain JSON: the visible calendars
 * and every event from 6 months back to 12 months ahead, each with the dates
 * it covers and its notes as plain text. The month grid, day list, search and
 * calendar toggles all run in the browser on this one download.
 */
class BuildCalendarData
{
    public const MONTHS_BACK = 6;

    public const MONTHS_AHEAD = 12;

    public function __construct(private readonly LoadCalendarEvents $loadCalendarEvents) {}

    /** @return array{sample: bool, today: string, from: string, to: string, calendars: list<array<string, mixed>>, events: list<array<string, mixed>>} */
    public function handle(CarbonImmutable $now): array
    {
        $now = $now->setTimezone(ParseIcs::TIMEZONE);
        $from = $now->startOfMonth()->subMonths(self::MONTHS_BACK);
        $to = $now->startOfMonth()->addMonths(self::MONTHS_AHEAD + 1);
        $loaded = $this->loadCalendarEvents->handle($from, $to);

        return [
            'sample' => $loaded['sample'],
            'today' => $now->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->subDay()->toDateString(),
            'calendars' => $loaded['calendars'],
            'events' => array_map(fn (array $e): array => self::compact($e, $from, $to), $loaded['events']),
        ];
    }

    /**
     * One event as the browser gets it.
     *
     * @param  array<string, mixed>  $e
     * @return array<string, mixed>
     */
    public static function compact(array $e, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [
            'id' => $e['id'],
            'calendarId' => $e['calendarId'],
            'calendar' => $e['calendar'],
            'color' => $e['color'],
            'icon' => (string) ($e['icon'] ?? ''),
            'title' => $e['title'],
            'location' => $e['location'],
            'notes' => self::plainText($e['description']),
            'attendees' => $e['attendees'],
            'allDay' => $e['allDay'],
            'start' => $e['start']->format('Y-m-d\TH:i'),
            'end' => $e['end']->format('Y-m-d\TH:i'),
            'days' => self::days($e, $from, $to),
        ];
    }

    /** HTML from Google descriptions becomes plain text with line breaks; nothing stays clickable. */
    public static function plainText(string $text): string
    {
        $text = (string) preg_replace('#<\s*(br|/p|/div|/li)\s*/?>#i', "\n", $text);

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * The dates an event shows on: its start date for timed events, every date for all-day ones.
     *
     * @param  array<string, mixed>  $event
     * @return list<string>
     */
    public static function days(array $event, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $last = $event['allDay'] ? $event['end']->subDay()->startOfDay() : $event['start']->startOfDay();
        $days = [];

        for ($day = $event['start']->startOfDay(); $day <= $last && count($days) < 62; $day = $day->addDay()) {
            if ($day >= $from && $day < $to) {
                $days[] = $day->toDateString();
            }
        }

        return $days;
    }
}
