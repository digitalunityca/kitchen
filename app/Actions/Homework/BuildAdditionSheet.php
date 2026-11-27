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
    public const COLUMNS = 5;

    public const ROWS = 4;

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
     * The sheet's problems. A level (exercise variant) gives the number ranges and,
     * optionally, carry (true: at least one carry, false: none) and sum_max; without
     * one, the grade's ranges are used as before.
     *
     * @param  array{top?: array{int, int}, bottom?: array{int, int}, carry?: bool, sum_max?: int}|null  $params
     * @return list<array{question: string, answer: string, top: int, bottom: int}>
     */
    public function handle(int $grade, int $seed, ?array $params = null): array
    {
        [$top, $bottom] = isset($params['top'], $params['bottom']) ? [$params['top'], $params['bottom']] : self::RANGES[max(1, min(6, $grade))];
        $carry = $params['carry'] ?? null;
        $max = $params['sum_max'] ?? PHP_INT_MAX;
        $random = new Randomizer(new Mt19937($seed));
        $problems = [];

        for ($i = 0; $i < self::COLUMNS * self::ROWS; $i++) {
            // Draw until the pair fits the level (a few tries at most for these ranges).
            for ($try = 0; $try < 200; $try++) {
                $a = $random->getInt(...$top);
                $b = $random->getInt(...$bottom);
                if ($a + $b <= $max && ($carry === null || self::carries($a, $b) === $carry)) {
                    break;
                }
            }
            $problems[] = ['question' => "{$a} + {$b}", 'answer' => (string) ($a + $b), 'top' => $a, 'bottom' => $b];
        }

        return $problems;
    }

    /** Does adding these two numbers carry in any column? */
    public static function carries(int $a, int $b): bool
    {
        for (; $a > 0 || $b > 0; $a = intdiv($a, 10), $b = intdiv($b, 10)) {
            if ($a % 10 + $b % 10 >= 10) {
                return true;
            }
        }

        return false;
    }

    /**
     * How to draw a sheet so every number lines up by place value: the column width
     * in characters comes from the longest possible line (the sum, or "+ " and the
     * second number), and the digit size shrinks a little for 4-digit numbers.
     *
     * @param  list<array{top: int, bottom: int}>  $problems
     * @return array{chars: int, font: int, rows: int, digits: int, title: string}
     */
    public static function layout(array $problems): array
    {
        $digits = max(array_map(fn (array $p): int => max(strlen((string) $p['top']), strlen((string) $p['bottom'])), $problems));
        $fewest = min(array_map(fn (array $p): int => min(strlen((string) $p['top']), strlen((string) $p['bottom'])), $problems));
        $sum = max(array_map(fn (array $p): int => strlen((string) ($p['top'] + $p['bottom'])), $problems));
        $chars = max($sum, 2 + $digits);

        return [
            'chars' => $chars,
            'font' => $chars <= 5 ? 22 : 19,
            'rows' => (int) ceil(count($problems) / self::COLUMNS),
            'digits' => $digits,
            'title' => match (true) {
                $digits === 1 => 'Additions : 1 chiffre',
                $fewest < $digits => "Additions : {$fewest} et {$digits} chiffres",
                default => "Additions : {$digits} chiffres",
            },
        ];
    }
}
