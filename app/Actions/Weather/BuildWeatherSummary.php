<?php

declare(strict_types=1);

namespace App\Actions\Weather;

use App\Modules\Weather\WeatherCodes;
use App\Modules\Weather\WeatherFormat;

/** One line for Fully's action bar, e.g. "⛅ 4° Partly cloudy · H 6° L −1° · 🌧️ Rain likely 3–6 PM". */
class BuildWeatherSummary
{
    /** @param  array{temp: int, label: string, icon: string, high: int, low: int, precip: string|null, precip_icon: string|null}  $forecast */
    public function handle(array $forecast): string
    {
        $line = WeatherCodes::emoji($forecast['icon']).' '.WeatherFormat::deg($forecast['temp']).' '.$forecast['label']
            .' · H '.WeatherFormat::deg($forecast['high']).' L '.WeatherFormat::deg($forecast['low']);

        if (! $forecast['precip']) {
            return $line;
        }

        return $line.' · '.WeatherCodes::emoji($forecast['precip_icon']).' '.$forecast['precip'];
    }
}
