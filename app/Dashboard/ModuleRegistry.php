<?php

namespace App\Dashboard;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/** Builds the enabled modules from config/dashboard.php, in the order listed there. */
class ModuleRegistry
{
    /** @var Collection<string, Module>|null */
    private ?Collection $modules = null;

    /** @return Collection<string, Module> keyed by module key */
    public function all(): Collection
    {
        return $this->modules ??= collect(config('dashboard.modules', []))
            ->filter(fn (array $cfg) => $cfg['enabled'] ?? true)
            ->map(function (array $cfg, string $class) {
                if (! is_subclass_of($class, Module::class)) {
                    throw new InvalidArgumentException("$class must extend ".Module::class);
                }

                return new $class($cfg);
            })
            ->keyBy(fn (Module $m) => $m->key());
    }

    public function get(string $key): ?Module
    {
        return $this->all()->get($key);
    }
}
