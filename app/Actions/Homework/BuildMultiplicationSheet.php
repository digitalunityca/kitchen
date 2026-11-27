<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * A multiplication worksheet made on the hub (no AI): 9 problems, 3 across, each in
 * its own square grid (one digit per cell) with rows for the partial products and
 * the total. Sized to the kid's grade; the same every time for the same seed.
 */
class BuildMultiplicationSheet
{
    /** Square cells sized for a handwritten digit, and the space between grids. */
    public const CELL_MM = 8.0;

    public const GAP_MM = 8.0;

    /** Grade => [top number range, multiplier range]. */
    private const RANGES = [
        1 => [[2, 9], [2, 5]],
        2 => [[10, 99], [2, 9]],
        3 => [[10, 99], [2, 9]],
        4 => [[10, 99], [10, 99]],
        5 => [[100, 999], [10, 99]],
        6 => [[100, 999], [100, 999]],
    ];

    /**
     * The sheet's problems, as many grids as fit on the page. A level (exercise
     * variant) gives the number ranges; without one, the grade's ranges are used.
     *
     * @param  array{top?: array{int, int}, bottom?: array{int, int}}|null  $params
     * @return list<array{question: string, answer: string, top: int, bottom: int}>
     */
    public function handle(int $grade, int $seed, ?array $params = null): array
    {
        [$top, $bottom] = isset($params['top'], $params['bottom']) ? [$params['top'], $params['bottom']] : self::RANGES[max(1, min(6, $grade))];
        $size = self::grid(strlen((string) $top[1]), strlen((string) $bottom[1]), strlen((string) ($top[1] * $bottom[1])));
        $random = new Randomizer(new Mt19937($seed));
        $problems = [];

        for ($i = 0; $i < $size['across'] * $size['down']; $i++) {
            $a = $random->getInt(...$top);
            $b = $random->getInt(...$bottom);
            $problems[] = ['question' => "{$a} × {$b}", 'answer' => (string) ($a * $b), 'top' => $a, 'bottom' => $b];
        }

        return $problems;
    }

    /**
     * One grid and how many fit on the page. Columns: the longest product (or the
     * numbers, or the multiplier and its × sign) plus one spare. Rows, top to
     * bottom: carries (one row per multiplier digit, at most 2), the number, × and
     * the multiplier, then the result row for a 1-digit multiplier; otherwise one
     * partial product per multiplier digit, a carry row for the sum and the total.
     *
     * @return array{columns: int, partials: int, total: bool, carries: int, sumCarry: bool, height: int, cell: float, gap: float, across: int, down: int}
     */
    public static function grid(int $topDigits, int $multiplierDigits, int $productDigits): array
    {
        $columns = max($productDigits, $topDigits, $multiplierDigits + 1) + 1;
        $total = $multiplierDigits > 1;
        $carries = min(2, $multiplierDigits);
        $height = $carries + 2 + $multiplierDigits + ($total ? 2 : 0);
        $cell = self::CELL_MM * SheetPage::MM;
        $gap = self::GAP_MM * SheetPage::MM;
        $fit = SheetPage::fit($columns * $cell, $height * $cell, $gap, $gap, 6, 8);

        return [
            'columns' => $columns, 'partials' => $multiplierDigits, 'total' => $total, 'carries' => $carries, 'sumCarry' => $total,
            'height' => $height, 'cell' => round($cell, 2), 'gap' => round($gap, 2), 'across' => $fit['columns'], 'down' => $fit['rows'],
        ];
    }

    /**
     * The grid for a sheet, sized from its problems. A sheet with more problems
     * than fit (made before layouts were sized) gets smaller cells, never two pages.
     *
     * @param  list<array{top: int, bottom: int}>  $problems
     * @return array{columns: int, partials: int, total: bool, carries: int, sumCarry: bool, height: int, cell: float, gap: float, across: int, down: int, rows: int, title: string}
     */
    public static function layout(array $problems): array
    {
        $product = max(array_map(fn (array $p): int => strlen((string) ($p['top'] * $p['bottom'])), $problems));
        $topDigits = max(array_map(fn (array $p): int => strlen((string) $p['top']), $problems));
        $partials = max(array_map(fn (array $p): int => strlen((string) $p['bottom']), $problems));
        $grid = self::grid($topDigits, $partials, $product);
        while (count($problems) > $grid['across'] * $grid['down'] && $grid['cell'] > 12) {
            $grid['cell'] = round($grid['cell'] * .92, 2);
            $fit = SheetPage::fit($grid['columns'] * $grid['cell'], $grid['height'] * $grid['cell'], $grid['gap'], $grid['gap'], 6, 8);
            [$grid['across'], $grid['down']] = [$fit['columns'], $fit['rows']];
        }

        $tables = max(array_map(fn (array $p): int => max($p['top'], $p['bottom']), $problems)) <= 10;
        $digits = fn (int $n): string => $n.' '.($n === 1 ? 'chiffre' : 'chiffres');

        return $grid + [
            'rows' => (int) ceil(count($problems) / $grid['across']),
            'title' => $tables ? 'Tables de multiplication' : 'Multiplications : '.$digits($topDigits).' × '.$digits($partials),
        ];
    }
}
