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
     * @return array{real: bool, temp: int, code: int, label: string, icon: string, precip_icon: string|null,
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
        $rest = []; // [time, pop, code] for the rest of today, from this hour on
        foreach ($times as $i => $time) {
            if ($time < $thisHour) {
                continue;
            }

            $pop = isset($pops[$i]) ? (int) $pops[$i] : null;
            if (str_starts_with($time, $today)) {
                $rest[] = [$time, $pop, (int) ($codes[$i] ?? 0)];
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
        [$precip, $precipIcon] = self::precipitation($rest, $thisHour);

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
            'precip_icon' => $precipIcon,
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
            'precip_icon' => null,
        ];
        $forecast['summary'] = self::summary($forecast);

        return $forecast;
    }

    /**
     * When rain or snow is likely today (hourly probability >= LIKELY), as text
     * and an icon name: "Rain likely 3–6 PM", "Snow likely until 5 PM",
     * "Rain likely from 9 PM" (still likely at midnight). The first likely
     * stretch counts; it ends at the first hour that drops below LIKELY.
     *
     * @param  list<array{0: string, 1: int|null, 2: int}>  $hours  [time, probability, code], this hour first
     * @return array{0: string|null, 1: string|null}
     */
    public static function precipitation(array $hours, string $thisHour): array
    {
        $start = $end = null;
        $word = 'Rain';
        foreach ($hours as [$time, $pop, $code]) {
            $likely = $pop !== null && $pop >= self::LIKELY;
            if ($start === null && $likely) {
                $start = $time;
                $word = WeatherCodes::isSnow($code) ? 'Snow' : 'Rain';
            } elseif ($start !== null && ! $likely) {
                $end = $time;
                break;
            }
        }

        if ($start === null) {
            return [null, null];
        }

        $icon = strtolower($word);
        if ($start === $thisHour) {
            return [$end ? "$word likely until ".self::hourLabel($end) : "$word likely today", $icon];
        }
        if (! $end) {
            return ["$word likely from ".self::hourLabel($start), $icon];
        }

        return ["$word likely ".self::hourRange($start, $end), $icon];
    }

    /**
     * One line for Fully's action bar, e.g.
     * "⛅ 4° Partly cloudy · H 6° L −1° · 🌧 Rain likely 3–6 PM".
     */
    public static function summary(array $f): string
    {
        $line = WeatherCodes::emoji($f['icon']).' '.self::deg($f['temp']).' '.$f['label']
            .' · H '.self::deg($f['high']).' L '.self::deg($f['low']);

        return $f['precip'] ? $line.' · '.WeatherCodes::emoji($f['precip_icon']).' '.$f['precip'] : $line;
    }

    /** 7 → "7°", -1 → "−1°" (a real minus sign). */
    public static function deg(int $t): string
    {
        return ($t < 0 ? '−'.abs($t) : (string) $t).'°';
    }

    /** "…T15:00", "…T18:00" → "3–6 PM"; "…T11:00", "…T14:00" → "11 AM–2 PM". */
    public static function hourRange(string $from, string $to): string
    {
        $a = self::hourLabel($from);
        $b = self::hourLabel($to);

        return substr($a, -2) === substr($b, -2) ? substr($a, 0, -3).'–'.$b : $a.'–'.$b;
    }

    /** "2026-11-24T15:00" → "3 PM". */
    public static function hourLabel(string $time): string
    {
        $h = (int) substr($time, 11, 2);

        return ($h % 12 ?: 12).($h < 12 ? ' AM' : ' PM');
    }
}
