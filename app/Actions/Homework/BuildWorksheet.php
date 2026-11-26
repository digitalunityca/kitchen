<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The problems on one worksheet, from its template, the kid's grade and the
 * task's seed (same seed = same sheet, so the answer key always matches).
 * Grades 1-3 get short sheets with pictures to draw; grades 4-6 get longer
 * sheets with column work and room to write the steps.
 *
 * A sheet is a list of sections; each has a "kind" that the worksheet view draws:
 * column (stacked + or −), facts (one line each), long-multiplication,
 * long-division, groups (draw the groups), share (draw the sharing), copy (writing).
 */
class BuildWorksheet
{
    private const THINGS = ['biscuits', 'billes', 'pommes', 'crayons', 'autocollants', 'bonbons'];

    /**
     * @param  list<string>  $words  for the writing template
     * @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}>
     */
    public function handle(string $template, int $grade, int $seed, array $words = []): array
    {
        $random = new Randomizer(new Mt19937($seed));
        $younger = $grade <= 3;

        return match ($template) {
            'addition' => $younger ? $this->additionYounger($random) : $this->additionOlder($random),
            'multiplication' => $younger ? $this->multiplicationYounger($random) : $this->multiplicationOlder($random),
            'division' => $younger ? $this->divisionYounger($random) : $this->divisionOlder($random),
            'writing' => $this->writing($random, $words),
            default => [],
        };
    }

    /** French number: 12 345 and 3,5. */
    public static function number(int|float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', "\u{00A0}");
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function additionYounger(Randomizer $random): array
    {
        $additions = [];
        for ($i = 0; $i < 11; $i++) {
            $a = $random->getInt(11, 79);
            $b = $random->getInt(5, 99 - $a);
            $additions[] = ['top' => (string) $a, 'bottom' => (string) $b, 'op' => '+', 'answer' => (string) ($a + $b)];
        }

        $subtractions = [];
        for ($i = 0; $i < 4; $i++) {
            $a = $random->getInt(20, 99);
            $b = $random->getInt(5, $a - 1);
            $subtractions[] = ['top' => (string) $a, 'bottom' => (string) $b, 'op' => '−', 'answer' => (string) ($a - $b)];
        }

        return [
            ['kind' => 'column', 'heading' => 'Additions', 'items' => $additions],
            ['kind' => 'column', 'heading' => 'Soustractions', 'items' => $subtractions],
        ];
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function additionOlder(Randomizer $random): array
    {
        $whole = [];
        for ($i = 0; $i < 12; $i++) {
            $a = $random->getInt(1000, 99999);
            $b = $random->getInt(1000, 99999);
            $whole[] = ['top' => self::number($a), 'bottom' => self::number($b), 'op' => '+', 'answer' => self::number($a + $b)];
        }

        $decimals = [];
        for ($i = 0; $i < 4; $i++) {
            $a = $random->getInt(100, 9999);
            $b = $random->getInt(10, 999) * 10;
            $decimals[] = ['top' => self::number($a / 100, 2), 'bottom' => self::number($b / 100, 2), 'op' => '+', 'answer' => self::number(($a + $b) / 100, 2)];
        }

        return [
            ['kind' => 'column', 'heading' => 'Additions', 'items' => $whole],
            ['kind' => 'column', 'heading' => 'Nombres décimaux', 'items' => $decimals],
        ];
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function multiplicationYounger(Randomizer $random): array
    {
        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $size = [2, 5, 10][$random->getInt(0, 2)];
            $groups = $random->getInt(2, $size === 10 ? 3 : 5);
            $items[] = ['groups' => $groups, 'size' => $size, 'answer' => (string) ($groups * $size)];
        }

        return [['kind' => 'groups', 'heading' => 'Dessine les groupes, puis écris la réponse', 'items' => $items]];
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function multiplicationOlder(Randomizer $random): array
    {
        $facts = [];
        for ($i = 0; $i < 20; $i++) {
            $a = $random->getInt(2, 10);
            $b = $random->getInt(2, 10);
            $facts[] = ['text' => "{$a} × {$b} =", 'answer' => (string) ($a * $b)];
        }

        $long = [];
        for ($i = 0; $i < 5; $i++) {
            $a = $random->getInt(100, 999);
            $b = $random->getInt(11, 99);
            $long[] = ['top' => self::number($a), 'bottom' => self::number($b), 'op' => '×', 'answer' => self::number($a * $b)];
        }

        return [
            ['kind' => 'facts', 'heading' => 'Tables de multiplication', 'items' => $facts],
            ['kind' => 'long-multiplication', 'heading' => 'Multiplications en colonne (écris tes calculs)', 'items' => $long],
        ];
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function divisionYounger(Randomizer $random): array
    {
        $items = [];
        for ($i = 0; $i < 8; $i++) {
            $people = $random->getInt(2, 5);
            $each = $random->getInt(1, 5);
            $items[] = ['total' => $people * $each, 'people' => $people, 'thing' => self::THINGS[$random->getInt(0, count(self::THINGS) - 1)], 'answer' => (string) $each];
        }

        return [['kind' => 'share', 'heading' => 'Partage en parts égales (tu peux dessiner)', 'items' => $items]];
    }

    /** @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}> */
    private function divisionOlder(Randomizer $random): array
    {
        $facts = [];
        for ($i = 0; $i < 15; $i++) {
            $divisor = $random->getInt(2, 10);
            $quotient = $random->getInt(2, 10);
            $facts[] = ['text' => ($divisor * $quotient)." ÷ {$divisor} =", 'answer' => (string) $quotient];
        }

        $long = [];
        for ($i = 0; $i < 5; $i++) {
            $divisor = $random->getInt(2, 9);
            $dividend = $random->getInt(100, 9999);
            $remainder = $dividend % $divisor;
            $answer = self::number(intdiv($dividend, $divisor)).($remainder > 0 ? " reste {$remainder}" : '');
            $long[] = ['dividend' => self::number($dividend), 'divisor' => (string) $divisor, 'answer' => $answer];
        }

        return [
            ['kind' => 'facts', 'heading' => 'Divisions de base', 'items' => $facts],
            ['kind' => 'long-division', 'heading' => 'Divisions en potence (écris tes calculs)', 'items' => $long],
        ];
    }

    /**
     * @param  list<string>  $words
     * @return list<array{kind: string, heading: string, items: list<array<string, mixed>>}>
     */
    private function writing(Randomizer $random, array $words): array
    {
        $words = array_values(array_filter(array_map('trim', $words)));
        if ($words === []) {
            return [];
        }

        $picked = array_slice($random->shuffleArray($words), 0, 6);
        $items = array_map(fn (string $word): array => ['word' => $word, 'copies' => mb_strlen($word) > 14 ? 2 : 3, 'answer' => ''], $picked);

        return [['kind' => 'copy', 'heading' => 'Copie chaque mot sur les lignes', 'items' => $items]];
    }
}
