<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Habs;

use App\Actions\Habs\FetchHabsSchedule;
use App\Actions\Habs\FindHabsHighlights;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FindHabsHighlightsTest extends TestCase
{
    /** @return list<array<string, mixed>> */
    private function games(?callable $change = null): array
    {
        $raw = $this->nhlFixture('season-schedule')['games'];

        if ($change) {
            $raw = array_map($change, $raw);
        }

        return array_map(fn (array $g): array => FetchHabsSchedule::normalize($g), $raw);
    }

    private function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, FetchHabsSchedule::TIMEZONE);
    }

    /** @return list<string> */
    private function kinds(array $items): array
    {
        return array_column($items, 'kind');
    }

    public function test_the_day_after_a_game_shows_the_final_until_6_pm_plus_tomorrows_game(): void
    {
        $items = (new FindHabsHighlights)->handle($this->games(), $this->at('2026-11-24 15:00'));

        $this->assertSame(['final', 'upcoming'], $this->kinds($items));
        $this->assertSame('LAK', $items[0]['game']['opponent']['abbrev']);
        $this->assertSame([1, 2], [$items[0]['game']['mtl']['score'], $items[0]['game']['opponent']['score']]);
        $this->assertSame('COL', $items[1]['game']['opponent']['abbrev']);
        $this->assertSame('Tomorrow 9:30 PM', $items[1]['when']);
    }

    public function test_after_6_pm_only_the_upcoming_game_remains(): void
    {
        $this->assertSame(['upcoming'], $this->kinds((new FindHabsHighlights)->handle($this->games(), $this->at('2026-11-24 19:00'))));
    }

    public function test_game_day_says_tonight_and_gives_an_action_bar_note(): void
    {
        $now = $this->at('2026-11-25 12:00');
        $items = (new FindHabsHighlights)->handle($this->games(), $now);

        $this->assertSame('Tonight 9:30 PM', $items[0]['when']);
        $this->assertSame('🏒 Habs 9:30 PM', FindHabsHighlights::titleNote($items, $now));
        $this->assertNull(FindHabsHighlights::titleNote((new FindHabsHighlights)->handle($this->games(), $this->at('2026-11-24 19:00')), $this->at('2026-11-24 19:00')));
    }

    public function test_a_live_game_comes_first_with_its_score_in_the_note(): void
    {
        $now = $this->at('2026-11-25 22:30');
        $games = $this->games(fn (array $g): array => $g['id'] === 2026020397
            ? array_replace_recursive($g, ['gameState' => 'LIVE', 'awayTeam' => ['score' => 3], 'homeTeam' => ['score' => 1], 'periodDescriptor' => ['number' => 2]])
            : $g);

        $items = (new FindHabsHighlights)->handle($games, $now);

        $this->assertSame('live', $items[0]['kind']);
        $this->assertSame('🏒 Habs 3–1', FindHabsHighlights::titleNote($items, $now));
    }

    public function test_no_game_in_range_means_no_ticker(): void
    {
        $this->assertSame([], (new FindHabsHighlights)->handle($this->games(), $this->at('2026-12-03 19:00')));
    }

    public function test_when_labels(): void
    {
        $now = $this->at('2026-11-24 09:00');

        $this->assertSame('Today 1:00 PM', FindHabsHighlights::when($this->at('2026-11-24 13:00'), $now));
        $this->assertSame('Tonight 7:00 PM', FindHabsHighlights::when($this->at('2026-11-24 19:00'), $now));
        $this->assertSame('Tomorrow 7:30 PM', FindHabsHighlights::when($this->at('2026-11-25 19:30'), $now));
        $this->assertSame('Sat 4:00 PM', FindHabsHighlights::when($this->at('2026-11-28 16:00'), $now));
    }
}
