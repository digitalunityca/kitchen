<?php

use App\Http\Access\AccessGate;
use App\Http\Middleware\RestrictTunnelVisitors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

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

        // Through the tunnel, addresses in HOME_HUB_ALLOWED_IPS get in; everyone
        // else needs the PIN from /unlock. Every request counts towards the
        // per-IP 'global' rate limit first (App\Providers\AppServiceProvider).
        $middleware->prepend([
            ThrottleRequests::using('global'),
            RestrictTunnelVisitors::class,
        ]);

        // The unlock cookie is encrypted by App\Http\Access\AccessGate itself,
        // because RestrictTunnelVisitors reads it before EncryptCookies runs.
        $middleware->encryptCookies(except: [AccessGate::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
