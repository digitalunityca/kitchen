<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Modules\Calendar\CalendarStore;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Connect a calendar by its iCal link, after checking that the link really serves a calendar. */
class AddCalendar
{
    public function __construct(
        private readonly CalendarStore $store,
        private readonly FetchCalendarFeed $fetchCalendarFeed,
    ) {}

    /** @throws ValidationException when the link doesn't give a calendar */
    public function handle(string $name, string $color, string $url, ?string $icon = null, ?int $reminder = null, bool $speak = false): void
    {
        $url = FetchCalendarFeed::httpUrl($url);

        if ($this->fetchCalendarFeed->handle(['name' => $name, 'url' => $url], fresh: true) === null) {
            throw ValidationException::withMessages(['url' => 'That link didn’t give a calendar. Copy the iCal (.ics) address again and check it is the secret/public one.']);
        }

        $calendars = $this->store->all();
        $calendars[] = ['id' => Str::lower(Str::random(8)), 'name' => $name, 'color' => $color, 'icon' => (string) $icon, 'reminder' => $reminder, 'speak' => $speak, 'url' => $url, 'visible' => true];
        $this->store->save($calendars);
    }
}
