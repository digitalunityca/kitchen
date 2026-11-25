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
        $this->get('/')->assertOk()->assertSee('>Sample data</a>', false)->assertSee('School drop-off')->assertSee('Tomorrow');
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

    public function test_the_card_shows_today_and_tomorrow_with_calendar_colours(): void
    {
        $this->addFamily();

        $this->get('/')->assertOk()
            ->assertDontSee('School drop-off')
            ->assertSeeInOrder(['Today', 'Swimming lessons', '5:00 – 6:00 PM', 'Centre aquatique', 'New Year party', '10:00 PM – 1:00 AM', 'Tomorrow', 'Nothing planned'])
            ->assertSee('background:#FF9500', false);
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
            ->assertJsonPath('calendars.0', ['id' => $id, 'name' => 'Family', 'color' => '#FF9500'])
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

    public function test_the_card_shows_the_next_event_after_tomorrow_and_its_details_open(): void
    {
        $this->addFamily();

        $this->get('/')->assertOk()->assertSeeInOrder(['Tomorrow', 'Nothing planned', 'Next', 'Pedagogical day', 'Fri Nov 27 · All day'], false);

        $this->travelTo(CarbonImmutable::parse('2026-12-10 12:00', ParseIcs::TIMEZONE));
        $html = $this->get('/')->assertOk()->assertSee('Tue Dec 15 · 5:00 – 6:00 PM')->getContent();
        preg_match('/Next<\/p>\s*<button type="button" class="ev" data-event="([a-f0-9]{12})"/', $html, $m);

        $this->assertContains($m[1], array_column($this->getJson('/modules/calendar/events')->json('events'), 'id'), 'the sheet can show its details');
    }
}
