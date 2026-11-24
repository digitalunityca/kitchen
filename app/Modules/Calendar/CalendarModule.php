<?php

namespace App\Modules\Calendar;

use App\Dashboard\Module;

class CalendarModule extends Module
{
    public function key(): string
    {
        return 'calendar';
    }

    public function name(): string
    {
        return 'Calendar';
    }

    // Sample events until a calendar source is connected.
    public function data(): array
    {
        return ['events' => $this->config('events', [
            ['title' => 'School drop-off', 'time' => '8:00 – 8:30 AM', 'color' => '#0071E3'],
            ['title' => 'Grocery pickup', 'time' => '12:30 PM', 'color' => '#34C759'],
            ['title' => 'Dentist', 'time' => '3:15 PM', 'color' => '#FF9500'],
            ['title' => 'Family dinner', 'time' => '6:30 PM', 'color' => '#AF52DE'],
        ])];
    }
}
