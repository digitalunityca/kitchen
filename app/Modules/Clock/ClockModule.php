<?php

namespace App\Modules\Clock;

use App\Dashboard\Module;

class ClockModule extends Module
{
    public function key(): string
    {
        return 'clock';
    }

    public function name(): string
    {
        return 'Clock';
    }
}
