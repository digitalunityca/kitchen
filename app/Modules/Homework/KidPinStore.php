<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Hash;

/**
 * Each kid's 4-digit homework PIN, set by a parent in Settings. Stored only as a
 * bcrypt hash in storage/app/private/homework-pins.json; the PIN itself is never
 * kept or logged.
 */
class KidPinStore
{
    private const FILE = 'homework-pins.json';

    public function __construct(private readonly Filesystem $disk) {}

    public function has(string $kid): bool
    {
        return isset($this->load()[$kid]);
    }

    public function matches(string $kid, string $pin): bool
    {
        $hash = $this->load()[$kid] ?? null;

        return is_string($hash) && $pin !== '' && Hash::check($pin, $hash);
    }

    public function put(string $kid, string $pin): void
    {
        $pins = $this->load();
        $pins[$kid] = Hash::make($pin);
        $this->save($pins);
    }

    public function forget(string $kid): void
    {
        $pins = $this->load();
        unset($pins[$kid]);
        $this->save($pins);
    }

    /** @return array<string, string> kid id => hash */
    private function load(): array
    {
        $data = $this->disk->exists(self::FILE) ? json_decode((string) $this->disk->get(self::FILE), true) : null;

        return is_array($data) ? array_filter($data, 'is_string') : [];
    }

    /** @param  array<string, string>  $pins */
    private function save(array $pins): void
    {
        $this->disk->put(self::FILE, json_encode($pins, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
