<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Modules\Calendar\CalendarStore;

/** Rename, recolour, change the icon of, show or hide a calendar. */
class UpdateCalendar
{
    public function __construct(private readonly CalendarStore $store) {}

    /** @param  array{name?: string, color?: string, icon?: string, visible?: bool}  $changes */
    public function handle(string $id, array $changes): void
    {
        $this->store->save(array_map(
            fn (array $c): array => $c['id'] === $id ? array_replace($c, array_intersect_key($changes, array_flip(['name', 'color', 'icon', 'visible']))) : $c,
            $this->store->all(),
        ));
    }
}
