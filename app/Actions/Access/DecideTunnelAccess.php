<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use App\Http\Access\TunnelAccess;
use Illuminate\Http\Request;

/**
 * Requests that come through the Cloudflare Tunnel carry the visitor's real
 * address in CF-Connecting-IP. Visitors from the home IPs get straight in.
 * Everyone else needs the unlock cookie from /unlock; with no PIN set they are
 * refused, unless no allow-list is set either. Requests that reach the hub
 * directly on the LAN have no such header and are not checked.
 */
class DecideTunnelAccess
{
    public function __construct(
        private readonly AccessSettings $settings,
        private readonly VerifyUnlockCookie $verifyUnlockCookie,
    ) {}

    public function handle(Request $request): TunnelAccess
    {
        if (! AccessGate::viaTunnel($request) || $this->settings->isHomeIp(AccessGate::clientIp($request))) {
            return TunnelAccess::Allow;
        }

        if ($request->is('unlock')) {
            return TunnelAccess::Allow;
        }

        if ($this->settings->pinEnabled()) {
            // A signed worksheet link goes directly to its own parent PIN form.
            if ($request->is('modules/homework/confirm/*') && ($request->isMethod('GET') || $request->isMethod('POST')) && $request->hasValidRelativeSignature()) {
                return TunnelAccess::Allow;
            }

            if ($this->verifyUnlockCookie->handle($request->cookies->get(AccessGate::COOKIE))) {
                return TunnelAccess::Allow;
            }

            return $request->isMethod('GET') || $request->isMethod('HEAD')
                ? TunnelAccess::RedirectToUnlock
                : TunnelAccess::Forbid;
        }

        return $this->settings->hasAllowList() ? TunnelAccess::Forbid : TunnelAccess::AllowUnprotected;
    }
}
