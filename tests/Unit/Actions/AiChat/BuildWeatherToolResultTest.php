<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AiChat;

use App\Actions\AiChat\BuildWeatherToolResult;
use App\Modules\AiChat\RealtimeSettings;
use App\Modules\Weather\WeatherLocation;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BuildWeatherToolResultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(RealtimeSettings::class, new RealtimeSettings(null, 'm', 'v', 'Sainte-Catherine, Quebec'));
    }

    public function test_it_returns_now_and_seven_days_tagged_today_and_tomorrow(): void
    {
        $this->app->instance(WeatherLocation::class, new WeatherLocation(45.4, -73.58));
        Http::fake(['api.open-meteo.com/*' => Http::response([
            'current' => ['time' => '2026-11-24T15:45', 'temperature_2m' => 2.4, 'weather_code' => 0, 'is_day' => 1],
            'daily' => [
                'time' => ['2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-11-29', '2026-11-30'],
                'temperature_2m_max' => [3.2, 5.6, 1, 0, -2, -4, -1],
                'temperature_2m_min' => [-3.1, -0.4, -5, -6, -8, -9, -7],
                'weather_code' => [0, 61, 3, 71, 73, 2, 1],
                'precipitation_probability_max' => [0, 80, 10, 60, 90, 5, null],
            ],
        ])]);

        $result = (new BuildWeatherToolResult(app(RealtimeSettings::class)))->handle('tomorrow');

        $this->assertTrue($result['available']);
        $this->assertSame('Sainte-Catherine, Quebec', $result['location']);
        $this->assertSame('tomorrow', $result['requested_day']);
        $this->assertSame(['temperature' => 2, 'conditions' => 'Clear', 'rain_today' => null], $result['now']);
        $this->assertCount(7, $result['days']);
        $this->assertSame(
            ['date' => '2026-11-25', 'weekday' => 'Wednesday', 'relative' => 'tomorrow', 'high' => 6, 'low' => 0, 'conditions' => 'Light rain', 'chance_of_precipitation_percent' => 80],
            $result['days'][1],
        );
        $this->assertSame('today', $result['days'][0]['relative']);
        $this->assertSame('in 6 days', $result['days'][6]['relative']);
        $this->assertNull($result['days'][6]['chance_of_precipitation_percent']);
    }

    public function test_without_a_real_forecast_it_says_the_weather_is_unavailable(): void
    {
        $this->app->instance(WeatherLocation::class, new WeatherLocation(null, null));

        $this->assertFalse((new BuildWeatherToolResult(app(RealtimeSettings::class)))->handle()['available']);
    }
}
