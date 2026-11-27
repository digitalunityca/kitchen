<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Models\KidPin;
use Illuminate\Support\Facades\Hash;

/**
 * Each kid's 4-digit homework PIN, set by a parent in Settings. Stored only as a
 * bcrypt hash (kid_pins table); the PIN itself is never kept or logged.
 */
class KidPinStore
{
    public function has(string $kid): bool
    {
        return KidPin::query()->whereKey($kid)->exists();
    }

    public function matches(string $kid, string $pin): bool
    {
        $hash = KidPin::query()->find($kid)?->hash;

        return is_string($hash) && $pin !== '' && Hash::check($pin, $hash);
    }

    public function put(string $kid, string $pin): void
    {
        KidPin::query()->updateOrCreate(['kid' => $kid], ['hash' => Hash::make($pin)]);
    }

    public function forget(string $kid): void
    {
        KidPin::query()->whereKey($kid)->delete();
    }
}
