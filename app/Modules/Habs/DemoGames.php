<?php

declare(strict_types=1);

namespace App\Modules\Habs;

use App\Actions\Habs\FetchHabsSchedule;
use Carbon\CarbonImmutable;

/**
 * Made-up games for previewing each ticker state on days without a game:
 * /?habs=upcoming, /?habs=live or /?habs=final.
 */
final class DemoGames
{
    public const STATES = ['upcoming', 'live', 'final'];

    /** @return list<array<string, mixed>> */
    public static function make(string $state, CarbonImmutable $now): array
    {
        $now = $now->setTimezone(FetchHabsSchedule::TIMEZONE);
        $team = fn (string $abbrev, string $name, ?int $score): array => [
            'id' => 0, 'abbrev' => $abbrev, 'commonName' => ['default' => $name], 'score' => $score,
            'logo' => "https://assets.nhle.com/logos/nhl/svg/{$abbrev}_light.svg",
            'darkLogo' => "https://assets.nhle.com/logos/nhl/svg/{$abbrev}_dark.svg",
        ];

        $game = match ($state) {
            'live' => [
                'gameState' => 'LIVE', 'startTimeUTC' => $now->subMinutes(70)->utc()->toIso8601ZuluString(),
                'homeTeam' => $team('MTL', 'Canadiens', 2), 'awayTeam' => $team('BOS', 'Bruins', 1),
                'periodDescriptor' => ['number' => 2, 'periodType' => 'REG'],
            ],
            'final' => [
                'gameState' => 'OFF', 'startTimeUTC' => $now->subHours(3)->utc()->toIso8601ZuluString(),
                'homeTeam' => $team('TOR', 'Maple Leafs', 2), 'awayTeam' => $team('MTL', 'Canadiens', 3),
                'periodDescriptor' => ['number' => 4, 'periodType' => 'OT'], 'gameOutcome' => ['lastPeriodType' => 'OT'],
            ],
            default => [
                'gameState' => 'FUT', 'startTimeUTC' => $now->setTime(19, 0)->utc()->toIso8601ZuluString(),
                'homeTeam' => $team('MTL', 'Canadiens', null), 'awayTeam' => $team('BOS', 'Bruins', null),
                'tvBroadcasts' => [['countryCode' => 'CA', 'network' => 'TSN2'], ['countryCode' => 'CA', 'network' => 'RDS']],
            ],
        };

        return [FetchHabsSchedule::normalize($game + ['id' => 1, 'gameScheduleState' => 'OK', 'venue' => ['default' => $state === 'final' ? 'Scotiabank Arena' : 'Centre Bell']])];
    }
}
