<?php

namespace App\Modules\Weather;

use App\Dashboard\ModuleRegistry;
use Illuminate\Http\JsonResponse;

/** GET /modules/weather/now. A controller rather than a closure, so `php artisan optimize` can cache the route. */
class WeatherController
{
    public function __invoke(ModuleRegistry $modules): JsonResponse
    {
        return $modules->get('weather')->json();
    }
}
