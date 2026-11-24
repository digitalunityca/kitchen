<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Access\DecideTunnelAccess;
use App\Http\Access\AccessGate;
use App\Http\Access\TunnelAccess;
use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/** Applies the tunnel access rules (App\Actions\Access\DecideTunnelAccess) to every request. */
class RestrictTunnelVisitors
{
    public function __construct(
        private readonly DecideTunnelAccess $decideTunnelAccess,
        private readonly LoggerInterface $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return match ($this->decideTunnelAccess->handle($request)) {
            TunnelAccess::Allow => $next($request),
            TunnelAccess::AllowUnprotected => $this->allowUnprotected($request, $next),
            TunnelAccess::RedirectToUnlock => redirect()->to('/unlock?'.http_build_query(['to' => $request->getRequestUri()])),
            TunnelAccess::Forbid => abort(403),
        };
    }

    private function allowUnprotected(Request $request, Closure $next): Response
    {
        $this->log->warning('Tunnel request allowed because HOME_HUB_ALLOWED_IPS and HOME_HUB_PIN are empty.', [
            'ip' => AccessGate::clientIp($request),
        ]);

        return $next($request);
    }
}
