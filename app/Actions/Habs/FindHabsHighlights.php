<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use Carbon\CarbonImmutable;

/**
 * What the ticker shows, newest first, at most two items:
 * a live game; a final from today, or from yesterday until 6 PM;
 * and an upcoming game today or tomorrow. Nothing in range: no ticker.
 */
class FindHabsHighlights
{
    /** Yesterday's final stays up until this hour (local time). */
    public const FINAL_UNTIL_HOUR = 18;

    /**
     * @param  list<array<string, mixed>>  $games  from FetchHabsSchedule
     * @return list<array{kind: string, game: array<string, mixed>, when: string|null}>
     */
    public function handle(array $games, CarbonImmutable $now): array
    {
        $now = $now->setTimezone(FetchHabsSchedule::TIMEZONE);
        $today = $now->toDateString();
        $yesterday = $now->subDay()->toDateString();
        $tomorrow = $now->addDay()->toDateString();
        $items = [];

        foreach ($games as $game) {
            if ($game['live']) {
                $items[] = ['kind' => 'live', 'game' => $game, 'when' => null];
            }
        }

        $finals = array_filter($games, fn (array $game): bool => $game['final']
            && ($game['date'] === $today || ($game['date'] === $yesterday && $now->hour < self::FINAL_UNTIL_HOUR)));

        if ($finals !== []) {
            $items[] = ['kind' => 'final', 'game' => end($finals), 'when' => null];
        }

        foreach ($games as $game) {
            if ($game['upcoming'] && in_array($game['date'], [$today, $tomorrow], true) && $game['start'] > $now->subHours(3)) {
                $items[] = ['kind' => 'upcoming', 'game' => $game, 'when' => self::when($game['start'], $now)];
                break;
            }
        }

        return array_slice($items, 0, 2);
    }

    /** "Tonight 7:00 PM", "Today 1:00 PM", "Tomorrow 7:30 PM". */
    public static function when(CarbonImmutable $start, CarbonImmutable $now): string
    {
        $time = $start->format('g:i A');

        if ($start->isSameDay($now)) {
            return ($start->hour >= 17 ? 'Tonight ' : 'Today ').$time;
        }

        return ($start->isSameDay($now->addDay()) ? 'Tomorrow ' : $start->format('D ')).$time;
    }

    /**
     * A short note for Fully's action bar on game days: "🏒 Habs 7 PM", or the live score.
     *
     * @param  list<array{kind: string, game: array<string, mixed>, when: string|null}>  $items
     */
    public static function titleNote(array $items, CarbonImmutable $now): ?string
    {
        foreach ($items as $item) {
            $game = $item['game'];

            if ($item['kind'] === 'live') {
                return '🏒 Habs '.$game['mtl']['score'].'–'.$game['opponent']['score'];
            }

            if ($item['kind'] === 'upcoming' && $game['start']->isSameDay($now->setTimezone(FetchHabsSchedule::TIMEZONE))) {
                return '🏒 Habs '.str_replace(':00', '', $game['start']->format('g:i A'));
            }
        }

        return null;
    }
}
