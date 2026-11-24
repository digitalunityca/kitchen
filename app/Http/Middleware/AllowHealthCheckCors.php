<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The tablet's offline page (deploy/offline/offline.html, loaded from file://)
 * polls GET /up and only returns to the dashboard when that answers 200. It can
 * only read the status with CORS, so /up, and nothing else, allows any origin.
 * A Cloudflare 530 has no such header, so the page keeps waiting.
 */
class AllowHealthCheckCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('up')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        return $response;
    }
}
