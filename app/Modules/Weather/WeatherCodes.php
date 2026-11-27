<?php

namespace App\Modules\Weather;

/**
 * WMO weather interpretation codes, as returned by Open-Meteo in weather_code.
 * https://open-meteo.com/en/docs (section "WMO Weather interpretation codes")
 */
class WeatherCodes
{
    private const LABELS = [
        0 => 'Clear',
        1 => 'Mostly clear',
        2 => 'Partly cloudy',
        3 => 'Cloudy',
        45 => 'Fog', 48 => 'Fog',
        51 => 'Light drizzle', 53 => 'Drizzle', 55 => 'Heavy drizzle',
        56 => 'Freezing drizzle', 57 => 'Freezing drizzle',
        61 => 'Light rain', 63 => 'Rain', 65 => 'Heavy rain',
        66 => 'Freezing rain', 67 => 'Freezing rain',
        71 => 'Light snow', 73 => 'Snow', 75 => 'Heavy snow',
        77 => 'Snow grains',
        80 => 'Showers', 81 => 'Showers', 82 => 'Heavy showers',
        85 => 'Snow showers', 86 => 'Snow showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm', 99 => 'Thunderstorm',
    ];

    public static function label(int $code): string
    {
        return __(self::LABELS[$code] ?? 'Cloudy');
    }

    public static function isSnow(int $code): bool
    {
        return in_array($code, [71, 73, 75, 77, 85, 86], true);
    }

    /** Icon name for resources/views/modules/weather/icon.blade.php. */
    public static function icon(int $code, bool $isDay = true): string
    {
        return match (true) {
            $code === 0 => $isDay ? 'clear' : 'clear-night',
            in_array($code, [1, 2], true) => $isDay ? 'partly' : 'partly-night',
            $code === 3 => 'cloudy',
            in_array($code, [45, 48], true) => 'fog',
            self::isSnow($code) => 'snow',
            $code >= 95 => 'thunder',
            $code >= 51 && $code <= 82 => 'rain',
            default => 'cloudy',
        };
    }

    /** A character for plain-text places such as Fully's action bar title. */
    public static function emoji(?string $icon): string
    {
        return match ($icon) {
            'clear' => '☀️',
            'clear-night' => '🌙',
            'partly' => '⛅',
            'partly-night', 'cloudy' => '☁️',
            'fog' => '🌫️',
            'rain' => '🌧️',
            'snow' => '🌨️',
            'thunder' => '⛈️',
            default => '',
        };
    }
}
