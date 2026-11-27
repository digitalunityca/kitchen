<?php

namespace App\Providers;

use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use Carbon\Carbon;
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
        // Not a singleton: config changes (a new PIN) apply on the next resolve.
        $this->app->bind(AccessSettings::class, fn (): AccessSettings => AccessSettings::fromConfig());
    }

    /** Requests a minute from the home's own address (all the family's devices together). */
    public const HOME_PER_MINUTE = 1000;

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates ("jeudi 27 novembre") follow the dashboard's language.
        Carbon::setLocale((string) config('app.locale'));

        // Every request, per visitor IP: 120 a minute, enough for one visitor and low
        // enough to stop scripts hammering the public hostname. The home's own address
        // (HOME_HUB_ALLOWED_IPS) is shared by the tablet, phones and computers, so it
        // gets a much higher ceiling. PIN attempts keep their own strict limits.
        RateLimiter::for('global', function (Request $request) {
            $ip = AccessGate::clientIp($request);

            return Limit::perMinute($this->app->make(AccessSettings::class)->isHomeIp($ip) ? self::HOME_PER_MINUTE : 120)->by($ip);
        });

        // PIN attempts on POST /unlock. Repeated failures also trigger a longer
        // lockout in UnlockController.
        RateLimiter::for('unlock', fn (Request $request) => Limit::perMinute(5)->by(AccessGate::clientIp($request)));
    }
}
