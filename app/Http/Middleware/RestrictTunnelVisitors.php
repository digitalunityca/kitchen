<?php

namespace App\Http\Middleware;

use App\Http\Access\AccessGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requests that come through the Cloudflare Tunnel carry the visitor's real
 * address in CF-Connecting-IP. Visitors from HOME_HUB_ALLOWED_IPS get straight
 * in. Everyone else needs the unlock cookie from /unlock (HOME_HUB_PIN); with
 * no PIN set they get 403. Requests that reach the hub directly on the LAN
 * have no such header and are not checked.
 */
class RestrictTunnelVisitors
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AccessGate::viaTunnel($request) || AccessGate::fromHome($request)) {
            return $next($request);
        }

        if ($request->is('unlock')) {
            return $next($request);
        }

        if (AccessGate::pinEnabled()) {
            if (AccessGate::hasValidCookie($request)) {
                return $next($request);
            }

            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return redirect()->to('/unlock?'.http_build_query(['to' => $request->getRequestUri()]));
            }

            abort(403);
        }

        if (AccessGate::allowedIps() === []) {
            Log::warning('Tunnel request allowed because HOME_HUB_ALLOWED_IPS and HOME_HUB_PIN are empty.', [
                'ip' => AccessGate::clientIp($request),
            ]);

            return $next($request);
        }

        abort(403);
    }
}
