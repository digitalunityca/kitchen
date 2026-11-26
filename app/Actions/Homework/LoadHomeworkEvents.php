<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;

/**
 * What happened to the kids' points since the dashboard last looked, for the
 * celebration on the tablet: approved sheets (several at once count as one
 * celebration per kid) and rewards bought. The cursor is a position in the points
 * ledger; entries older than MAX_AGE_MINUTES are skipped so a tablet coming back
 * from YouTube doesn't celebrate yesterday.
 */
class LoadHomeworkEvents
{
    public const MAX_AGE_MINUTES = 120;

    public function __construct(private readonly HomeworkStore $store) {}

    /**
     * @param  list<string>  $kids
     * @return array{next: int, events: list<array<string, mixed>>, badges: array<string, int>}
     */
    public function handle(?int $after, array $kids): array
    {
        $ledger = $this->store->load()['ledger'];
        $next = count($ledger);
        $cutoff = now(HomeworkModule::TIMEZONE)->subMinutes(self::MAX_AGE_MINUTES)->toIso8601String();
        $events = [];

        foreach (array_slice($ledger, max(0, min($after ?? $next, $next))) as $entry) {
            if (! in_array($entry['kid'], $kids, true) || (string) $entry['at'] < $cutoff) {
                continue;
            }
            if (! empty($entry['taskId'])) {
                $key = 'approved:'.$entry['kid'];
                $events[$key] ??= ['kind' => 'approved', 'kid' => $entry['kid'], 'points' => 0, 'sheets' => 0];
                $events[$key]['points'] += (int) $entry['points'];
                $events[$key]['sheets']++;
            } elseif (! empty($entry['rewardId'])) {
                $events[] = ['kind' => 'reward', 'kid' => $entry['kid'], 'points' => (int) $entry['points'], 'name' => (string) $entry['reason']];
            }
        }

        $events = array_values(array_map(fn (array $e): array => $e + ['balance' => $this->store->balance($e['kid'])], $events));
        $badges = [];
        foreach ($kids as $kid) {
            $badges[$kid] = $this->store->unseenPoints($kid);
        }

        return ['next' => $next, 'events' => $events, 'badges' => $badges];
    }
}
