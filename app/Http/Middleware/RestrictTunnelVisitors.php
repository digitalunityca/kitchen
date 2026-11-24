<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requests that come through the Cloudflare Tunnel carry the visitor's real
 * address in CF-Connecting-IP. When HOME_HUB_ALLOWED_IPS is set, only those
 * addresses (or CIDR ranges) get in; everyone else sees 403. Requests that
 * reach the hub directly on the LAN have no such header and are not checked.
 */
class RestrictTunnelVisitors
{
    public function handle(Request $request, Closure $next): Response
    {
        $visitor = $request->header('CF-Connecting-IP');

        if ($visitor === null) {
            return $next($request);
        }

        $allowed = config('dashboard.allowed_ips', []);

        if ($allowed === []) {
            Log::warning('Tunnel request allowed because HOME_HUB_ALLOWED_IPS is empty.', ['ip' => $visitor]);

            return $next($request);
        }

        if (! IpUtils::checkIp($visitor, $allowed)) {
            abort(403);
        }

        return $next($request);
    }
}
