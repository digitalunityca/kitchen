<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/** Times as people read them in the dashboard's language: "17 h 45" / "17 h" in French, "5:45 PM" / "5 PM" in English. */
final class TimeFormat
{
    public static function french(): bool
    {
        return str_starts_with(app()->getLocale(), 'fr');
    }

    /** "17 h 45", "5:45 PM". With $short, whole hours drop the minutes ("17 h", "5 PM"). */
    public static function hm(CarbonInterface $time, bool $short = false): string
    {
        if (self::french()) {
            return $time->hour.' h'.($time->minute || ! $short ? ($time->minute ? ' '.$time->format('i') : '') : '');
        }

        $text = $time->format('g:i A');

        return $short ? str_replace(':00', '', $text) : $text;
    }

    /** An hour of the day: "15 h", "3 PM". */
    public static function hour(int $hour): string
    {
        return self::french() ? $hour.' h' : ($hour % 12 ?: 12).($hour < 12 ? ' AM' : ' PM');
    }
}
