<?php

declare(strict_types=1);

namespace App\Actions\Weather;

use Illuminate\Contracts\View\Factory as ViewFactory;

/** The forecast for /modules/weather/now, with each icon pre-rendered as SVG so the card can swap them in. */
class BuildWeatherPayload
{
    public function __construct(
        private readonly FetchForecast $fetchForecast,
        private readonly ViewFactory $view,
    ) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $weather = $this->fetchForecast->handle();
        $weather['icon_svg'] = $this->icon($weather['icon'], 72);
        $weather['hours'] = $weather['hours'] === null ? null : array_map(
            fn (array $hour): array => $hour + ['icon_svg' => $this->icon($hour['icon'], 22)],
            $weather['hours'],
        );

        return $weather;
    }

    private function icon(string $icon, int $size): string
    {
        return $this->view->make('modules.weather.icon', ['icon' => $icon, 'size' => $size])->render();
    }
}
