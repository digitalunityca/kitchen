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

    /** @return list<array{question: string, answer: string, top: int, bottom: int}> */
    public function handle(int $grade, int $seed): array
    {
        [$top, $bottom] = self::RANGES[max(1, min(6, $grade))];
        $random = new Randomizer(new Mt19937($seed));
        $rows = self::ROWS;
        $problems = [];

        for ($i = 0; $i < self::COLUMNS * $rows; $i++) {
            $a = $random->getInt(...$top);
            $b = $random->getInt(...$bottom);
            $problems[] = ['question' => "{$a} + {$b}", 'answer' => (string) ($a + $b), 'top' => $a, 'bottom' => $b];
        }

        return $problems;
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
