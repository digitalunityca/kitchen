<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Weather;

use App\Actions\Weather\BuildWeatherSummary;
use App\Actions\Weather\DescribePrecipitation;
use App\Actions\Weather\ParseForecast;
use Tests\TestCase;

class ParseForecastTest extends TestCase
{
    private function parse(array $data): array
    {
        return (new ParseForecast(new DescribePrecipitation, new BuildWeatherSummary))->handle($data);
    }

    public function test_it_keeps_the_next_four_hours_and_uses_night_icons_after_7_pm(): void
    {
        $hours = range(17, 23);

        $forecast = $this->parse([
            'current' => ['time' => '2026-11-24T17:20', 'temperature_2m' => 1.5, 'weather_code' => 0, 'is_day' => 0],
            'hourly' => [
                'time' => array_map(fn (int $h): string => "2026-11-24T$h:00", $hours),
                'temperature_2m' => array_fill(0, 7, 0.4),
                'precipitation_probability' => array_fill(0, 7, 0),
                'weather_code' => array_fill(0, 7, 1),
            ],
            'daily' => ['temperature_2m_max' => [3.6], 'temperature_2m_min' => [-2.5]],
        ]);

        $this->assertSame('clear-night', $forecast['icon']);
        $this->assertSame(2, $forecast['temp']);
        $this->assertSame([4, -3], [$forecast['high'], $forecast['low']]);
        $this->assertSame(['6 PM', '7 PM', '8 PM', '9 PM'], array_column($forecast['hours'], 'label'));
        $this->assertSame(['partly', 'partly-night', 'partly-night', 'partly-night'], array_column($forecast['hours'], 'icon'));
        $this->assertNull($forecast['precip']);
    }

    public function test_tomorrows_hours_do_not_count_towards_todays_rain_note(): void
    {
        $forecast = $this->parse([
            'current' => ['time' => '2026-11-24T22:10', 'temperature_2m' => 0, 'weather_code' => 3, 'is_day' => 0],
            'hourly' => [
                'time' => ['2026-11-24T22:00', '2026-11-24T23:00', '2026-11-25T00:00', '2026-11-25T01:00'],
                'temperature_2m' => [0, 0, 0, 0],
                'precipitation_probability' => [0, 0, 90, 90],
                'weather_code' => [3, 3, 61, 61],
            ],
            'daily' => ['temperature_2m_max' => [1], 'temperature_2m_min' => [-1]],
        ]);

        $this->assertNull($forecast['precip']);
        $this->assertCount(3, $forecast['hours']);
    }
}
