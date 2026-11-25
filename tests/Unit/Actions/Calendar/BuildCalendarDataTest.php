<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Calendar;

use App\Actions\Calendar\BuildCalendarData;
use App\Actions\Calendar\FetchCalendarFeed;
use App\Actions\Calendar\ParseIcs;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BuildCalendarDataTest extends TestCase
{
    private function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, ParseIcs::TIMEZONE);
    }

    public function test_html_notes_become_plain_text_with_line_breaks(): void
    {
        $this->assertSame("Line one\nLine two & <three>", BuildCalendarData::plainText('<p>Line one</p><b>Line two</b> &amp; &lt;three&gt;'));
        $this->assertSame('Join https://example.com/meet', BuildCalendarData::plainText('Join <a href="https://example.com/meet">https://example.com/meet</a>'));
    }

    public function test_days_cover_all_day_ranges_and_clamp_to_the_loaded_range(): void
    {
        $from = $this->at('2026-05-01');
        $to = $this->at('2027-12-01');
        $camp = ['allDay' => true, 'start' => $this->at('2026-04-29'), 'end' => $this->at('2026-05-03')];
        $timed = ['allDay' => false, 'start' => $this->at('2026-11-24 22:00'), 'end' => $this->at('2026-11-25 01:00')];

        $this->assertSame(['2026-05-01', '2026-05-02'], BuildCalendarData::days($camp, $from, $to));
        $this->assertSame(['2026-11-24'], BuildCalendarData::days($timed, $from, $to));
    }

    public function test_pasted_links_become_feed_addresses(): void
    {
        $this->assertSame('https://p103-caldav.icloud.com/published/2/abc', FetchCalendarFeed::httpUrl(' webcal://p103-caldav.icloud.com/published/2/abc '));
        $this->assertSame(
            'https://calendar.google.com/calendar/ical/abc123%40group.calendar.google.com/public/basic.ics',
            FetchCalendarFeed::httpUrl('https://calendar.google.com/calendar/embed?src=abc123%40group.calendar.google.com&ctz=America%2FToronto'),
        );
        $this->assertSame('https://calendar.google.com/calendar/ical/x/basic.ics', FetchCalendarFeed::httpUrl('https://calendar.google.com/calendar/ical/x/basic.ics'));
    }
}
