<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Modules\Calendar\CalendarStore;

class RemoveCalendar
{
    public function __construct(private readonly CalendarStore $store) {}

    public function handle(string $id): void
    {
        $this->store->save(array_filter($this->store->all(), fn (array $c): bool => $c['id'] !== $id));
    }
}
