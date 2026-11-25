<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Access\AccessSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings pages need the PIN (HOME_HUB_PIN). Once entered, they stay open
 * for 10 minutes of activity on that device. Without a PIN set, they are open.
 */
class RequireSettingsPin
{
    public const SESSION_KEY = 'settings_unlocked_until';

    public const MINUTES = 10;

    public function __construct(private readonly AccessSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->settings->pinEnabled() && (int) $request->session()->get(self::SESSION_KEY, 0) < now()->timestamp) {
            return redirect()->route('settings.unlock', ['to' => $request->getRequestUri()]);
        }

        $request->session()->put(self::SESSION_KEY, now()->addMinutes(self::MINUTES)->timestamp);

        return $next($request);
    }
}
