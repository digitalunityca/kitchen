<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use App\Modules\Habs\NhlApi;
use Carbon\CarbonImmutable;

/**
 * The Canadiens' season schedule, normalized: one array per game with local
 * (America/Toronto) start time, state and both teams from Montreal's side.
 * Cached 10 minutes, or 2 minutes while a game is live or about to start.
 */
class FetchHabsSchedule
{
    public const TEAM = 'MTL';

    public const TIMEZONE = 'America/Toronto';

    public function __construct(private readonly NhlApi $api) {}

    /** @return list<array<string, mixed>>|null null when the NHL API is unavailable */
    public function handle(): ?array
    {
        $data = $this->api->get('club-schedule-season/'.self::TEAM.'/now', fn (array $data): int => $this->ttl($data));

        if ($data === null) {
            return null;
        }

        return array_values(array_map(fn (array $game): array => self::normalize($game), (array) ($data['games'] ?? [])));
    }

    /**
     * @param  array<string, mixed>  $game  a game from any NHL schedule or landing response
     * @return array<string, mixed>
     */
    public static function normalize(array $game): array
    {
        $atHome = data_get($game, 'homeTeam.abbrev') === self::TEAM;
        $state = (string) ($game['gameState'] ?? 'FUT');
        $start = CarbonImmutable::parse((string) ($game['startTimeUTC'] ?? 'now'))->setTimezone(self::TIMEZONE);

        return [
            'id' => (int) ($game['id'] ?? 0),
            'start' => $start,
            'date' => $start->toDateString(),
            'state' => $state,
            'live' => in_array($state, ['LIVE', 'CRIT'], true),
            'final' => in_array($state, ['FINAL', 'OFF'], true),
            'upcoming' => in_array($state, ['FUT', 'PRE'], true) && ($game['gameScheduleState'] ?? 'OK') === 'OK',
            'atHome' => $atHome,
            'mtl' => self::team((array) ($atHome ? $game['homeTeam'] : $game['awayTeam'])),
            'opponent' => self::team((array) ($atHome ? $game['awayTeam'] : $game['homeTeam'])),
            'venue' => (string) data_get($game, 'venue.default', ''),
            'period' => (int) data_get($game, 'periodDescriptor.number', 0),
            'periodType' => (string) data_get($game, 'periodDescriptor.periodType', 'REG'),
            'lastPeriodType' => data_get($game, 'gameOutcome.lastPeriodType'),
            'broadcasts' => array_values(array_unique(array_map(
                fn (array $b): string => (string) $b['network'],
                array_filter((array) ($game['tvBroadcasts'] ?? []), fn (array $b): bool => ($b['countryCode'] ?? '') === 'CA'),
            ))),
        ];
    }

    /**
     * @param  array<string, mixed>  $team
     * @return array{abbrev: string, name: string, place: string, logo: string, darkLogo: string, score: int|null}
     */
    private static function team(array $team): array
    {
        return [
            'abbrev' => (string) ($team['abbrev'] ?? ''),
            'name' => (string) data_get($team, 'commonName.default', $team['abbrev'] ?? ''),
            'place' => (string) data_get($team, 'placeName.default', ''),
            'logo' => (string) ($team['logo'] ?? ''),
            'darkLogo' => (string) ($team['darkLogo'] ?? $team['logo'] ?? ''),
            'score' => isset($team['score']) ? (int) $team['score'] : null,
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function ttl(array $data): int
    {
        foreach ((array) ($data['games'] ?? []) as $game) {
            $state = $game['gameState'] ?? '';
            $startsSoon = isset($game['startTimeUTC'])
                && abs(CarbonImmutable::parse($game['startTimeUTC'])->diffInMinutes(now(), false)) < 30;

            if (in_array($state, ['LIVE', 'CRIT'], true) || ($startsSoon && in_array($state, ['FUT', 'PRE'], true))) {
                return 120;
            }
        }

        return 600;
    }
}
