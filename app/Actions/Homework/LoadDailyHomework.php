<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonInterface;

/** A kid's tasks for one day: the ones they picked, with their status. */
class LoadDailyHomework
{
    public function __construct(private readonly HomeworkStore $store) {}

    /**
     * @param  array{id: string}  $kid
     * @return list<array<string, mixed>>
     */
    public function handle(array $kid, CarbonInterface $date): array
    {
        return $this->store->tasks($kid['id'], $date->toDateString()) ?? [];
    }
}
