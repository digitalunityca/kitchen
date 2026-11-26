<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Spend points on a reward from the store (an adult confirms with the PIN first).
 * Evening rewards: on school days only once all of today's homework is approved.
 * Weekend prizes: Saturday and Sunday only. Never more points than the kid has.
 */
class RedeemReward
{
    public function __construct(private readonly HomeworkStore $store) {}

    /** @throws ValidationException when the reward can't be bought now */
    public function handle(string $kid, string $rewardId, CarbonInterface $today): void
    {
        $reward = collect(HomeworkModule::REWARDS)->firstWhere('id', $rewardId);
        if ($reward === null) {
            throw ValidationException::withMessages(['reward' => 'That reward isn’t in the store.']);
        }

        $bought = $this->boughtToday($kid, $today);
        $state = self::state($reward, $this->store->balance($kid), $this->store->tasks($kid, $today->toDateString()) ?? [], $bought, $today);

        if ($state !== 'ready') {
            throw ValidationException::withMessages(['reward' => self::REASONS[$state]]);
        }

        $this->store->addPoints($kid, -$reward['cost'], $reward['name'], rewardId: $reward['id']);
    }

    public const REASONS = [
        'points' => 'Not enough points yet.',
        'homework' => 'Finish and get today’s homework checked first.',
        'weekend' => 'Weekend prizes are for Saturday and Sunday.',
        'limit' => 'Already taken today.',
        'after' => 'Take the first 2 periods first.',
    ];

    /**
     * Whether a reward can be bought now: "ready", or why not.
     *
     * @param  array{id: string, cost: int, when: string, limit?: int, after?: string}  $reward
     * @param  list<array<string, mixed>>  $tasks  today's tasks
     * @param  array<string, int>  $bought  reward id => times bought today
     */
    public static function state(array $reward, int $balance, array $tasks, array $bought, CarbonInterface $today): string
    {
        return match (true) {
            $reward['when'] === 'weekend' && ! $today->isWeekend() => 'weekend',
            $reward['when'] === 'evening' && ! $today->isWeekend() && ($tasks === [] || collect($tasks)->contains(fn (array $t): bool => $t['status'] !== 'approved')) => 'homework',
            isset($reward['limit']) && ($bought[$reward['id']] ?? 0) >= $reward['limit'] => 'limit',
            isset($reward['after']) && ($bought[$reward['after']] ?? 0) === 0 => 'after',
            $balance < $reward['cost'] => 'points',
            default => 'ready',
        };
    }

    /** @return array<string, int> reward id => times bought today */
    public function boughtToday(string $kid, CarbonInterface $today): array
    {
        return collect($this->store->load()['ledger'])
            ->filter(fn (array $e): bool => $e['kid'] === $kid && ! empty($e['rewardId']) && str_starts_with((string) $e['at'], $today->toDateString()))
            ->countBy('rewardId')->all();
    }
}
