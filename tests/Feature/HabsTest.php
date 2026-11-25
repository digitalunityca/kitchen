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

    private function withGameAndStandings(): void
    {
        $this->nhl += [
            'gamecenter/2026020379/landing' => $this->nhlFixture('landing'),
            'gamecenter/2026020379/right-rail' => $this->nhlFixture('right-rail'),
            'gamecenter/2026020379/boxscore' => $this->nhlFixture('boxscore'),
            'standings/now' => $this->nhlFixture('standings'),
        ];
    }

    public function test_the_stats_sheet_game_tab_shows_the_latest_game(): void
    {
        $this->at('2026-11-24 15:00');
        $this->withGameAndStandings();

        $this->get('/modules/habs/sheet')->assertOk()
            ->assertSeeInOrder(['data-tab="game"', 'data-tab="standings"', 'data-tab="schedule"'], false)
            ->assertSee('Final')
            ->assertSee('A. Newhook')
            ->assertSee('Q. Byfield, A. Panarin')
            ->assertSee('J. Dobes</b> 20/22 saves', false);
    }

    public function test_the_default_tab_is_game_on_game_days_and_standings_otherwise(): void
    {
        $this->withGameAndStandings();

        $this->at('2026-11-24 15:00');
        $this->get('/modules/habs/sheet')->assertSee('data-default="standings"', false);

        $this->at('2026-11-25 12:00');
        $this->get('/modules/habs/sheet')->assertSee('data-default="game"', false);
    }

    public function test_the_standings_tab_has_division_conference_and_league_tables_with_the_habs_highlighted(): void
    {
        $this->at('2026-11-24 15:00');
        $this->withGameAndStandings();

        $response = $this->get('/modules/habs/sheet')->assertOk()
            ->assertSee('14-7-4')
            ->assertSee('data-table="division">Atlantic', false)
            ->assertSee('data-table="conference">Eastern', false)
            ->assertSee('data-scope="league"', false)
            ->assertSee('https://assets.nhle.com/logos/nhl/svg/OTT_light.svg', false);

        $division = (string) str($response->getContent())->betweenFirst('data-scope="division"', '</table>');
        $this->assertSame(8, substr_count($division, '<tr class='), 'all eight Atlantic teams');
        $this->assertSame(1, substr_count($division, 'habs-us'));
        $this->assertMatchesRegularExpression('/<tr class="habs-us habs-cut">\s*<td>3<\/td>/', $division, 'the Canadiens are 3rd, right on the playoff line');
        $this->assertSame(16, substr_count((string) str($response->getContent())->betweenFirst('data-scope="conference"', '</table>'), '<tr class='));
        $this->assertSame(32, substr_count((string) str($response->getContent())->betweenFirst('data-scope="league"', '</table>'), '<tr class='));
    }

    public function test_the_schedule_tab_lists_the_last_five_results_and_next_five_games(): void
    {
        $this->at('2026-11-24 15:00');
        $this->withGameAndStandings();

        $this->get('/modules/habs/sheet')->assertOk()
            ->assertSee('Last 5')
            ->assertSee('Next 5')
            ->assertSee('vs Kings')
            ->assertSee('at Avalanche')
            ->assertSee('9:30 PM · Ball Arena · TV TSN2, RDS');
    }

    public function test_the_side_menu_holds_the_canadiens_logo_then_the_chatgpt_mic(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<nav class="side-menu"', 'id="menu-home"', 'class="menu-divider"', 'id="habs-open"', 'MTL_light.svg', 'MTL_dark.svg', 'id="aic-fab"', '</nav>'], false)
            ->assertDontSee('<p class="muted">', false)
            ->assertDontSee('<header>', false);
    }

    public function test_the_stats_sheet_says_so_when_the_nhl_is_unreachable(): void
    {
        $this->nhl['club-schedule-season/MTL/now'] = 503;

        $this->get('/modules/habs/sheet')->assertOk()->assertSee('Can’t reach the NHL right now');
    }

    public function test_the_stats_sheet_opens_full_size_with_a_centred_spinner(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<div class="habs-sheet-body" id="habs-sheet-body"><div class="habs-loading" role="status" aria-label="Loading">', false)
            ->assertSee('.habs-sheet { width: 100%; height: 100%;', false);
    }
}
