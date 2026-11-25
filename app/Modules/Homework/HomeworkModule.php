<?php

namespace App\Modules\Homework;

use App\Dashboard\Module;
use Illuminate\Support\Str;

/**
 * One round avatar button per kid in the side menu, under Home. Each opens a
 * "<Name>'s homework" sheet (a placeholder until the homework section is defined).
 * Kids are configured in config/dashboard.php: name, initial or emoji, colour.
 */
class HomeworkModule extends Module
{
    public function key(): string
    {
        return 'homework';
    }

    public function name(): string
    {
        return 'Homework';
    }

    /** No card: the buttons go into the side menu and the sheet floats over the dashboard. */
    public function placement(): string
    {
        return 'overlay';
    }

    /** @return array{kids: list<array{id: string, name: string, badge: string, color: string}>} */
    public function data(): array
    {
        return ['kids' => array_values(array_map(fn (array $kid): array => [
            'id' => Str::slug($kid['name']),
            'name' => (string) $kid['name'],
            'badge' => (string) ($kid['badge'] ?? mb_substr((string) $kid['name'], 0, 1)),
            'color' => (string) ($kid['color'] ?? '#5AC8FA'),
        ], (array) $this->config('kids', [])))];
    }
}
