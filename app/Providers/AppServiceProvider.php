<?php

namespace App\Providers;

use App\Http\Access\AccessGate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every request, per visitor IP. The dashboard polls rarely, so this only
        // bites scripts hammering the public hostname.
        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(120)->by(AccessGate::clientIp($request)));

        // PIN attempts on POST /unlock. Repeated failures also trigger a longer
        // lockout in UnlockController.
        RateLimiter::for('unlock', fn (Request $request) => Limit::perMinute(5)->by(AccessGate::clientIp($request)));
    }
}
