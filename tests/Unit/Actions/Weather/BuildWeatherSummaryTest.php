<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Weather;

use App\Actions\Weather\BuildWeatherSummary;
use Tests\TestCase;

class BuildWeatherSummaryTest extends TestCase
{
    private const FORECAST = [
        'temp' => 4, 'label' => 'Partly cloudy', 'icon' => 'partly', 'high' => 6, 'low' => -1,
        'precip' => null, 'precip_icon' => null,
    ];

    public function test_without_precipitation_the_line_stops_at_high_and_low(): void
    {
        $this->assertSame('⛅ 4° Partly cloudy · H 6° L −1°', (new BuildWeatherSummary)->handle(self::FORECAST));
    }

    public function test_likely_precipitation_is_appended_with_its_icon(): void
    {
        $forecast = ['precip' => 'Snow likely from 9 PM', 'precip_icon' => 'snow'] + self::FORECAST;

        $this->assertSame('⛅ 4° Partly cloudy · H 6° L −1° · 🌨️ Snow likely from 9 PM', (new BuildWeatherSummary)->handle($forecast));
    }
}
