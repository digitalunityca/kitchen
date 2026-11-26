<?php

namespace Tests\Feature;

use App\Http\Access\AccessGate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinAccessTest extends TestCase
{
    private const HOME = '203.0.113.7';

    private const AWAY = '192.0.2.1';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.allowed_ips' => [self::HOME], 'dashboard.pin' => '4821']);
    }

    private function away(string $ip = self::AWAY): static
    {
        return $this->withHeader('CF-Connecting-IP', $ip);
    }

    private function unlockCookie(): string
    {
        $response = $this->away()->post('/unlock', ['pin' => '4821', 'to' => '/']);
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === AccessGate::COOKIE);
        $this->assertNotNull($cookie);

        return $cookie->getValue();
    }

    public function test_home_ip_skips_the_pin(): void
    {
        $this->withHeader('CF-Connecting-IP', self::HOME)->get('/')->assertOk();
    }

    public function test_lan_requests_are_not_checked(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_away_visitor_is_sent_to_the_pin_page(): void
    {
        $this->away()->get('/modules/youtube-music/settings?x=1')
            ->assertRedirect('/unlock?to='.urlencode('/modules/youtube-music/settings?x=1'));

        $this->away()->get('/unlock')->assertOk()->assertSee('Enter PIN');
    }

    public function test_away_writes_without_cookie_are_forbidden(): void
    {
        $this->away()->post('/modules/youtube-music/playlists', ['link' => 'PLabcdefghij123'])->assertForbidden();
    }

    public function test_right_pin_sets_a_secure_cookie_and_returns_to_the_page(): void
    {
        $response = $this->away()->post('/unlock', ['pin' => '4821', 'to' => '/modules/youtube-music/settings']);

        $response->assertRedirect('/modules/youtube-music/settings');
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === AccessGate::COOKIE);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 5);

        $this->away()->withUnencryptedCookie(AccessGate::COOKIE, $cookie->getValue())->get('/')->assertOk();
    }

    public function test_unlock_only_redirects_to_local_paths(): void
    {
        $this->away()->post('/unlock', ['pin' => '4821', 'to' => '//evil.example/x'])->assertRedirect('/');
        $this->away()->post('/unlock', ['pin' => '4821', 'to' => 'https://evil.example'])->assertRedirect('/');
    }

    public function test_forged_cookie_is_rejected(): void
    {
        $this->away()->withUnencryptedCookie(AccessGate::COOKIE, 'nope')->get('/')->assertRedirect();
    }

    public function test_changing_the_pin_signs_everyone_out(): void
    {
        $value = $this->unlockCookie();

        config(['dashboard.pin' => '9999']);

        $this->away()->withUnencryptedCookie(AccessGate::COOKIE, $value)->get('/')->assertRedirect();
    }

    public function test_expired_cookie_is_rejected(): void
    {
        $value = $this->unlockCookie();

        $this->travel(31)->days();

        $this->away()->withUnencryptedCookie(AccessGate::COOKIE, $value)->get('/')->assertRedirect();
    }

    public function test_wrong_pin_is_rate_limited_to_five_a_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->away()->post('/unlock', ['pin' => '0000'])->assertStatus(422)->assertSee('isn’t right', false);
        }

        $this->away()->post('/unlock', ['pin' => '4821'])->assertStatus(429)->assertSee('Slow down');

        // Another visitor is unaffected.
        $this->away('192.0.2.99')->post('/unlock', ['pin' => '4821'])->assertRedirect('/');
    }

    public function test_ten_failures_lock_the_ip_out_for_fifteen_minutes(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->travel(1)->minutes(); // stay under the per-minute limit
            $response = $this->away()->post('/unlock', ['pin' => '0000']);
        }

        $response->assertStatus(429)->assertSee('Too many tries');

        $this->travel(1)->minutes();
        $this->away()->get('/unlock')->assertStatus(429)->assertSee('Try again in');
        $this->away()->post('/unlock', ['pin' => '4821'])->assertStatus(429);

        $this->travel(15)->minutes();
        $this->away()->post('/unlock', ['pin' => '4821'])->assertRedirect('/');
    }

    public function test_no_pin_means_away_visitors_get_403(): void
    {
        config(['dashboard.pin' => '']);

        $this->away()->get('/')->assertForbidden();
        $this->away()->get('/unlock')->assertOk()->assertSee('Not available');
        $this->away()->post('/unlock', ['pin' => ''])->assertForbidden();
    }

    public function test_global_limit_is_120_requests_a_minute_per_ip(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->get('/up')->assertOk();
        }

        $this->get('/up')->assertStatus(429)->assertSee('http-equiv="refresh" content="30"', false);
        $this->away(self::HOME)->get('/up')->assertOk();
    }

    public function test_the_home_address_shares_a_much_higher_limit(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->away(self::HOME)->get('/up')->assertOk();
        }
    }
}
