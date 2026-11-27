<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Weather;

use App\Actions\Weather\DescribePrecipitation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DescribePrecipitationTest extends TestCase
{
    /**
     * Hours 13:00–23:00 with these probabilities; code 61 (rain) unless overridden.
     *
     * @param  list<int>  $probabilities
     * @param  array<int, int>  $codes
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private static function day(array $probabilities, array $codes = []): array
    {
        return array_map(
            fn (int $hour, int $probability): array => [sprintf('2026-11-24T%02d:00', $hour), $probability, $codes[$hour] ?? 61],
            range(13, 23),
            $probabilities,
        );
    }

    /** @return array<string, array{list<array{0: string, 1: int, 2: int}>, array{0: string|null, 1: string|null}}> */
    public static function days(): array
    {
        return [
            'likely 3 PM to 6 PM' => [self::day([0, 0, 60, 80, 70, 20, 0, 0, 0, 0, 0]), ['Rain likely 3–6 PM', 'rain']],
            'already raining now' => [self::day([90, 80, 50, 10, 0, 0, 0, 0, 0, 0, 0]), ['Rain likely until 4 PM', 'rain']],
            'raining now all day' => [self::day(array_fill(0, 11, 80)), ['Rain likely today', 'rain']],
            'still likely at the end of the day' => [self::day([0, 0, 0, 0, 0, 0, 0, 0, 60, 70, 80], [21 => 73]), ['Snow likely from 9 PM', 'snow']],
            '49% is not likely' => [self::day([0, 10, 20, 30, 40, 49, 0, 0, 0, 0, 0]), [null, null]],
            'only the first stretch counts' => [self::day([0, 60, 0, 0, 90, 90, 0, 0, 0, 0, 0]), ['Rain likely 2–3 PM', 'rain']],
        ];
    }

    #[DataProvider('days')]
    public function test_it_names_the_likely_hours(array $hours, array $expected): void
    {
        $this->assertSame($expected, (new DescribePrecipitation)->handle($hours, '2026-11-24T13:00'));
    }

    public function test_missing_probabilities_are_not_likely(): void
    {
        $this->assertSame([null, null], (new DescribePrecipitation)->handle([['2026-11-24T13:00', null, 61]], '2026-11-24T13:00'));
    }
}
