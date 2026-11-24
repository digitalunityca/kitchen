<?php

declare(strict_types=1);

namespace App\Actions\AiChat;

use App\Actions\Weather\FetchForecast;
use App\Modules\AiChat\RealtimeSettings;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * What the voice assistant's get_weather tool returns: current conditions and
 * the 7-day forecast from the same Open-Meteo data (and cache) as the weather
 * card. Each day carries its weekday and a today/tomorrow tag so the model can
 * answer "tomorrow", "Saturday" or "samedi" without date maths.
 */
class BuildWeatherToolResult
{
    public function __construct(private readonly RealtimeSettings $settings) {}

    /** @return array<string, mixed> */
    public function handle(?string $requestedDay = null): array
    {
        try {
            $forecast = app(FetchForecast::class)->handle();
        } catch (Throwable) {
            $forecast = ['real' => false];
        }

        if (! ($forecast['real'] ?? false)) {
            return ['available' => false, 'note' => 'The weather forecast is not available right now. Say so briefly.'];
        }

        $today = (string) ($forecast['days'][0]['date'] ?? now()->toDateString());

        return [
            'available' => true,
            'location' => $this->settings->location,
            'units' => 'degrees Celsius',
            'requested_day' => $requestedDay,
            'now' => ['temperature' => $forecast['temp'], 'conditions' => $forecast['label'], 'rain_today' => $forecast['precip']],
            'days' => array_map(function (array $day) use ($today): array {
                $date = CarbonImmutable::parse($day['date']);
                $offset = (int) CarbonImmutable::parse($today)->diffInDays($date);

                return [
                    'date' => $day['date'],
                    'weekday' => $date->englishDayOfWeek,
                    'relative' => match ($offset) {
                        0 => 'today',
                        1 => 'tomorrow',
                        default => "in {$offset} days",
                    },
                    'high' => $day['high'],
                    'low' => $day['low'],
                    'conditions' => $day['label'],
                    'chance_of_precipitation_percent' => $day['rain_chance'],
                ];
            }, $forecast['days'] ?? []),
        ];
    }
}
