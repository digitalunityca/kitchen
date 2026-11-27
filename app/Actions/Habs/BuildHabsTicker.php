<?php

declare(strict_types=1);

namespace App\Actions\Habs;

use App\Modules\Habs\DemoGames;
use Carbon\CarbonImmutable;

/** The game highlights (with the live clock when a game is on), today's game for the weather card, and the action bar note. */
class BuildHabsTicker
{
    public function __construct(
        private readonly FetchHabsSchedule $fetchHabsSchedule,
        private readonly FindHabsHighlights $findHabsHighlights,
        private readonly FetchGameDetails $fetchGameDetails,
    ) {}

    /** @return array{items: list<array<string, mixed>>, today: array<string, mixed>|null, title: string|null} */
    public function handle(CarbonImmutable $now, ?string $demo = null): array
    {
        $isDemo = in_array($demo, DemoGames::STATES, true);
        $games = $isDemo ? DemoGames::make($demo, $now) : $this->fetchHabsSchedule->handle();

        if ($games === null) {
            return ['items' => [], 'today' => null, 'title' => null];
        }

        $items = array_map(function (array $item) use ($isDemo): array {
            $game = $item['game'];
            $item['status'] = match ($item['kind']) {
                'final' => __('Final').($game['lastPeriodType'] && $game['lastPeriodType'] !== 'REG' ? '/'.$game['lastPeriodType'] : ''),
                'live' => $this->liveStatus($game, $isDemo),
                default => null,
            };
            $item['result'] = $item['kind'] === 'final' ? ($game['mtl']['score'] > $game['opponent']['score'] ? 'W' : 'L') : null;

            return $item;
        }, $this->findHabsHighlights->handle($games, $now));

        return ['items' => $items, 'today' => FindHabsHighlights::today($items, $now), 'title' => FindHabsHighlights::titleNote($items, $now)];
    }

    /** "2nd · 12:34", "2nd intermission", or "2nd" without a clock. */
    private function liveStatus(array $game, bool $isDemo): string
    {
        $period = FetchGameDetails::periodLabel($game['period'], $game['periodType']);
        $details = $isDemo ? ['clock' => '12:34', 'intermission' => false] : $this->fetchGameDetails->handle($game['id'], true);

        if ($details && $details['intermission']) {
            return $period.' '.__('intermission');
        }

        return $details && $details['clock'] ? $period.' · '.$details['clock'] : $period;
    }
}
