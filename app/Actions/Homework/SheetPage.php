<?php

declare(strict_types=1);

namespace App\Actions\Homework;

/**
 * The printed page (Letter, the Dompdf print sheet) in points: the area left for
 * the problems under the header, and how many problems of a given size fit in it.
 */
final class SheetPage
{
    /** 1 mm in points. */
    public const MM = 2.8346;

    /** Letter 612 × 792 pt, minus the 40 pt side margins. */
    public const WIDTH = 532.0;

    /** What Dompdf leaves under the header on one Letter page (measured: 619 pt), minus a small safety margin. */
    public const HEIGHT = 612.0;

    /** Borders and rounding make printed rows a little taller than their cells. */
    public const BORDER_ALLOWANCE = 1.03;

    /**
     * Columns and rows of problems that fit, gaps included.
     *
     * @return array{columns: int, rows: int}
     */
    public static function fit(float $width, float $height, float $gapX, float $gapY, int $maxColumns = 8, int $maxRows = 10): array
    {
        return [
            'columns' => max(1, min($maxColumns, (int) floor((self::WIDTH + $gapX) / ($width + $gapX)))),
            'rows' => max(1, min($maxRows, (int) floor((self::HEIGHT + $gapY) / ($height * self::BORDER_ALLOWANCE + $gapY)))),
        ];
    }
}
