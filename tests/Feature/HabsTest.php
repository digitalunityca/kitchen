<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Habs\FetchHabsSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HabsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->nhl['club-schedule-season/MTL/now'] = $this->nhlFixture('season-schedule');
    }

    private function at(string $local): void
    {
        $this->travelTo(CarbonImmutable::parse($local, FetchHabsSchedule::TIMEZONE));
    }

    public function test_the_day_after_a_game_the_strip_shows_the_final_and_the_next_game(): void
    {
        $this->at('2026-11-24 15:00');

        $this->get('/')->assertOk()
            ->assertSee('id="habs-strip"', false)
            ->assertSee('1 – 2')
            ->assertSee('Final')
            ->assertSee('Canadiens vs Kings')
            ->assertSee('Canadiens at Avalanche')
            ->assertSee('Tomorrow 9:30 PM')
            ->assertSee('TV TSN2, RDS')
            ->assertSee('https://assets.nhle.com/logos/nhl/svg/LAK_light.svg', false)
            ->assertSee('https://assets.nhle.com/logos/nhl/svg/COL_dark.svg', false);
    }

    public function test_on_game_day_the_action_bar_gets_a_note(): void
    {
        $this->at('2026-11-25 12:00');

        $this->get('/')->assertOk()->assertSee('Tonight 9:30 PM')->assertSee('data-title="🏒 Habs 9:30 PM"', false);
    }

    public function test_no_game_in_range_means_no_strip_but_the_stats_button_stays(): void
    {
        $this->at('2026-12-03 19:00');

        $this->get('/')->assertOk()->assertDontSee('id="habs-strip"', false)->assertSee('id="habs-open"', false);
    }

    public function test_when_the_nhl_api_fails_the_strip_is_hidden_and_the_page_still_works(): void
    {
        $this->nhl['club-schedule-season/MTL/now'] = 500;

        $this->get('/')->assertOk()->assertDontSee('id="habs-strip"', false);
        $this->getJson('/modules/habs/ticker')->assertOk()->assertJsonPath('html', '')->assertJsonPath('title', null);
    }

    public function test_demo_states_preview_live_and_final(): void
    {
        $this->get('/?habs=live')->assertOk()->assertSee('habs-strip habs-state-live', false)->assertSee('2 – 1')->assertSee('2nd · 12:34');
        $this->get('/?habs=final')->assertOk()->assertSee('3 – 2')->assertSee('Final/OT')->assertSee('habs-result habs-w', false);
        $this->getJson('/modules/habs/ticker?habs=upcoming')->assertOk()->assertJsonPath('title', '🏒 Habs 7 PM');
    }

    public function test_the_stats_sheet_shows_the_latest_game_standing_last_five_and_next_games(): void
    {
        $this->at('2026-11-24 15:00');
        $this->nhl += [
            'gamecenter/2026020379/landing' => $this->nhlFixture('landing'),
            'gamecenter/2026020379/right-rail' => $this->nhlFixture('right-rail'),
            'gamecenter/2026020379/boxscore' => $this->nhlFixture('boxscore'),
            'standings/now' => $this->nhlFixture('standings'),
        ];

        $this->get('/modules/habs/sheet')->assertOk()
            ->assertSee('Final')
            ->assertSee('A. Newhook')
            ->assertSee('Q. Byfield, A. Panarin')
            ->assertSee('J. Dobes</b> 20/22 saves', false)
            ->assertSee('14-7-4')
            ->assertSee('Atlantic #3 · Eastern #5')
            ->assertSee('Last 5')
            ->assertSee('at Avalanche')
            ->assertSee('Tomorrow 9:30 PM');
    }

    public function test_the_stats_sheet_says_so_when_the_nhl_is_unreachable(): void
    {
        $this->nhl['club-schedule-season/MTL/now'] = 503;

        $this->get('/modules/habs/sheet')->assertOk()->assertSee('Can’t reach the NHL right now');
    }
}
