<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Modules\Calendar\CalendarStore;

class MoveCalendarUp
{
    public function __construct(private readonly CalendarStore $store) {}

    /** Swap with the calendar above; the first one stays put. */
    public function handle(string $id): void
    {
        $calendars = $this->store->all();
        $i = array_search($id, array_column($calendars, 'id'), true);

        if ($i === false || $i < 1) {
            return;
        }

        [$calendars[$i - 1], $calendars[$i]] = [$calendars[$i], $calendars[$i - 1]];
        $this->store->save($calendars);
    }
}
