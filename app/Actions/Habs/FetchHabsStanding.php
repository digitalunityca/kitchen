<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use App\Modules\Habs\NhlApi;

/** The Canadiens' record and place in the division and conference. Cached 30 minutes. */
class FetchHabsStanding
{
    public function __construct(private readonly NhlApi $api) {}

    /** @return array{record: string, points: int, gamesPlayed: int, division: string, divisionRank: int, conference: string, conferenceRank: int, streak: string, lastTen: string}|null */
    public function handle(): ?array
    {
        $data = $this->api->get('standings/now', 1800);
        $team = collect((array) ($data['standings'] ?? []))->first(fn (array $t): bool => data_get($t, 'teamAbbrev.default') === FetchHabsSchedule::TEAM);

        if (! is_array($team)) {
            return null;
        }

        return [
            'record' => "{$team['wins']}-{$team['losses']}-{$team['otLosses']}",
            'points' => (int) $team['points'],
            'gamesPlayed' => (int) $team['gamesPlayed'],
            'division' => (string) $team['divisionName'],
            'divisionRank' => (int) $team['divisionSequence'],
            'conference' => (string) $team['conferenceName'],
            'conferenceRank' => (int) $team['conferenceSequence'],
            'streak' => ($team['streakCode'] ?? '').($team['streakCount'] ?? ''),
            'lastTen' => ($team['l10Wins'] ?? 0).'-'.($team['l10Losses'] ?? 0).'-'.($team['l10OtLosses'] ?? 0),
        ];
    }
}
