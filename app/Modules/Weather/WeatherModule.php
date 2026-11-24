<?php

namespace App\Modules\Weather;

use App\Actions\Weather\FetchForecast;
use App\Dashboard\Module;
use App\Dashboard\ModuleRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;

/**
 * Current conditions, today's high/low and the next 4 hours from Open-Meteo
 * (App\Actions\Weather\FetchForecast). Set WEATHER_LAT and WEATHER_LON in .env;
 * until then the card shows sample data with a "Sample data" pill. The card
 * refreshes itself from /modules/weather/now every 15 minutes and, in Fully
 * Kiosk, puts a one-line summary in the action bar title.
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

    public function register(Container $app): void
    {
        $app->bind(WeatherLocation::class, function (Container $app): WeatherLocation {
            $module = $app->make(ModuleRegistry::class)->get($this->key());

            return WeatherLocation::fromConfig($module?->config('lat'), $module?->config('lon'));
        });
    }

    /** @return array{city: mixed, weather: array<string, mixed>} */
    public function data(): array
    {
        return [
            'city' => $this->config('city'),
            'weather' => app(FetchForecast::class)->handle(),
        ];
    }

    public function routes(Router $router): void
    {
        $router->get('now', WeatherController::class)->name('now');
    }
}
