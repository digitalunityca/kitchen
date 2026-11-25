<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Calendar\ParseIcs;
use App\Modules\Calendar\CalendarStore;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    private const FEED = 'https://calendar.example.com/ical/secret-token-123/basic.ics';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-24 12:00', ParseIcs::TIMEZONE));
        Http::fake(['calendar.example.com/*' => Http::response((string) file_get_contents(base_path('tests/Fixtures/ics/family.ics')), 200, ['Content-Type' => 'text/calendar'])]);
    }

    private function addFamily(): void
    {
        $this->post('/modules/calendar/calendars', ['name' => 'Family', 'color' => '#FF9500', 'url' => self::FEED])
            ->assertRedirect('/modules/calendar/settings');
    }

    public function test_without_calendars_the_card_shows_sample_events(): void
    {
        $this->get('/')->assertOk()->assertSee('>Sample data</a>', false);
        $this->assertContains('School drop-off', array_column($this->cardData()['events'], 'title'));
    }

    public function test_adding_a_calendar_checks_the_link_and_keeps_it_secret(): void
    {
        $this->addFamily();

        $saved = app(CalendarStore::class)->all();
        $this->assertSame([['Family', '#FF9500', self::FEED, true]], array_map(fn ($c) => [$c['name'], $c['color'], $c['url'], $c['visible']], $saved));

        $this->get('/modules/calendar/settings')->assertOk()
            ->assertSee('calendar.example.com/…')
            ->assertDontSee('secret-token-123');
    }

    public function test_webcal_links_are_fetched_over_https(): void
    {
        $this->post('/modules/calendar/calendars', ['name' => 'iCloud', 'color' => '#0071E3', 'url' => 'webcal://calendar.example.com/ical/secret-token-123/basic.ics'])->assertRedirect();

        $this->assertSame(self::FEED, app(CalendarStore::class)->all()[0]['url']);
        Http::assertSent(fn (Request $r): bool => $r->url() === self::FEED);
    }

    public function test_a_link_that_is_not_a_calendar_is_rejected(): void
    {
        Http::fake(['nope.example.com/*' => Http::response('<html>Sign in</html>')]);

        $this->from('/modules/calendar/settings')
            ->post('/modules/calendar/calendars', ['name' => 'Bad', 'color' => '#0071E3', 'url' => 'https://nope.example.com/x'])
            ->assertSessionHasErrors('url');
        $this->post('/modules/calendar/calendars', ['name' => 'Bad', 'color' => 'red', 'url' => 'ftp://x'])->assertSessionHasErrors(['color', 'url']);
        $this->assertSame([], app(CalendarStore::class)->all());
    }

    /** The JSON the calendar card draws itself from. */
    private function cardData(): array
    {
        preg_match('#<script type="application/json" id="cal-data">(.*?)</script>#s', $this->get('/')->assertOk()->getContent(), $m);

        return json_decode($m[1], true);
    }

    public function test_the_card_gets_this_week_and_next_with_calendar_colours(): void
    {
        $this->addFamily();
        $data = $this->cardData();

        $this->assertSame(['2026-11-24', '2026-11-22'], [$data['today'], $data['weekStart']], 'weeks start on Sunday');
        $this->assertSame(['Family'], array_column($data['calendars'], 'name'));
        $titles = array_map(fn ($e) => $e['days'][0].' '.$e['title'], $data['events']);
        $this->assertSame([
            '2026-11-24 Swimming lessons', '2026-11-24 New Year party', '2026-11-27 Pedagogical day',
            '2026-12-01 Swimming lessons', '2026-12-03 Swimming lessons',
        ], $titles);
        $this->assertSame('#FF9500', $data['events'][0]['color']);
        $this->assertDontSeeTitleOutsideTheWindow($data);
    }

    private function assertDontSeeTitleOutsideTheWindow(array $data): void
    {
        foreach ($data['events'] as $event) {
            $this->assertTrue($event['days'][0] >= '2026-11-22' && $event['days'][0] < '2026-12-06');
        }
    }

    public function test_the_calendar_data_covers_six_months_back_to_twelve_ahead(): void
    {
        $this->addFamily();
        $id = app(CalendarStore::class)->all()[0]['id'];

        $data = $this->getJson('/modules/calendar/events')->assertOk()
            ->assertJsonPath('sample', false)
            ->assertJsonPath('today', '2026-11-24')
            ->assertJsonPath('from', '2026-05-01')
            ->assertJsonPath('to', '2027-11-30')
            ->assertJsonPath('calendars.0', ['id' => $id, 'name' => 'Family', 'color' => '#FF9500', 'icon' => ''])
            ->json();

        $swims = array_values(array_filter($data['events'], fn ($e) => $e['title'] === 'Swimming lessons'));
        $this->assertSame('2026-11-03T17:00', $swims[0]['start'], 'the first lesson, three weeks back');
        $this->assertNotContains('2026-11-26T17:00', array_column($swims, 'start'), 'the EXDATE');
        $this->assertSame("Bring goggles.\nPickup at 6 PM.", $swims[0]['notes']);
        $this->assertSame($id, $swims[0]['calendarId']);

        $holiday = collect($data['events'])->firstWhere('title', 'Pedagogical day');
        $this->assertSame(['2026-11-27', '2026-11-28'], $holiday['days']);
        $party = collect($data['events'])->firstWhere('title', 'New Year party');
        $this->assertSame(['2026-11-24'], $party['days']);
        $this->assertSame(['Ana', 'bob@example.com'], $party['attendees']);
    }

    public function test_hidden_calendars_leave_the_card_and_the_calendar_data(): void
    {
        $this->addFamily();
        $id = app(CalendarStore::class)->all()[0]['id'];

        $this->post("/modules/calendar/calendars/{$id}", ['visible' => 0])->assertRedirect();

        $this->get('/')->assertDontSee('Swimming lessons');
        $this->getJson('/modules/calendar/events')->assertJsonPath('calendars', [])->assertJsonPath('events', []);
    }

    public function test_rename_recolour_reorder_and_remove(): void
    {
        $this->addFamily();
        $this->post('/modules/calendar/calendars', ['name' => 'School', 'color' => '#34C759', 'url' => self::FEED]);
        [$family, $school] = array_column(app(CalendarStore::class)->all(), 'id');

        $this->post("/modules/calendar/calendars/{$family}", ['name' => 'Home', 'color' => '#AF52DE'])->assertRedirect();
        $this->post("/modules/calendar/calendars/{$school}/up")->assertRedirect();
        $this->assertSame([['School', '#34C759'], ['Home', '#AF52DE']], array_map(fn ($c) => [$c['name'], $c['color']], app(CalendarStore::class)->all()));

        $this->post("/modules/calendar/calendars/{$school}/delete")->assertRedirect();
        $this->assertSame(['Home'], array_column(app(CalendarStore::class)->all(), 'name'));
    }

    public function test_a_failing_feed_keeps_showing_the_last_good_copy(): void
    {
        $this->addFamily();
        $this->get('/')->assertSee('Swimming lessons');

        $this->travel(16)->minutes();
        Http::fake(['calendar.example.com/*' => Http::response('down', 500)]);

        $this->get('/')->assertOk()->assertSee('Swimming lessons');
    }

    public function test_the_card_gets_each_calendars_next_event_after_tomorrow(): void
    {
        $this->addFamily();
        $this->assertSame(['Pedagogical day'], array_column($this->cardData()['next'], 'title'));

        $this->travelTo(CarbonImmutable::parse('2026-12-10 12:00', ParseIcs::TIMEZONE));
        $next = $this->cardData()['next'];

        $this->assertSame(['Swimming lessons'], array_column($next, 'title'));
        $this->assertSame('2026-12-15T17:00', $next[0]['start']);
        $this->assertContains($next[0]['id'], array_column($this->getJson('/modules/calendar/events')->json('events'), 'id'), 'the month view can show its details');
    }

    public function test_a_calendar_can_have_an_icon_shown_before_its_events(): void
    {
        $this->post('/modules/calendar/calendars', ['name' => 'Theo Soccer', 'color' => '#FF9500', 'icon' => '⚽', 'url' => self::FEED])->assertRedirect();
        $id = app(CalendarStore::class)->all()[0]['id'];

        $this->get('/modules/calendar/settings')->assertSee('⚽ Theo Soccer');
        $data = $this->getJson('/modules/calendar/events')->assertJsonPath('calendars.0.icon', '⚽')->json();
        $this->assertSame('⚽', $data['events'][0]['icon']);

        $this->post("/modules/calendar/calendars/{$id}", ['name' => 'Theo Soccer', 'color' => '#FF9500', 'icon' => ''])->assertRedirect();
        $this->getJson('/modules/calendar/events')->assertJsonPath('calendars.0.icon', '');
    }

    public function test_the_card_data_can_be_polled_and_picks_up_new_calendars_at_once(): void
    {
        $this->getJson('/modules/calendar/card')->assertOk()->assertJsonPath('calendars.0.name', 'Family')->assertJsonPath('calendars.1.name', 'School');

        $this->addFamily();

        $this->getJson('/modules/calendar/card')->assertOk()
            ->assertJsonPath('weekStart', '2026-11-22')
            ->assertJsonCount(1, 'calendars')
            ->assertJsonPath('events.0.title', 'Swimming lessons')
            ->assertJsonPath('next.0.title', 'Pedagogical day');
    }

    public function test_a_calendar_reminder_reaches_its_events_and_an_events_own_alarm_wins(): void
    {
        Http::fake(['alarms.example.com/*' => Http::response((string) file_get_contents(base_path('tests/Fixtures/ics/alarms.ics')))]);
        $this->post('/modules/calendar/calendars', ['name' => 'Theo Soccer', 'color' => '#FF9500', 'icon' => '⚽', 'reminder' => 30, 'speak' => 1, 'url' => 'https://alarms.example.com/feed.ics'])->assertRedirect();

        $this->get('/modules/calendar/settings')->assertSee('reminder 30 minutes before, spoken');
        $events = collect($this->getJson('/modules/calendar/events')->json('events'))->keyBy('title');

        $this->assertSame([30, true], [$events['Plain']['remind'], $events['Plain']['speak']]);
        $this->assertSame(45, $events['Soccer game']['remind'], 'the event\'s own alarm takes precedence');
        $this->assertSame('Coach Martin', $events['Soccer game']['organizer']);

        $id = app(CalendarStore::class)->all()[0]['id'];
        $this->post("/modules/calendar/calendars/{$id}", ['name' => 'Theo Soccer', 'color' => '#FF9500', 'icon' => '⚽', 'reminder' => ''])->assertRedirect();
        $this->assertNull(collect($this->getJson('/modules/calendar/events')->json('events'))->firstWhere('title', 'Plain')['remind']);
    }
}
