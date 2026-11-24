<?php

namespace App\Dashboard;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
    }

    public function boot(ModuleRegistry $registry): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        // Each module mounts its own routes under /modules/{key}.
        Route::middleware('web')->group(function () use ($registry) {
            foreach ($registry->all() as $module) {
                Route::prefix('modules/'.$module->key())
                    ->name('modules.'.$module->key().'.')
                    ->group(fn ($router) => $module->routes($router));
            }
        });
    }
}
