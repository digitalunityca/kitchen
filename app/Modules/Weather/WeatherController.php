<?php

declare(strict_types=1);

namespace App\Modules\Weather;

use App\Actions\Weather\BuildWeatherPayload;
use Illuminate\Http\JsonResponse;

/** GET /modules/weather/now. A controller rather than a closure, so `php artisan optimize` can cache the route. */
class WeatherController
{
    public function __invoke(BuildWeatherPayload $buildWeatherPayload): JsonResponse
    {
        return response()->json($buildWeatherPayload->handle())->header('Cache-Control', 'no-store');
    }
}
