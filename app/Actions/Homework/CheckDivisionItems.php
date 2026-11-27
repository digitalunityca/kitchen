<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The AI writes the division sheets; this checks each calculation against the
 * level's numbers (divisor, dividend or quotient range, remainder allowed or
 * not) and fixes what doesn't fit: a wrong answer is corrected, an exercise
 * outside the level is replaced by one made on the hub ("Calcule : 35 ÷ 5").
 * Word problems with no written calculation are kept when the level allows them.
 */
class CheckDivisionItems
{
    /**
     * @param  list<array{question: string, answer: string}>  $exercises
     * @param  array{divisors?: list<int>, divisor?: array{int, int}, dividend?: array{int, int}, quotient?: array{int, int}, remainder?: bool, word?: bool}  $check
     * @return list<array{question: string, answer: string}>
     */
    public function handle(array $exercises, array $check, int $seed): array
    {
        $random = new Randomizer(new Mt19937($seed));

        return array_map(function (array $exercise) use ($check, $random): array {
            if (! preg_match('/(\d[\d\x{00A0}\x{202F} ]*\d|\d)\s*[÷:\/]\s*(\d+)/u', $exercise['question'], $m)) {
                return ($check['word'] ?? false) ? $exercise : $this->make($check, $random);
            }
            $a = (int) preg_replace('/\D/', '', $m[1]);
            $b = (int) $m[2];
            if (! $this->fits($a, $b, $check)) {
                return $this->make($check, $random);
            }
            if (! ($check['remainder'] ?? false) && ! preg_match('/(?<!\d)'.intdiv($a, $b).'(?!\d)/', $exercise['answer'])) {
                $exercise['answer'] = (string) intdiv($a, $b);
            }

            return $exercise;
        }, $exercises);
    }

    private function fits(int $a, int $b, array $check): bool
    {
        if ($b === 0) {
            return false;
        }
        $divisorOk = isset($check['divisors']) ? in_array($b, $check['divisors'], true)
            : (! isset($check['divisor']) || ($b >= $check['divisor'][0] && $b <= $check['divisor'][1]));
        $sizeOk = isset($check['dividend']) ? $a >= $check['dividend'][0] && $a <= $check['dividend'][1]
            : (! isset($check['quotient']) || (intdiv($a, $b) >= $check['quotient'][0] && intdiv($a, $b) <= $check['quotient'][1]));

        return $divisorOk && $sizeOk && (($check['remainder'] ?? false) || $a % $b === 0);
    }

    /** @return array{question: string, answer: string} */
    private function make(array $check, Randomizer $random): array
    {
        for ($try = 0; $try < 200; $try++) {
            $b = isset($check['divisors']) ? $check['divisors'][$random->getInt(0, count($check['divisors']) - 1)] : $random->getInt(...($check['divisor'] ?? [2, 9]));
            if (isset($check['dividend'])) {
                $a = $random->getInt(...$check['dividend']);
                if (! ($check['remainder'] ?? false)) {
                    $a -= $a % $b;
                }
            } else {
                $a = $b * $random->getInt(...($check['quotient'] ?? [1, 10]));
            }
            if ($this->fits($a, $b, $check)) {
                break;
            }
        }
        $q = intdiv($a, $b);
        $r = $a % $b;

        return ['question' => "Calcule : {$a} ÷ {$b}", 'answer' => $r === 0 ? (string) $q : "{$q} reste {$r}"];
    }
}
