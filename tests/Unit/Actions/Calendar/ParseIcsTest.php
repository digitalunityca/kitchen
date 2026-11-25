<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Calendar;

use App\Actions\Calendar\LoadCalendarEvents;
use App\Actions\Calendar\ParseIcs;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ParseIcsTest extends TestCase
{
    /** @return list<array<string, mixed>> */
    private function parse(): array
    {
        $from = CarbonImmutable::parse('2026-11-24 00:00', ParseIcs::TIMEZONE);

        return (new ParseIcs)->handle((string) file_get_contents(__DIR__.'/../../../Fixtures/ics/family.ics'), $from, $from->addDays(14));
    }

    /** @return list<string> */
    private function starts(string $title): array
    {
        return array_map(fn (array $e): string => $e['start']->format('Y-m-d H:i'), array_values(array_filter($this->parse(), fn (array $e): bool => $e['title'] === $title)));
    }

    public function test_weekly_recurrence_is_expanded_and_exdates_are_skipped(): void
    {
        $this->assertSame(['2026-11-24 17:00', '2026-12-01 17:00', '2026-12-03 17:00'], $this->starts('Swimming lessons'));
    }

    public function test_all_day_events_keep_their_dates(): void
    {
        $holiday = collect($this->parse())->firstWhere('title', 'Pedagogical day');

        $this->assertTrue($holiday['allDay']);
        $this->assertSame(['2026-11-27', '2026-11-29'], [$holiday['start']->toDateString(), $holiday['end']->toDateString()]);
    }

    public function test_utc_times_convert_to_the_home_time_zone_across_midnight(): void
    {
        $party = collect($this->parse())->firstWhere('title', 'New Year party');

        $this->assertSame('2026-11-24 22:00', $party['start']->format('Y-m-d H:i'));
        $this->assertSame('2026-11-25 01:00', $party['end']->format('Y-m-d H:i'));
        $this->assertSame(['Ana', 'bob@example.com'], $party['attendees']);
    }

    public function test_cancelled_events_are_left_out_and_details_are_kept(): void
    {
        $events = $this->parse();

        $this->assertNotContains('Cancelled thing', array_column($events, 'title'));
        $swim = collect($events)->firstWhere('title', 'Swimming lessons');
        $this->assertSame('Centre aquatique, Sainte-Catherine', $swim['location']);
        $this->assertStringContainsString('Bring goggles.', $swim['description']);
    }

    public function test_something_that_is_not_ical_gives_null(): void
    {
        $this->assertNull((new ParseIcs)->handle('<html>Sign in</html>', CarbonImmutable::now(), CarbonImmutable::now()->addDay()));
    }

    public function test_grouping_by_day_puts_multi_day_all_day_events_on_each_day(): void
    {
        $from = CarbonImmutable::parse('2026-11-24 00:00', ParseIcs::TIMEZONE);
        $days = LoadCalendarEvents::byDay($this->parse(), $from, 14);

        $this->assertCount(14, $days);
        $this->assertSame(['Swimming lessons', 'New Year party'], array_column($days['2026-11-24'], 'title'));
        $this->assertSame([], $days['2026-11-25'], 'the party started the day before');
        $this->assertSame(['Pedagogical day'], array_column($days['2026-11-27'], 'title'));
        $this->assertSame(['Pedagogical day'], array_column($days['2026-11-28'], 'title'));
        $this->assertSame([], $days['2026-11-29']);
    }
}
