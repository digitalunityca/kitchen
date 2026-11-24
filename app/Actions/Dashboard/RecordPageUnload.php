<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use Psr\Log\LoggerInterface;

/**
 * Log why the kitchen screen went away: the tablet beacons what it knows
 * (how long it ran, the last taps and Fully setting writes, whether music
 * played) when the page unloads, to track down unexpected reloads.
 */
class RecordPageUnload
{
    public function __construct(private readonly LoggerInterface $log) {}

    /** @param  array<string, mixed>  $report */
    public function handle(array $report): void
    {
        $this->log->info('Dashboard page unloaded.', [
            'ran_seconds' => (int) ($report['ranSeconds'] ?? 0),
            'playing' => (bool) ($report['playing'] ?? false),
            'visibility' => mb_substr((string) ($report['visibility'] ?? ''), 0, 20),
            'recent' => array_slice(array_map(fn ($e): string => mb_substr((string) $e, 0, 120), (array) ($report['recent'] ?? [])), -12),
        ]);
    }
}
