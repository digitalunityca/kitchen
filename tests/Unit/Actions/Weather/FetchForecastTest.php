<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Weather;

use App\Actions\Weather\FetchForecast;
use App\Modules\Weather\WeatherLocation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchForecastTest extends TestCase
{
    private function fetch(?float $latitude = 45.42, ?float $longitude = -75.69): array
    {
        $this->app->instance(WeatherLocation::class, new WeatherLocation($latitude, $longitude));

        return $this->app->make(FetchForecast::class)->handle();
    }

    public function test_without_a_location_it_returns_the_sample_without_calling_the_api(): void
    {
        Http::fake();

        $forecast = $this->fetch(45.42, null);

        $this->assertFalse($forecast['real']);
        $this->assertSame('⛅ 4° Partly cloudy · H 6° L −1°', $forecast['summary']);
        Http::assertNothingSent();
    }

    public function test_a_connection_error_falls_back_to_the_sample(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->assertFalse($this->fetch()['real']);
    }

    public function test_failures_are_not_cached_so_the_next_request_tries_again(): void
    {
        Http::fakeSequence()->push('oops', 500)->push([
            'current' => ['time' => '2026-11-24T13:45', 'temperature_2m' => 3, 'weather_code' => 0, 'is_day' => 1],
            'daily' => ['temperature_2m_max' => [5], 'temperature_2m_min' => [1]],
        ]);

        $this->assertFalse($this->fetch()['real']);
        $this->assertTrue($this->fetch()['real']);
    }

    public function test_location_from_config_ignores_values_that_are_not_numbers(): void
    {
        $this->assertTrue(WeatherLocation::fromConfig('45.42', -75.69)->isSet());
        $this->assertFalse(WeatherLocation::fromConfig('', '-75.69')->isSet());
        $this->assertFalse(WeatherLocation::fromConfig(null, 'west')->isSet());
    }
}
