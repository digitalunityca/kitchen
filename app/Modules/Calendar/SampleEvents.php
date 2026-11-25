<?php

declare(strict_types=1);

namespace App\Modules\Calendar;

use App\Actions\Calendar\ParseIcs;
use Carbon\CarbonImmutable;

/** What the card shows until the first calendar is connected (with a "Sample data" pill). */
final class SampleEvents
{
    /** @return list<array{id: string, name: string, color: string}> */
    public static function calendars(): array
    {
        return [
            ['id' => 'sample-family', 'name' => 'Family', 'color' => '#0071E3'],
            ['id' => 'sample-school', 'name' => 'School', 'color' => '#34C759'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function between(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $today = $from->setTimezone(ParseIcs::TIMEZONE)->startOfDay();
        $make = fn (int $day, string $at, int $minutes, string $title, string $calendar, string $location = '', string $description = ''): array => [
            'id' => 'sample-'.substr(sha1($title.$day), 0, 8),
            'uid' => 'sample-'.$title,
            'title' => $title,
            'start' => $start = $today->addDays($day)->setTimeFromTimeString($at),
            'end' => $start->addMinutes($minutes),
            'allDay' => false,
            'location' => $location,
            'description' => $description,
            'attendees' => [],
            'calendarId' => $calendar === 'School' ? 'sample-school' : 'sample-family',
            'calendar' => $calendar,
            'color' => $calendar === 'School' ? '#34C759' : '#0071E3',
        ];

        return array_values(array_filter([
            $make(0, '08:00', 30, 'School drop-off', 'School', 'École Saint-Jean'),
            $make(0, '12:30', 30, 'Grocery pickup', 'Family', 'IGA'),
            $make(0, '18:30', 90, 'Family dinner', 'Family', '', 'Lasagna night.'),
            $make(1, '08:00', 30, 'School drop-off', 'School', 'École Saint-Jean'),
            $make(1, '15:15', 45, 'Dentist', 'Family', 'Clinique dentaire', 'Bring the health card.'),
        ], fn (array $e): bool => $e['start'] >= $from->startOfDay() && $e['start'] < $to));
    }
}
