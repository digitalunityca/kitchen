<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Access\AccessGate;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Laravel's throttle, but each route keeps its own count per visitor. The stock
 * one counts every route with "throttle:N,1" in one shared bucket per address, so
 * the dashboard's background polling (homework events, now-playing, Habs, calendar)
 * used up the PIN screens' allowance and a correct PIN came back "Too many tries".
 */
class ThrottlePerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        $name = $route?->getName() ?? ($route?->uri() ?? $request->path());

        return sha1($name.'|'.AccessGate::clientIp($request));
    }
}
