<?php

declare(strict_types=1);

namespace App\Http\Access;

/** What RestrictTunnelVisitors does with a request. */
enum TunnelAccess
{
    case Allow;

    /** Let in only because neither an allow-list nor a PIN is configured; worth a log line. */
    case AllowUnprotected;

    case RedirectToUnlock;

    case Forbid;
}
