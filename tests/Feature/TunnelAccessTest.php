<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TunnelAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '']);
    }

    public function test_lan_requests_are_not_checked(): void
    {
        config(['dashboard.allowed_ips' => ['203.0.113.7']]);

        $this->get('/')->assertOk();
    }

    public function test_allowed_tunnel_visitor_gets_in(): void
    {
        config(['dashboard.allowed_ips' => ['203.0.113.7', '198.51.100.0/24']]);

        $this->withHeader('CF-Connecting-IP', '203.0.113.7')->get('/')->assertOk();
        $this->withHeader('CF-Connecting-IP', '198.51.100.42')->get('/')->assertOk();
    }

    public function test_other_tunnel_visitors_get_403(): void
    {
        config(['dashboard.allowed_ips' => ['203.0.113.7']]);

        $this->withHeader('CF-Connecting-IP', '192.0.2.1')->get('/')->assertForbidden();
        $this->withHeader('CF-Connecting-IP', '192.0.2.1')
            ->post('/modules/youtube-music/playlists', ['link' => 'PLabcdefghij123'])
            ->assertForbidden();
    }

    public function test_health_check_allows_cors_for_the_offline_page(): void
    {
        config(['dashboard.allowed_ips' => ['203.0.113.7']]);

        $this->withHeader('CF-Connecting-IP', '203.0.113.7')->get('/up')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*');
        $this->get('/up')->assertHeader('Access-Control-Allow-Origin', '*');
        $this->get('/')->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_empty_allow_list_and_no_pin_lets_everyone_in_with_a_warning(): void
    {
        config(['dashboard.allowed_ips' => []]);
        Log::spy();

        $this->withHeader('CF-Connecting-IP', '192.0.2.1')->get('/')->assertOk();

        Log::shouldHaveReceived('warning')->once();
    }
}
