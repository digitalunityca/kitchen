<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use App\Modules\Habs\NhlApi;

/** The whole league table from the NHL, one row per team. Cached 30 minutes. */
class FetchStandings
{
    public function __construct(private readonly NhlApi $api) {}

    /**
     * @return list<array{abbrev: string, name: string, logo: string, darkLogo: string, gp: int, w: int, l: int, otl: int, pts: int, pct: float,
     *               streak: string, lastTen: string, division: string, divisionRank: int, conference: string, conferenceRank: int, leagueRank: int}>|null
     */
    public function handle(): ?array
    {
        $data = $this->api->get('standings/now', 1800);

        if ($data === null) {
            return null;
        }

        return array_values(array_map(fn (array $t): array => [
            'abbrev' => (string) data_get($t, 'teamAbbrev.default'),
            'name' => (string) data_get($t, 'teamCommonName.default', data_get($t, 'teamName.default', '')),
            'logo' => (string) ($t['teamLogo'] ?? ''),
            'darkLogo' => (string) ($t['teamLogoDark'] ?? $t['teamLogo'] ?? ''),
            'gp' => (int) ($t['gamesPlayed'] ?? 0),
            'w' => (int) ($t['wins'] ?? 0),
            'l' => (int) ($t['losses'] ?? 0),
            'otl' => (int) ($t['otLosses'] ?? 0),
            'pts' => (int) ($t['points'] ?? 0),
            'pct' => (float) ($t['pointPctg'] ?? 0),
            'streak' => ($t['streakCode'] ?? '').($t['streakCount'] ?? ''),
            'lastTen' => ($t['l10Wins'] ?? 0).'-'.($t['l10Losses'] ?? 0).'-'.($t['l10OtLosses'] ?? 0),
            'division' => (string) ($t['divisionName'] ?? ''),
            'divisionRank' => (int) ($t['divisionSequence'] ?? 0),
            'conference' => (string) ($t['conferenceName'] ?? ''),
            'conferenceRank' => (int) ($t['conferenceSequence'] ?? 0),
            'leagueRank' => (int) ($t['leagueSequence'] ?? 0),
        ], (array) ($data['standings'] ?? [])));
    }
}
