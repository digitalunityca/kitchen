<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Weather\WeatherCodes;
use App\Modules\Weather\WeatherFormat;
use Tests\TestCase;

class WeatherFormatAndCodesTest extends TestCase
{
    public function test_degrees_use_a_real_minus_sign(): void
    {
        $this->assertSame('7°', WeatherFormat::deg(7));
        $this->assertSame('0°', WeatherFormat::deg(0));
        $this->assertSame('−12°', WeatherFormat::deg(-12));
    }

    public function test_hour_labels_and_ranges(): void
    {
        $this->assertSame('12 AM', WeatherFormat::hourLabel('2026-11-24T00:00'));
        $this->assertSame('12 PM', WeatherFormat::hourLabel('2026-11-24T12:00'));
        $this->assertSame('3–6 PM', WeatherFormat::hourRange('2026-11-24T15:00', '2026-11-24T18:00'));
        $this->assertSame('11 AM–2 PM', WeatherFormat::hourRange('2026-11-24T11:00', '2026-11-24T14:00'));
    }

    public function test_codes_map_to_labels_and_day_or_night_icons(): void
    {
        $this->assertSame('Clear', WeatherCodes::label(0));
        $this->assertSame('Cloudy', WeatherCodes::label(1234));
        $this->assertSame('clear-night', WeatherCodes::icon(0, false));
        $this->assertSame('partly-night', WeatherCodes::icon(2, false));
        $this->assertSame('fog', WeatherCodes::icon(48));
        $this->assertSame('snow', WeatherCodes::icon(85));
        $this->assertSame('rain', WeatherCodes::icon(80));
        $this->assertSame('thunder', WeatherCodes::icon(99));
        $this->assertTrue(WeatherCodes::isSnow(77));
        $this->assertFalse(WeatherCodes::isSnow(61));
        $this->assertSame('', WeatherCodes::emoji(null));
    }
}
