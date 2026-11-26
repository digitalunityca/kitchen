<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Homework;

use App\Actions\Homework\BuildWorksheet;
use PHPUnit\Framework\TestCase;

class BuildWorksheetTest extends TestCase
{
    public function test_the_same_seed_gives_the_same_sheet(): void
    {
        $build = new BuildWorksheet;

        $this->assertSame($build->handle('multiplication', 5, 42), $build->handle('multiplication', 5, 42));
        $this->assertNotSame($build->handle('multiplication', 5, 42), $build->handle('multiplication', 5, 43));
    }

    public function test_second_grade_sheets_stay_small_and_correct(): void
    {
        foreach (range(1, 30) as $seed) {
            [$additions, $subtractions] = (new BuildWorksheet)->handle('addition', 2, $seed);
            $this->assertCount(11, $additions['items']);
            $this->assertCount(4, $subtractions['items']);

            foreach ($additions['items'] as $item) {
                $this->assertSame((int) $item['top'] + (int) $item['bottom'], (int) $item['answer']);
                $this->assertLessThanOrEqual(99, (int) $item['answer']);
            }
            foreach ($subtractions['items'] as $item) {
                $this->assertSame((int) $item['top'] - (int) $item['bottom'], (int) $item['answer']);
                $this->assertGreaterThan(0, (int) $item['answer']);
            }

            [$groups] = (new BuildWorksheet)->handle('multiplication', 2, $seed);
            foreach ($groups['items'] as $item) {
                $this->assertContains($item['size'], [2, 5, 10]);
                $this->assertSame($item['groups'] * $item['size'], (int) $item['answer']);
            }

            [$share] = (new BuildWorksheet)->handle('division', 2, $seed);
            foreach ($share['items'] as $item) {
                $this->assertSame($item['total'], $item['people'] * (int) $item['answer']);
                $this->assertLessThanOrEqual(25, $item['total']);
            }
        }
    }

    public function test_fifth_grade_long_division_answers_include_the_remainder(): void
    {
        foreach (range(1, 30) as $seed) {
            [$facts, $long] = (new BuildWorksheet)->handle('division', 5, $seed);
            $this->assertCount(15, $facts['items']);
            $this->assertCount(5, $long['items']);

            foreach ($long['items'] as $item) {
                $dividend = (int) str_replace("\u{00A0}", '', $item['dividend']);
                $divisor = (int) $item['divisor'];
                $expected = BuildWorksheet::number(intdiv($dividend, $divisor)).($dividend % $divisor ? ' reste '.($dividend % $divisor) : '');
                $this->assertSame($expected, $item['answer']);
            }
        }
    }

    public function test_long_multiplications_are_three_digits_by_two(): void
    {
        [, $long] = (new BuildWorksheet)->handle('multiplication', 5, 7);

        $this->assertSame('long-multiplication', $long['kind']);
        foreach ($long['items'] as $item) {
            $a = (int) str_replace("\u{00A0}", '', $item['top']);
            $b = (int) $item['bottom'];
            $this->assertSame(BuildWorksheet::number($a * $b), $item['answer']);
            $this->assertSame(3, strlen((string) $a));
            $this->assertSame(2, strlen((string) $b));
        }
    }

    public function test_writing_picks_six_words_from_the_list(): void
    {
        $words = ['un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit'];
        [$copy] = (new BuildWorksheet)->handle('writing', 2, 3, $words);

        $this->assertCount(6, $copy['items']);
        $this->assertEmpty(array_diff(array_column($copy['items'], 'word'), $words));
        $this->assertSame([], (new BuildWorksheet)->handle('writing', 2, 3, []));
    }
}
