<?php

declare(strict_types=1);

namespace App\Actions\Weather;

use App\Modules\Weather\WeatherLocation;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Current conditions and today's forecast from Open-Meteo (free, no API key).
 * Good results are cached for 15 minutes; failures are not cached, so the next
 * request tries again. Without coordinates, or when the API fails, it returns
 * the sample forecast the card has always shown.
 */
class FetchForecast
{
    public const URL = 'https://api.open-meteo.com/v1/forecast';

    public const CACHE_MINUTES = 15;

    /** Days of daily forecast fetched (today first); the voice assistant's weather tool uses all of them. */
    public const DAYS = 7;

    public function __construct(
        private readonly WeatherLocation $location,
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly LoggerInterface $log,
        private readonly ParseForecast $parseForecast,
        private readonly BuildWeatherSummary $buildWeatherSummary,
    ) {}

    /**
     * @return array{real: bool, temp: int, code: int, label: string, icon: string, high: int, low: int,
     *               hours: list<array{label: string, temp: int, pop: int|null, icon: string}>|null,
     *               precip: string|null, precip_icon: string|null, summary: string,
     *               days: list<array{date: string, high: int, low: int, code: int, label: string, rain_chance: int|null}>}
     */
    public function handle(): array
    {
        if (! $this->location->isSet()) {
            return $this->sample();
        }

        $key = sprintf('weather.forecast.%.3f.%.3f', $this->location->latitude, $this->location->longitude);

        if ($cached = $this->cache->get($key)) {
            return $cached;
        }

        try {
            $response = $this->http->timeout(6)->retry(1, 300, throw: false)->get(self::URL, [
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude,
                'current' => 'temperature_2m,weather_code,is_day',
                'hourly' => 'temperature_2m,precipitation_probability,weather_code',
                'daily' => 'temperature_2m_max,temperature_2m_min,weather_code,precipitation_probability_max',
                'timezone' => 'auto',
                'forecast_days' => self::DAYS,
            ]);

            if (! $response->successful()) {
                $this->log->warning('Open-Meteo request failed.', ['status' => $response->status()]);

                return $this->sample();
            }

            $forecast = $this->parseForecast->handle((array) $response->json());
        } catch (Throwable $e) {
            $this->log->warning('Open-Meteo request failed.', ['error' => $e->getMessage()]);

            return $this->sample();
        }

        $this->cache->put($key, $forecast, now()->addMinutes(self::CACHE_MINUTES));

        return $forecast;
    }

    /** The sample forecast shown until a location is set; the card fills in the hours from the tablet's clock. */
    private function sample(): array
    {
        $forecast = [
            'real' => false,
            'temp' => 4,
            'code' => 2,
            'label' => 'Partly cloudy',
            'icon' => 'partly',
            'high' => 6,
            'low' => -1,
            'hours' => null,
            'precip' => null,
            'precip_icon' => null,
            'days' => [],
        ];
        $forecast['summary'] = $this->buildWeatherSummary->handle($forecast);

        return $forecast;
    }
}
