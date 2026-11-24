<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use Carbon\CarbonImmutable;

/**
 * The stats sheet's three tabs: the live game (or the latest final) in
 * detail; the standings for the Canadiens' division, conference and the
 * league; and the last five results with the next five games.
 */
class BuildHabsSheet
{
    /** Teams above this line make the playoffs: top 3 in a division, top 8 in a conference. */
    public const PLAYOFF_CUT = ['division' => 3, 'conference' => 8, 'league' => null];

    public function __construct(
        private readonly FetchHabsSchedule $fetchHabsSchedule,
        private readonly FetchGameDetails $fetchGameDetails,
        private readonly FetchHabsStanding $fetchHabsStanding,
        private readonly FetchStandings $fetchStandings,
    ) {}

    /** @return array<string, mixed> */
    public function handle(CarbonImmutable $now): array
    {
        $now = $now->setTimezone(FetchHabsSchedule::TIMEZONE);
        $games = $this->fetchHabsSchedule->handle();

        if ($games === null) {
            return ['available' => false];
        }

        $finals = array_values(array_filter($games, fn (array $g): bool => $g['final']));
        $live = array_values(array_filter($games, fn (array $g): bool => $g['live']));
        $next = array_values(array_filter($games, fn (array $g): bool => $g['upcoming'] && $g['start'] > $now->subHours(3)));
        $focus = $live[0] ?? ($finals === [] ? null : end($finals));
        $gameDay = $live !== [] || collect($games)->contains(fn (array $g): bool => $g['date'] === $now->toDateString());

        return [
            'available' => true,
            'defaultTab' => $gameDay ? 'game' : 'standings',
            'focus' => $focus,
            'details' => $focus ? $this->fetchGameDetails->handle($focus['id'], $focus['live']) : null,
            'standing' => $this->fetchHabsStanding->handle(),
            'tables' => $this->tables(),
            'cut' => self::PLAYOFF_CUT,
            'recent' => array_reverse(array_slice($finals, -5)),
            'next' => array_map(fn (array $g): array => $g + ['when' => FindHabsHighlights::when($g['start'], $now)], array_slice($next, 0, 5)),
        ];
    }

    /** @return array{division: list<array<string, mixed>>, conference: list<array<string, mixed>>, league: list<array<string, mixed>>, divisionName: string, conferenceName: string}|null */
    private function tables(): ?array
    {
        $teams = $this->fetchStandings->handle();
        $habs = collect($teams ?? [])->firstWhere('abbrev', FetchHabsSchedule::TEAM);

        if (! is_array($habs)) {
            return null;
        }

        $sorted = fn (string $rank, ?string $field = null, ?string $value = null): array => collect($teams)
            ->when($field, fn ($c) => $c->where($field, $value))
            ->sortBy($rank)
            ->map(fn (array $t): array => $t + ['rank' => $t[$rank]])
            ->values()->all();

        return [
            'divisionName' => $habs['division'],
            'conferenceName' => $habs['conference'],
            'division' => $sorted('divisionRank', 'division', $habs['division']),
            'conference' => $sorted('conferenceRank', 'conference', $habs['conference']),
            'league' => $sorted('leagueRank'),
        ];
    }
}
