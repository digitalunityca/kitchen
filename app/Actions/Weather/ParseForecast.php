<?php

declare(strict_types=1);

namespace App\Actions\Weather;

use App\Modules\Weather\WeatherCodes;
use App\Modules\Weather\WeatherFormat;

/** Turn an Open-Meteo response into what the card shows. Times are the location's local time. */
class ParseForecast
{
    public function __construct(
        private readonly DescribePrecipitation $describePrecipitation,
        private readonly BuildWeatherSummary $buildWeatherSummary,
    ) {}

    /**
     * @param  array<string, mixed>  $data  the Open-Meteo JSON body
     * @return array{real: bool, temp: int, code: int, label: string, icon: string, high: int, low: int,
     *               hours: list<array{label: string, temp: int, pop: int|null, icon: string}>,
     *               precip: string|null, precip_icon: string|null, summary: string}
     */
    public function handle(array $data): array
    {
        $now = (string) data_get($data, 'current.time'); // e.g. 2026-11-24T13:45
        $today = substr($now, 0, 10);
        $thisHour = substr($now, 0, 13).':00';
        $isDay = (bool) data_get($data, 'current.is_day', 1);
        $code = (int) data_get($data, 'current.weather_code');

        $times = (array) data_get($data, 'hourly.time', []);
        $temperatures = (array) data_get($data, 'hourly.temperature_2m', []);
        $probabilities = (array) data_get($data, 'hourly.precipitation_probability', []);
        $codes = (array) data_get($data, 'hourly.weather_code', []);

        $hours = [];
        $restOfToday = [];

        foreach ($times as $i => $time) {
            if ($time < $thisHour) {
                continue;
            }

            $probability = isset($probabilities[$i]) ? (int) $probabilities[$i] : null;
            $hourCode = (int) ($codes[$i] ?? 0);

            if (str_starts_with($time, $today)) {
                $restOfToday[] = [$time, $probability, $hourCode];
            }

            if ($time > $thisHour && count($hours) < 4) {
                $hour = (int) substr($time, 11, 2);
                $hours[] = [
                    'label' => WeatherFormat::hourLabel($time),
                    'temp' => (int) round((float) ($temperatures[$i] ?? 0)),
                    'pop' => $probability,
                    'icon' => WeatherCodes::icon($hourCode, $hour >= 7 && $hour < 19),
                ];
            }
        }

        [$precipitation, $precipitationIcon] = $this->describePrecipitation->handle($restOfToday, $thisHour);

        $forecast = [
            'real' => true,
            'temp' => (int) round((float) data_get($data, 'current.temperature_2m')),
            'code' => $code,
            'label' => WeatherCodes::label($code),
            'icon' => WeatherCodes::icon($code, $isDay),
            'high' => (int) round((float) data_get($data, 'daily.temperature_2m_max.0')),
            'low' => (int) round((float) data_get($data, 'daily.temperature_2m_min.0')),
            'hours' => $hours,
            'precip' => $precipitation,
            'precip_icon' => $precipitationIcon,
        ];
        $forecast['summary'] = $this->buildWeatherSummary->handle($forecast);

        return $forecast;
    }
}
