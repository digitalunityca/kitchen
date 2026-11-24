<?php

namespace App\Modules\Weather;

use App\Dashboard\Module;

class WeatherModule extends Module
{
    public function key(): string
    {
        return 'weather';
    }

    public function name(): string
    {
        return 'Weather';
    }

    // Sample data until a weather source is connected.
    public function data(): array
    {
        return ['city' => $this->config('city')];
    }
}
