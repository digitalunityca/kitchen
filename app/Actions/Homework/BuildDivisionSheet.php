<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Division sheets made on the hub (levels with 'build' in their params), as many
 * as fit on one Letter page, in square 8 mm cells:
 * - facts: "56 ÷ 8 = ▢" lines for the table facts;
 * - long: the Québec "crochet" layout: the dividend on the left, the divisor
 *   behind the bracket with the quotient under it, and under the dividend two
 *   rows per quotient digit (the product to subtract, then the remainder with the
 *   next digit brought down).
 * Numbers come from the level's check (divisor, dividend or quotient range,
 * remainder; decimals for a remainder written as a decimal), answers are exact.
 */
class BuildDivisionSheet
{
    public const CELL_MM = 8.0;

    public const GAP_MM = 8.0;

    /** Facts lines are spaced a little more than a cell apart, for writing. */
    public const FACT_GAP_MM = 6.0;

    public function __construct(private readonly CheckDivisionItems $check) {}

    /**
     * @param  array{build: string, check: array<string, mixed>}  $params
     * @return list<array{question: string, answer: string, top: int, bottom: int, kind: string, decimals: int}>
     */
    public function handle(int $seed, array $params): array
    {
        $check = (array) $params['check'];
        $long = ($params['build'] ?? 'facts') === 'long';
        $decimals = (int) ($check['decimals'] ?? 0);
        [$maxDividend, $maxDivisor] = self::extremes($check);
        $quotientDigits = strlen((string) intdiv($maxDividend, max(1, isset($check['divisors']) ? min($check['divisors']) : ($check['divisor'][0] ?? 2))));
        $size = $long
            ? self::longGrid(strlen((string) $maxDividend), strlen((string) $maxDivisor), $quotientDigits, $decimals)
            : self::factsGrid(strlen((string) $maxDividend), strlen((string) $maxDivisor), $quotientDigits);

        $random = new Randomizer(new Mt19937($seed));
        $problems = [];
        for ($i = 0; $i < $size['across'] * $size['down']; $i++) {
            preg_match('/(\d+) ÷ (\d+)/', $this->check->make($check, $random)['question'], $m);
            [$a, $b] = [(int) $m[1], (int) $m[2]];
            $answer = match (true) {
                $decimals > 0 => str_replace('.', ',', number_format($a / $b, $decimals, '.', '')),
                $a % $b !== 0 => intdiv($a, $b).' reste '.($a % $b),
                default => (string) intdiv($a, $b),
            };
            $problems[] = ['question' => "{$a} ÷ {$b}", 'answer' => $answer, 'top' => $a, 'bottom' => $b, 'kind' => $long ? 'long' : 'fact', 'decimals' => $decimals];
        }

        return $problems;
    }

    /**
     * A facts line: dividend, ÷, divisor, =, then a box per quotient digit.
     *
     * @return array{kind: string, columns: int, dividend: int, divisor: int, quotient: int, height: int, cell: float, gap: float, gapY: float, across: int, down: int}
     */
    public static function factsGrid(int $dividendDigits, int $divisorDigits, int $quotientDigits): array
    {
        $cell = self::CELL_MM * SheetPage::MM;
        $gap = self::GAP_MM * SheetPage::MM;
        $gapY = self::FACT_GAP_MM * SheetPage::MM;
        $columns = $dividendDigits + 1 + $divisorDigits + 1 + $quotientDigits;
        $fit = SheetPage::fit($columns * $cell, $cell, $gap, $gapY, 4, 16);

        return ['kind' => 'fact', 'columns' => $columns, 'dividend' => $dividendDigits, 'divisor' => $divisorDigits, 'quotient' => $quotientDigits, 'height' => 1, 'cell' => round($cell, 2), 'gap' => round($gap, 2), 'gapY' => round($gapY, 2), 'across' => $fit['columns'], 'down' => $fit['rows']];
    }

    /**
     * A crochet grid. Left: a spare column for the "−" signs, then the dividend
     * (plus "," and a column per decimal). Right, behind the bracket: the divisor
     * on top, the quotient under the line. Rows: the dividend, then 2 per
     * quotient digit (decimals included).
     *
     * @return array{kind: string, columns: int, left: int, right: int, height: int, steps: int, cell: float, gap: float, gapY: float, across: int, down: int}
     */
    public static function longGrid(int $dividendDigits, int $divisorDigits, int $quotientDigits, int $decimals = 0): array
    {
        $cell = self::CELL_MM * SheetPage::MM;
        $gap = self::GAP_MM * SheetPage::MM;
        $left = 1 + $dividendDigits + ($decimals > 0 ? 1 + $decimals : 0);
        $right = max($divisorDigits, $quotientDigits + ($decimals > 0 ? 1 + $decimals : 0)) + 1;
        $steps = $quotientDigits + $decimals;
        $height = 1 + 2 * $steps;
        $fit = SheetPage::fit(($left + $right) * $cell, $height * $cell, $gap, $gap, 4, 6);

        return ['kind' => 'long', 'columns' => $left + $right, 'left' => $left, 'right' => $right, 'height' => $height, 'steps' => $steps, 'cell' => round($cell, 2), 'gap' => round($gap, 2), 'gapY' => round($gap, 2), 'across' => $fit['columns'], 'down' => $fit['rows']];
    }

    /**
     * The grid for a sheet, sized from its problems.
     *
     * @param  list<array{top: int, bottom: int, kind: string, decimals?: int}>  $problems
     * @return array<string, mixed>
     */
    public static function layout(array $problems): array
    {
        $dividend = max(array_map(fn (array $p): int => strlen((string) $p['top']), $problems));
        $divisor = max(array_map(fn (array $p): int => strlen((string) $p['bottom']), $problems));
        $quotient = max(array_map(fn (array $p): int => strlen((string) max(1, intdiv($p['top'], $p['bottom']))), $problems));
        $decimals = max(array_map(fn (array $p): int => (int) ($p['decimals'] ?? 0), $problems));
        $long = $problems[0]['kind'] === 'long';
        $grid = $long ? self::longGrid($dividend, $divisor, $quotient, $decimals) : self::factsGrid($dividend, $divisor, $quotient);
        while (count($problems) > $grid['across'] * $grid['down'] && $grid['cell'] > 12) {
            $grid['cell'] = round($grid['cell'] * .92, 2);
            $fit = SheetPage::fit($grid['columns'] * $grid['cell'], $grid['height'] * $grid['cell'], $grid['gap'], $grid['gapY'], $long ? 4 : 4, $long ? 6 : 16);
            [$grid['across'], $grid['down']] = [$fit['columns'], $fit['rows']];
        }
        $digits = fn (int $n): string => $n.' '.($n === 1 ? 'chiffre' : 'chiffres');

        return $grid + [
            'rows' => (int) ceil(count($problems) / $grid['across']),
            'title' => $long ? 'Divisions : '.$digits($dividend).' ÷ '.$digits($divisor).($decimals > 0 ? ', quotient décimal' : '') : 'Divisions des tables',
            'instruction' => $long ? 'Calcule chaque division. Un chiffre par case.' : 'Écris le quotient dans les cases.',
        ];
    }

    /** @return array{int, int} the largest dividend and divisor the level can make */
    private static function extremes(array $check): array
    {
        $divisor = isset($check['divisors']) ? max($check['divisors']) : ($check['divisor'][1] ?? 9);
        $dividend = isset($check['dividend']) ? $check['dividend'][1] : $divisor * ($check['quotient'][1] ?? 10);

        return [$dividend, $divisor];
    }
}
