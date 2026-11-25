<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Modules\Calendar\CalendarStore;
use App\Modules\Calendar\SampleEvents;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Events from every connected calendar between two moments, merged and sorted
 * (all-day first, then by start time), each tagged with its calendar's name and
 * colour. With no calendars connected yet it returns the sample events.
 */
class LoadCalendarEvents
{
    public function __construct(
        private readonly CalendarStore $store,
        private readonly FetchCalendarFeed $fetchCalendarFeed,
        private readonly ParseIcs $parseIcs,
        private readonly Cache $cache,
    ) {}

    /** @return array{sample: bool, calendars: list<array<string, mixed>>, events: list<array<string, mixed>>} */
    public function handle(CarbonImmutable $from, CarbonImmutable $to, bool $visibleOnly = true): array
    {
        $calendars = array_values(array_filter($this->store->all(), fn (array $c): bool => ! $visibleOnly || $c['visible']));

        if ($this->store->all() === []) {
            return ['sample' => true, 'calendars' => SampleEvents::calendars(), 'events' => SampleEvents::between($from, $to)];
        }

        // Expanding a year of recurring events is the slow part, so keep the result 5 minutes.
        $key = 'calendar.events.'.sha1(json_encode([$calendars, $from->toIso8601String(), $to->toIso8601String()]));

        return $this->cache->remember($key, now()->addMinutes(5), fn (): array => $this->merge($calendars, $from, $to));
    }

    /**
     * @param  list<array{id: string, name: string, color: string, url: string, visible: bool}>  $calendars
     * @return array{sample: bool, calendars: list<array<string, mixed>>, events: list<array<string, mixed>>}
     */
    private function merge(array $calendars, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $events = [];

        foreach ($calendars as $calendar) {
            $ics = $this->fetchCalendarFeed->handle($calendar);
            $parsed = $ics === null ? null : $this->parseIcs->handle($ics, $from, $to);

            foreach ($parsed ?? [] as $event) {
                $events[] = $event + [
                    'id' => substr(sha1($calendar['id'].'|'.$event['uid'].'|'.$event['start']->toIso8601String()), 0, 12),
                    'calendarId' => $calendar['id'],
                    'calendar' => $calendar['name'],
                    'color' => $calendar['color'],
                    'icon' => (string) ($calendar['icon'] ?? ''),
                ];
            }
        }

        usort($events, fn (array $a, array $b): int => [$a['start']->toDateString(), ! $a['allDay'], $a['start']] <=> [$b['start']->toDateString(), ! $b['allDay'], $b['start']]);

        return [
            'sample' => false,
            'calendars' => array_map(fn (array $c): array => ['id' => $c['id'], 'name' => $c['name'], 'color' => $c['color'], 'icon' => (string) ($c['icon'] ?? '')], $calendars),
            'events' => $events,
        ];
    }

    /**
     * Events grouped by local date, every day in the range present (possibly empty).
     *
     * @param  list<array<string, mixed>>  $events
     * @return array<string, list<array<string, mixed>>>
     */
    public static function byDay(array $events, CarbonImmutable $from, int $days): array
    {
        $grouped = [];

        for ($i = 0; $i < $days; $i++) {
            $grouped[$from->addDays($i)->toDateString()] = [];
        }

        foreach ($events as $event) {
            // Timed events sit on the day they start; all-day events on every day they cover.
            $last = $event['allDay'] ? $event['end']->subDay()->startOfDay() : $event['start']->startOfDay();

            for ($day = $event['start']->startOfDay(); $day <= $last; $day = $day->addDay()) {
                if (isset($grouped[$day->toDateString()])) {
                    $grouped[$day->toDateString()][] = $event;
                }
            }
        }

        foreach ($grouped as &$dayEvents) {
            usort($dayEvents, fn (array $a, array $b): int => [! $a['allDay'], $a['start']] <=> [! $b['allDay'], $b['start']]);
        }

        return $grouped;
    }
}
