<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Habs;

use App\Actions\Habs\FetchGameDetails;
use App\Actions\Habs\FetchHabsSchedule;
use App\Actions\Habs\FetchHabsStanding;
use Tests\TestCase;

class NhlDataTest extends TestCase
{
    public function test_the_schedule_is_normalized_from_montreals_side(): void
    {
        $this->nhl['club-schedule-season/MTL/now'] = $this->nhlFixture('season-schedule');

        $games = app(FetchHabsSchedule::class)->handle();
        $lastHome = collect($games)->firstWhere('id', 2026020379);
        $nextAway = collect($games)->firstWhere('id', 2026020397);

        $this->assertTrue($lastHome['final']);
        $this->assertTrue($lastHome['atHome']);
        $this->assertSame(['MTL', 'LAK'], [$lastHome['mtl']['abbrev'], $lastHome['opponent']['abbrev']]);
        $this->assertSame('Centre Bell', $lastHome['venue']);
        $this->assertTrue($nextAway['upcoming']);
        $this->assertFalse($nextAway['atHome']);
        $this->assertSame('2026-11-25 21:30', $nextAway['start']->format('Y-m-d H:i'));
        $this->assertSame(['TSN2', 'RDS'], $nextAway['broadcasts']);
        $this->assertStringStartsWith('https://assets.nhle.com/logos/nhl/svg/', $nextAway['opponent']['logo']);
    }

    public function test_an_unavailable_api_gives_null_and_is_not_retried_for_a_minute(): void
    {
        $this->assertNull(app(FetchHabsSchedule::class)->handle());

        $this->nhl['club-schedule-season/MTL/now'] = $this->nhlFixture('season-schedule');
        $this->assertNull(app(FetchHabsSchedule::class)->handle());

        $this->travel(61)->seconds();
        $this->assertNotNull(app(FetchHabsSchedule::class)->handle());
    }

    public function test_game_details_merge_landing_right_rail_and_boxscore(): void
    {
        $this->nhl['gamecenter/2026020379/landing'] = $this->nhlFixture('landing');
        $this->nhl['gamecenter/2026020379/right-rail'] = $this->nhlFixture('right-rail');
        $this->nhl['gamecenter/2026020379/boxscore'] = $this->nhlFixture('boxscore');

        $details = app(FetchGameDetails::class)->handle(2026020379, false);

        $this->assertSame(['1st', '2nd', '3rd'], array_column($details['periods'], 'label'));
        $this->assertSame(['away' => 22, 'home' => 30], $details['shots']);
        $this->assertSame(['away' => '1/1', 'home' => '0/3'], $details['powerPlay']);
        $this->assertCount(3, $details['goals']);
        $this->assertSame(['1st', '14:43', 'LAK', 'A. Laferriere', 'PP'], [$details['goals'][0]['period'], $details['goals'][0]['time'], $details['goals'][0]['team'], $details['goals'][0]['scorer'], $details['goals'][0]['strength']]);
        $this->assertSame(['Q. Byfield', 'A. Panarin'], $details['goals'][0]['assists']);
        $this->assertSame('A. Newhook', $details['goals'][1]['scorer']);
        $this->assertContains(['side' => 'home', 'name' => 'J. Dobes', 'saves' => 20, 'shots' => 22, 'decision' => 'L'], $details['goalies']);
        $this->assertNotContains('J. Fowler', array_column($details['goalies'], 'name'), 'a goalie who did not play is left out');
    }

    public function test_period_labels(): void
    {
        $this->assertSame(['1st', '2nd', '3rd', 'OT', '2OT', 'SO'], [
            FetchGameDetails::periodLabel(1, 'REG'), FetchGameDetails::periodLabel(2, 'REG'), FetchGameDetails::periodLabel(3, 'REG'),
            FetchGameDetails::periodLabel(4, 'OT'), FetchGameDetails::periodLabel(5, 'OT'), FetchGameDetails::periodLabel(5, 'SO'),
        ]);
    }

    public function test_the_standing(): void
    {
        $this->nhl['standings/now'] = $this->nhlFixture('standings');

        $this->assertSame([
            'record' => '14-7-4', 'points' => 32, 'gamesPlayed' => 25, 'division' => 'Atlantic', 'divisionRank' => 3,
            'conference' => 'Eastern', 'conferenceRank' => 5, 'streak' => 'L2', 'lastTen' => '5-4-1',
        ], app(FetchHabsStanding::class)->handle());
    }
}
