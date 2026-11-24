<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Actions\Access\DecideTunnelAccess;
use App\Actions\Access\IssueUnlockCookie;
use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use App\Http\Access\TunnelAccess;
use Illuminate\Http\Request;
use Tests\TestCase;

class DecideTunnelAccessTest extends TestCase
{
    private const HOME = '203.0.113.7';

    private const AWAY = '192.0.2.1';

    /** @param  list<string>  $allowedIps */
    private function settings(array $allowedIps = [self::HOME], string $pin = '4821'): void
    {
        $this->app->instance(AccessSettings::class, new AccessSettings($allowedIps, $pin, 'test-key'));
    }

    /** @param  array<string, string>  $cookies */
    private function decide(string $method = 'GET', string $path = '/', ?string $ip = self::AWAY, array $cookies = []): TunnelAccess
    {
        $server = $ip === null ? [] : ['HTTP_CF_CONNECTING_IP' => $ip];

        return $this->app->make(DecideTunnelAccess::class)->handle(Request::create($path, $method, [], $cookies, [], $server));
    }

    public function test_lan_requests_and_home_ips_are_allowed(): void
    {
        $this->settings();

        $this->assertSame(TunnelAccess::Allow, $this->decide(ip: null));
        $this->assertSame(TunnelAccess::Allow, $this->decide(ip: self::HOME));
    }

    public function test_the_unlock_page_is_always_reachable(): void
    {
        $this->settings();

        $this->assertSame(TunnelAccess::Allow, $this->decide('POST', '/unlock'));
    }

    public function test_away_reads_are_sent_to_unlock_and_writes_are_forbidden(): void
    {
        $this->settings();

        $this->assertSame(TunnelAccess::RedirectToUnlock, $this->decide('GET'));
        $this->assertSame(TunnelAccess::RedirectToUnlock, $this->decide('HEAD'));
        $this->assertSame(TunnelAccess::Forbid, $this->decide('POST'));
        $this->assertSame(TunnelAccess::Forbid, $this->decide('DELETE'));
    }

    public function test_a_valid_unlock_cookie_lets_away_visitors_in(): void
    {
        $this->settings();
        $cookie = $this->app->make(IssueUnlockCookie::class)->handle()->getValue();

        $this->assertSame(TunnelAccess::Allow, $this->decide('POST', cookies: [AccessGate::COOKIE => $cookie]));
    }

    public function test_without_a_pin_away_visitors_are_forbidden_unless_nothing_is_configured(): void
    {
        $this->settings(pin: '');
        $this->assertSame(TunnelAccess::Forbid, $this->decide());

        $this->settings(allowedIps: [], pin: '');
        $this->assertSame(TunnelAccess::AllowUnprotected, $this->decide());
    }
}
