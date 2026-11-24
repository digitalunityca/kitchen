<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use Carbon\CarbonImmutable;

/**
 * The stats sheet: the live game (or the latest final) in detail, the
 * standing, the last five results and the next three games.
 */
class BuildHabsSheet
{
    public function __construct(
        private readonly FetchHabsSchedule $fetchHabsSchedule,
        private readonly FetchGameDetails $fetchGameDetails,
        private readonly FetchHabsStanding $fetchHabsStanding,
    ) {}

    /** @return array{available: bool, focus: array<string, mixed>|null, details: array<string, mixed>|null, standing: array<string, mixed>|null, recent: list<array<string, mixed>>, next: list<array<string, mixed>>} */
    public function handle(CarbonImmutable $now): array
    {
        $games = $this->fetchHabsSchedule->handle();

        if ($games === null) {
            return ['available' => false, 'focus' => null, 'details' => null, 'standing' => null, 'recent' => [], 'next' => []];
        }

        $finals = array_values(array_filter($games, fn (array $g): bool => $g['final']));
        $live = array_values(array_filter($games, fn (array $g): bool => $g['live']));
        $next = array_values(array_filter($games, fn (array $g): bool => $g['upcoming'] && $g['start'] > $now->subHours(3)));
        $focus = $live[0] ?? ($finals === [] ? null : end($finals));

        return [
            'available' => true,
            'focus' => $focus,
            'details' => $focus ? $this->fetchGameDetails->handle($focus['id'], $focus['live']) : null,
            'standing' => $this->fetchHabsStanding->handle(),
            'recent' => array_reverse(array_slice($finals, -5)),
            'next' => array_map(fn (array $g): array => $g + ['when' => FindHabsHighlights::when($g['start'], $now->setTimezone(FetchHabsSchedule::TIMEZONE))], array_slice($next, 0, 3)),
        ];
    }
}
