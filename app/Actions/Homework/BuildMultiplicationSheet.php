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
    public const ACROSS = 3;

    public const COUNT = 9;

    /** Grade => [top number range, multiplier range]. */
    private const RANGES = [
        1 => [[2, 9], [2, 5]],
        2 => [[10, 99], [2, 9]],
        3 => [[10, 99], [2, 9]],
        4 => [[10, 99], [10, 99]],
        5 => [[100, 999], [10, 99]],
        6 => [[100, 999], [100, 999]],
    ];

    /** @return list<array{question: string, answer: string, top: int, bottom: int}> */
    public function handle(int $grade, int $seed): array
    {
        [$top, $bottom] = self::RANGES[max(1, min(6, $grade))];
        $random = new Randomizer(new Mt19937($seed));
        $problems = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $a = $random->getInt(...$top);
            $b = $random->getInt(...$bottom);
            $problems[] = ['question' => "{$a} × {$b}", 'answer' => (string) ($a * $b), 'top' => $a, 'bottom' => $b];
        }

        return $problems;
    }

    /**
     * The grid for a sheet: as many columns as the longest possible product, plus one
     * spare (for the × sign and carries); one row for each digit of the multiplier
     * (its partial products), and a total row when there is more than one.
     *
     * @param  list<array{top: int, bottom: int}>  $problems
     * @return array{columns: int, partials: int, total: bool, title: string}
     */
    public static function layout(array $problems): array
    {
        $product = max(array_map(fn (array $p): int => strlen((string) ($p['top'] * $p['bottom'])), $problems));
        $topDigits = max(array_map(fn (array $p): int => strlen((string) $p['top']), $problems));
        $partials = max(array_map(fn (array $p): int => strlen((string) $p['bottom']), $problems));

        return [
            'columns' => max($product, $topDigits, $partials + 1) + 1,
            'partials' => $partials,
            'total' => $partials > 1,
            'title' => "Multiplications : {$topDigits} chiffres × {$partials} ".($partials === 1 ? 'chiffre' : 'chiffres'),
        ];
    }
}
