<?php

declare(strict_types=1);

namespace App\Modules\Weather;

use App\Support\TimeFormat;

/** Text formatting shared by the weather card, its summary line and the precipitation note. */
final class WeatherFormat
{
    /** 7 → "7°", -1 → "−1°" (a real minus sign). */
    public static function deg(int $temperature): string
    {
        return ($temperature < 0 ? '−'.abs($temperature) : (string) $temperature).'°';
    }

    /** "2026-11-24T15:00" → "3 PM" ("15 h" in French). */
    public static function hourLabel(string $time): string
    {
        return TimeFormat::hour((int) substr($time, 11, 2));
    }

    /** "…T15:00", "…T18:00" → "3–6 PM"; "…T11:00", "…T14:00" → "11 AM–2 PM". */
    public static function hourRange(string $from, string $to): string
    {
        $start = self::hourLabel($from);
        $end = self::hourLabel($to);
        if (TimeFormat::french()) {
            return str_replace(' h', '', $start).'–'.$end; // "15–18 h"
        }

        return substr($start, -2) === substr($end, -2) ? substr($start, 0, -3).'–'.$end : $start.'–'.$end;
    }
}
