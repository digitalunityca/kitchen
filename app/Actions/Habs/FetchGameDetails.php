<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use App\Modules\Habs\NhlApi;

/**
 * Everything the stats sheet shows about one game: score by period, goals
 * with assists, shots, power play and goalies, from the NHL gamecenter
 * landing, right-rail and boxscore. Cached 1 minute while live, else 10.
 */
class FetchGameDetails
{
    public function __construct(private readonly NhlApi $api) {}

    /** @return array<string, mixed>|null */
    public function handle(int $gameId, bool $live): ?array
    {
        $ttl = $live ? 60 : 600;
        $landing = $this->api->get("gamecenter/{$gameId}/landing", $ttl);

        if ($landing === null) {
            return null;
        }

        $rail = $this->api->get("gamecenter/{$gameId}/right-rail", $ttl) ?? [];
        $box = $this->api->get("gamecenter/{$gameId}/boxscore", $ttl) ?? [];
        $stats = collect((array) ($rail['teamGameStats'] ?? []))->keyBy('category');

        return [
            'game' => FetchHabsSchedule::normalize($landing),
            'clock' => data_get($landing, 'clock.timeRemaining'),
            'intermission' => (bool) data_get($landing, 'clock.inIntermission', false),
            'periods' => array_map(fn (array $period): array => [
                'label' => self::periodLabel((int) data_get($period, 'periodDescriptor.number'), (string) data_get($period, 'periodDescriptor.periodType')),
                'away' => (int) ($period['away'] ?? 0),
                'home' => (int) ($period['home'] ?? 0),
            ], (array) data_get($rail, 'linescore.byPeriod', [])),
            'shots' => ['away' => data_get($stats, 'sog.awayValue'), 'home' => data_get($stats, 'sog.homeValue')],
            'powerPlay' => ['away' => data_get($stats, 'powerPlay.awayValue'), 'home' => data_get($stats, 'powerPlay.homeValue')],
            'goals' => $this->goals($landing),
            'goalies' => $this->goalies($box),
        ];
    }

    /** 1 → "1st", 4 + OT → "OT", 5 + SO → "SO". */
    public static function periodLabel(int $number, string $type): string
    {
        return match (true) {
            $type === 'SO' => 'SO',
            $type === 'OT' => $number > 4 ? ($number - 3).'OT' : 'OT',
            default => $number.match ($number) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
            },
        };
    }

    /**
     * @param  array<string, mixed>  $landing
     * @return list<array{period: string, time: string, team: string, scorer: string, total: int|null, assists: list<string>, strength: string, home: bool}>
     */
    private function goals(array $landing): array
    {
        $goals = [];

        foreach ((array) data_get($landing, 'summary.scoring', []) as $period) {
            $label = self::periodLabel((int) data_get($period, 'periodDescriptor.number'), (string) data_get($period, 'periodDescriptor.periodType'));

            foreach ((array) ($period['goals'] ?? []) as $goal) {
                $goals[] = [
                    'period' => $label,
                    'time' => (string) ($goal['timeInPeriod'] ?? ''),
                    'team' => (string) data_get($goal, 'teamAbbrev.default', ''),
                    'scorer' => (string) data_get($goal, 'name.default', ''),
                    'total' => isset($goal['goalsToDate']) ? (int) $goal['goalsToDate'] : null,
                    'assists' => array_map(fn (array $a): string => (string) data_get($a, 'name.default', ''), (array) ($goal['assists'] ?? [])),
                    'strength' => strtoupper((string) ($goal['strength'] ?? 'ev')),
                    'home' => (bool) ($goal['isHome'] ?? false),
                ];
            }
        }

        return $goals;
    }

    /**
     * Goalies who played, both teams.
     *
     * @param  array<string, mixed>  $box
     * @return list<array{side: string, name: string, saves: int, shots: int, decision: string|null}>
     */
    private function goalies(array $box): array
    {
        $goalies = [];

        foreach (['away' => 'awayTeam', 'home' => 'homeTeam'] as $side => $key) {
            foreach ((array) data_get($box, "playerByGameStats.{$key}.goalies", []) as $goalie) {
                if ((int) ($goalie['shotsAgainst'] ?? 0) === 0 && ($goalie['toi'] ?? '00:00') === '00:00') {
                    continue;
                }

                $goalies[] = [
                    'side' => $side,
                    'name' => (string) data_get($goalie, 'name.default', ''),
                    'saves' => (int) ($goalie['saves'] ?? 0),
                    'shots' => (int) ($goalie['shotsAgainst'] ?? 0),
                    'decision' => $goalie['decision'] ?? null,
                ];
            }
        }

        return $goalies;
    }
}
