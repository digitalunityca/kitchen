<?php

declare(strict_types=1);

namespace App\Modules\Weather;

/** Where to fetch the forecast for, from the weather module's config. Without both coordinates the card shows sample data. */
final class WeatherLocation
{
    public function __construct(
        public readonly ?float $latitude,
        public readonly ?float $longitude,
    ) {}

    /** Accepts the raw config values; anything that isn't a number counts as unset. */
    public static function fromConfig(mixed $latitude, mixed $longitude): self
    {
        return new self(
            is_numeric($latitude) ? (float) $latitude : null,
            is_numeric($longitude) ? (float) $longitude : null,
        );
    }

    public function isSet(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
