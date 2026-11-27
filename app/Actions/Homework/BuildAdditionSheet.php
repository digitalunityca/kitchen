<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * An addition worksheet made on the hub (no AI): 5 columns of stacked additions,
 * 4 rows, sized to the kid's grade, the same every time for the same seed.
 */
class BuildAdditionSheet
{
    /** Room for handwriting: one digit 7 mm wide, one written row 9.5 mm high, a carry row of 6 mm above. */
    public const DIGIT_MM = 7.0;

    public const ROW_MM = 9.5;

    public const CARRY_MM = 6.0;

    /** Space between problems, across and down. */
    public const GAP_X_MM = 8.0;

    public const GAP_Y_MM = 7.0;

    /** Grade => [top number range, bottom number range]. */
    private const RANGES = [
        1 => [[1, 9], [1, 9]],
        2 => [[10, 99], [10, 99]],
        3 => [[100, 999], [10, 99]],
        4 => [[100, 999], [100, 999]],
        5 => [[1000, 9999], [100, 999]],
        6 => [[1000, 9999], [1000, 9999]],
    ];

    /**
     * The sheet's problems, as many as fit on the page at handwriting size. A level
     * (exercise variant) gives the number ranges and, optionally, carry (true: at
     * least one carry, false: none), sum_max and addends (2 or 3 numbers to add);
     * without one, the grade's ranges are used as before.
     *
     * @param  array{top?: array{int, int}, bottom?: array{int, int}, carry?: bool, sum_max?: int, addends?: int}|null  $params
     * @return list<array{question: string, answer: string, top: int, bottom: int, terms?: list<int>}>
     */
    public function handle(int $grade, int $seed, ?array $params = null): array
    {
        [$top, $bottom] = isset($params['top'], $params['bottom']) ? [$params['top'], $params['bottom']] : self::RANGES[max(1, min(6, $grade))];
        $carry = $params['carry'] ?? null;
        $max = $params['sum_max'] ?? PHP_INT_MAX;
        $addends = max(2, min(4, (int) ($params['addends'] ?? 2)));

        // The biggest problem this level can make decides the cell size, hence how many fit.
        $sumDigits = strlen((string) min($max, $top[1] + $bottom[1] * ($addends - 1)));
        $grid = self::grid(max($sumDigits, 2 + strlen((string) max($top[1], $bottom[1]))), $addends);

        $random = new Randomizer(new Mt19937($seed));
        $problems = [];
        for ($i = 0; $i < $grid['columns'] * $grid['rows']; $i++) {
            // Draw until the numbers fit the level (a few tries at most for these ranges).
            for ($try = 0; $try < 200; $try++) {
                $terms = [$random->getInt(...$top)];
                for ($n = 1; $n < $addends; $n++) {
                    $terms[] = $random->getInt(...$bottom);
                }
                if (array_sum($terms) <= $max && ($carry === null || self::carries(...$terms) === $carry)) {
                    break;
                }
            }
            $problem = ['question' => implode(' + ', $terms), 'answer' => (string) array_sum($terms), 'top' => $terms[0], 'bottom' => $terms[count($terms) - 1]];
            $problems[] = $addends > 2 ? $problem + ['terms' => $terms] : $problem;
        }

        return $problems;
    }

    /** Does adding these numbers carry in any column? */
    public static function carries(int ...$numbers): bool
    {
        $carry = 0;
        while (array_filter($numbers) !== [] || $carry > 0) {
            $column = $carry;
            foreach ($numbers as $i => $n) {
                $column += $n % 10;
                $numbers[$i] = intdiv($n, 10);
            }
            if ($column >= 10) {
                return true;
            }
            $carry = 0;
        }

        return false;
    }

    /**
     * Cell size and how many problems fit on the page. A problem is a small grid:
     * one column per character (the + sign and every digit, answer included), a
     * carry row, one row per number, and the answer row under the line.
     *
     * @return array{columns: int, rows: int, chars: int, digit: float, row: float, carry: float, gapX: float, gapY: float, font: float}
     */
    public static function grid(int $chars, int $addends, float $scale = 1.0): array
    {
        $digit = self::DIGIT_MM * SheetPage::MM * $scale;
        $row = self::ROW_MM * SheetPage::MM * $scale;
        $carry = self::CARRY_MM * SheetPage::MM * $scale;
        $gapX = self::GAP_X_MM * SheetPage::MM;
        $gapY = self::GAP_Y_MM * SheetPage::MM;
        $fit = SheetPage::fit($chars * $digit, $carry + ($addends + 1) * $row, $gapX, $gapY);

        return $fit + ['chars' => $chars, 'digit' => round($digit, 2), 'row' => round($row, 2), 'carry' => round($carry, 2), 'gapX' => round($gapX, 2), 'gapY' => round($gapY, 2), 'font' => round($row * .7, 1)];
    }

    /**
     * How to draw a sheet so every number lines up by place value: the width in
     * characters comes from the longest line (the sum, or "+" and the longest
     * number), then the grid above. A sheet with more problems than fit at
     * handwriting size (made before layouts were sized) is drawn a little smaller,
     * never over two pages.
     *
     * @param  list<array{top: int, bottom: int, terms?: list<int>}>  $problems
     * @return array{chars: int, font: float, rows: int, columns: int, digit: float, row: float, carry: float, gapX: float, gapY: float, addends: int, digits: int, title: string}
     */
    public static function layout(array $problems): array
    {
        $terms = array_map(fn (array $p): array => $p['terms'] ?? [$p['top'], $p['bottom']], $problems);
        $digits = max(array_map(fn (array $t): int => max(array_map(fn (int $n): int => strlen((string) $n), $t)), $terms));
        $fewest = min(array_map(fn (array $t): int => min(array_map(fn (int $n): int => strlen((string) $n), $t)), $terms));
        $sum = max(array_map(fn (array $t): int => strlen((string) array_sum($t)), $terms));
        $addends = max(array_map('count', $terms));
        $chars = max($sum, 2 + $digits);

        $scale = 1.0;
        do {
            $grid = self::grid($chars, $addends, $scale);
            $scale -= .05;
        } while (count($problems) > $grid['columns'] * $grid['rows'] && $scale >= .5);

        $size = $digits === 1 ? '1 chiffre' : ($fewest < $digits ? "{$fewest} et {$digits} chiffres" : "{$digits} chiffres");

        return [
            'rows' => (int) ceil(count($problems) / $grid['columns']),
            'addends' => $addends,
            'digits' => $digits,
            'title' => $addends > 2 ? "Additions de {$addends} nombres : {$size}" : "Additions : {$size}",
        ] + $grid;
    }
}
