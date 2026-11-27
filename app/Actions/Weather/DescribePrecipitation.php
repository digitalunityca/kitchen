<?php

declare(strict_types=1);

namespace App\Actions\Weather;

use App\Modules\Weather\WeatherCodes;
use App\Modules\Weather\WeatherFormat;

/**
 * When rain or snow is likely today (hourly probability >= LIKELY), as text
 * and an icon name: "Rain likely 3–6 PM", "Snow likely until 5 PM",
 * "Rain likely from 9 PM" (still likely at midnight). The first likely
 * stretch counts; it ends at the first hour that drops below LIKELY.
 */
class DescribePrecipitation
{
    /** Hourly precipitation probability (%) that counts as "likely". */
    public const LIKELY = 50;

    /**
     * @param  list<array{0: string, 1: int|null, 2: int}>  $hours  [time, probability, code], this hour first
     * @return array{0: string|null, 1: string|null} [text, icon]
     */
    public function handle(array $hours, string $thisHour): array
    {
        $start = $end = null;
        $word = 'Rain';

        foreach ($hours as [$time, $probability, $code]) {
            $likely = $probability !== null && $probability >= self::LIKELY;

            if ($start === null && $likely) {
                $start = $time;
                $word = WeatherCodes::isSnow($code) ? 'Snow' : 'Rain';
            } elseif ($start !== null && ! $likely) {
                $end = $time;
                break;
            }
        }

        if ($start === null) {
            return [null, null];
        }

        $icon = strtolower($word);

        if ($start === $thisHour) {
            return [$end ? __(':word likely until :time', ['word' => __($word), 'time' => WeatherFormat::hourLabel($end)]) : __(':word likely today', ['word' => __($word)]), $icon];
        }

        if (! $end) {
            return [__(':word likely from :time', ['word' => __($word), 'time' => WeatherFormat::hourLabel($start)]), $icon];
        }

        return [__(':word likely :range', ['word' => __($word), 'range' => WeatherFormat::hourRange($start, $end)]), $icon];
    }
}
