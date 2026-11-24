<?php

declare(strict_types=1);

namespace App\Actions\Habs;

/** The Canadiens' record and place in the division and conference. */
class FetchHabsStanding
{
    public function __construct(private readonly FetchStandings $fetchStandings) {}

    /** @return array{record: string, points: int, gamesPlayed: int, division: string, divisionRank: int, conference: string, conferenceRank: int, streak: string, lastTen: string}|null */
    public function handle(): ?array
    {
        $team = collect($this->fetchStandings->handle() ?? [])->firstWhere('abbrev', FetchHabsSchedule::TEAM);

        if (! is_array($team)) {
            return null;
        }

        return [
            'record' => "{$team['w']}-{$team['l']}-{$team['otl']}",
            'points' => $team['pts'],
            'gamesPlayed' => $team['gp'],
            'division' => $team['division'],
            'divisionRank' => $team['divisionRank'],
            'conference' => $team['conference'],
            'conferenceRank' => $team['conferenceRank'],
            'streak' => $team['streak'],
            'lastTen' => $team['lastTen'],
        ];
    }
}
