<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind the Cloudflare Tunnel, requests arrive over plain http from the
        // cloudflared container; trust its X-Forwarded-* headers so generated
        // URLs use the public https hostname.
        $middleware->trustProxies(at: '*');

        // Through the tunnel, only addresses in HOME_HUB_ALLOWED_IPS get in.
        $middleware->prepend(\App\Http\Middleware\RestrictTunnelVisitors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
