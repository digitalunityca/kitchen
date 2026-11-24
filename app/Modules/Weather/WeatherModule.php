<?php

namespace App\Modules\Weather;

use App\Dashboard\Module;
use App\Dashboard\ModuleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;

/**
 * Current conditions, today's high/low and the next 4 hours from Open-Meteo
 * (WeatherService). Set WEATHER_LAT and WEATHER_LON in .env; until then the
 * card shows sample data with a "Sample data" pill. The card refreshes itself
 * from /modules/weather/now every 15 minutes and, in Fully Kiosk, puts a
 * one-line summary in the action bar title.
 */
class WeatherModule extends Module
{
    public function key(): string
    {
        return 'weather';
    }

    public function name(): string
    {
        return 'Weather';
    }

    public function service(): WeatherService
    {
        $coord = fn (string $key) => is_numeric($v = $this->config($key)) ? (float) $v : null;

        return new WeatherService($coord('lat'), $coord('lon'));
    }

    public function data(): array
    {
        return [
            'city' => $this->config('city'),
            'weather' => $this->service()->forecast(),
        ];
    }

    public function routes(Router $router): void
    {
        $router->get('now', fn (ModuleRegistry $modules) => $modules->get($this->key())->json())->name('now');
    }

    public function json(): JsonResponse
    {
        $weather = $this->service()->forecast();
        $weather['icon_svg'] = view('modules.weather.icon', ['icon' => $weather['icon'], 'size' => 72])->render();
        $weather['hours'] = $weather['hours'] === null ? null : array_map(
            fn ($h) => $h + ['icon_svg' => view('modules.weather.icon', ['icon' => $h['icon'], 'size' => 22])->render()],
            $weather['hours'],
        );

        return response()->json($weather)->header('Cache-Control', 'no-store');
    }
}
