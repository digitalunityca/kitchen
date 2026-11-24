<?php

namespace App\Modules\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Current conditions and today's forecast from Open-Meteo (free, no API key).
 * Results are cached for 15 minutes. Without coordinates, or when the API
 * fails, it returns the sample forecast the card has always shown.
 */
class WeatherService
{
    public const URL = 'https://api.open-meteo.com/v1/forecast';

    public const CACHE_MINUTES = 15;

    /** Hourly precipitation probability (%) that counts as "likely". */
    public const LIKELY = 50;

    public function __construct(
        private readonly ?float $lat,
        private readonly ?float $lon,
    ) {}

    /**
     * @return array{real: bool, temp: int, code: int, label: string, icon: string,
     *               high: int, low: int, hours: list<array{label: string, temp: int, pop: int|null, icon: string}>|null,
     *               precip: string|null, summary: string}
     */
    public function forecast(): array
    {
        if ($this->lat === null || $this->lon === null) {
            return self::sample();
        }

        $key = sprintf('weather.forecast.%.3f.%.3f', $this->lat, $this->lon);
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        try {
            $response = Http::timeout(6)->retry(1, 300, throw: false)->get(self::URL, [
                'latitude' => $this->lat,
                'longitude' => $this->lon,
                'current' => 'temperature_2m,weather_code,is_day',
                'hourly' => 'temperature_2m,precipitation_probability,weather_code',
                'daily' => 'temperature_2m_max,temperature_2m_min',
                'timezone' => 'auto',
                'forecast_days' => 2,
            ]);

            if (! $response->successful()) {
                Log::warning('Open-Meteo request failed.', ['status' => $response->status()]);

                return self::sample();
            }

            $forecast = self::parse($response->json());
        } catch (Throwable $e) {
            Log::warning('Open-Meteo request failed.', ['error' => $e->getMessage()]);

            return self::sample();
        }

        Cache::put($key, $forecast, now()->addMinutes(self::CACHE_MINUTES));

        return $forecast;
    }

    /** Turn an Open-Meteo response into what the card shows. Times are the location's local time. */
    public static function parse(array $data): array
    {
        $now = (string) data_get($data, 'current.time'); // e.g. 2026-11-24T13:45
        $today = substr($now, 0, 10);
        $thisHour = substr($now, 0, 13).':00';
        $isDay = (bool) data_get($data, 'current.is_day', 1);
        $code = (int) data_get($data, 'current.weather_code');

        $times = (array) data_get($data, 'hourly.time', []);
        $temps = (array) data_get($data, 'hourly.temperature_2m', []);
        $pops = (array) data_get($data, 'hourly.precipitation_probability', []);
        $codes = (array) data_get($data, 'hourly.weather_code', []);

        $hours = [];
        $precip = null;
        foreach ($times as $i => $time) {
            if ($time < $thisHour) {
                continue;
            }

            $pop = isset($pops[$i]) ? (int) $pops[$i] : null;
            if ($precip === null && str_starts_with($time, $today) && $pop !== null && $pop >= self::LIKELY) {
                $word = WeatherCodes::isSnow((int) ($codes[$i] ?? 0)) ? 'Snow' : 'Rain';
                $precip = $time === $thisHour ? "$word likely now" : "$word likely from ".self::hourLabel($time);
            }

            if ($time > $thisHour && count($hours) < 4) {
                $hourCode = (int) ($codes[$i] ?? 0);
                $hour = (int) substr($time, 11, 2);
                $hours[] = [
                    'label' => self::hourLabel($time),
                    'temp' => (int) round((float) ($temps[$i] ?? 0)),
                    'pop' => $pop,
                    'icon' => WeatherCodes::icon($hourCode, $hour >= 7 && $hour < 19),
                ];
            }
        }

        $forecast = [
            'real' => true,
            'temp' => (int) round((float) data_get($data, 'current.temperature_2m')),
            'code' => $code,
            'label' => WeatherCodes::label($code),
            'icon' => WeatherCodes::icon($code, $isDay),
            'high' => (int) round((float) data_get($data, 'daily.temperature_2m_max.0')),
            'low' => (int) round((float) data_get($data, 'daily.temperature_2m_min.0')),
            'hours' => $hours,
            'precip' => $precip,
        ];
        $forecast['summary'] = self::summary($forecast);

        return $forecast;
    }

    /** The sample forecast shown until a location is set. */
    public static function sample(): array
    {
        $forecast = [
            'real' => false,
            'temp' => 4,
            'code' => 2,
            'label' => 'Partly cloudy',
            'icon' => 'partly',
            'high' => 6,
            'low' => -1,
            'hours' => null, // the card fills in the next 4 hours from the tablet's clock
            'precip' => null,
        ];
        $forecast['summary'] = self::summary($forecast);

        return $forecast;
    }

    /** One line for Fully's action bar, e.g. "4° Partly cloudy · H 6° L −1° · Rain likely from 3 PM". */
    public static function summary(array $f): string
    {
        $line = self::deg($f['temp']).' '.$f['label'].' · H '.self::deg($f['high']).' L '.self::deg($f['low']);

        return $f['precip'] ? $line.' · '.$f['precip'] : $line;
    }

    /** 7 → "7°", -1 → "−1°" (a real minus sign). */
    public static function deg(int $t): string
    {
        return ($t < 0 ? '−'.abs($t) : (string) $t).'°';
    }

    /** "2026-11-24T15:00" → "3 PM". */
    public static function hourLabel(string $time): string
    {
        $h = (int) substr($time, 11, 2);

        return ($h % 12 ?: 12).($h < 12 ? ' AM' : ' PM');
    }
}
