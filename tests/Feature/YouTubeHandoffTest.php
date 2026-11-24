<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouTubeHandoffTest extends TestCase
{
    private const HOME = '203.0.113.7';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.allowed_ips' => [self::HOME], 'dashboard.pin' => '4821']);
    }

    /** Post like the injected script: text/plain JSON from youtube.com, no CSRF token. */
    private function report(array $data, string $ip = self::HOME)
    {
        return $this->call('POST', '/modules/youtube-music/handoff', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
            'HTTP_ORIGIN' => 'https://www.youtube.com',
            'HTTP_CF_CONNECTING_IP' => $ip,
        ], json_encode($data));
    }

    public function test_youtube_tv_reports_and_the_card_reads_then_clears(): void
    {
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 83.6, 'list' => 'PLabcdefghij123'])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://www.youtube.com');

        $this->getJson('/modules/youtube-music/handoff')->assertOk()
            ->assertJsonPath('v', 'fPO76Jlnz6c')
            ->assertJsonPath('t', 83)
            ->assertJsonPath('list', 'PLabcdefghij123');

        $this->post('/modules/youtube-music/handoff/clear')->assertNoContent();
        $this->getJson('/modules/youtube-music/handoff')->assertOk()->assertExactJson([]);
    }

    public function test_a_handoff_older_than_ten_minutes_is_ignored(): void
    {
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 10, 'list' => null])->assertNoContent();

        $this->travel(11)->minutes();
        $this->getJson('/modules/youtube-music/handoff')->assertExactJson([]);
    }

    public function test_bad_reports_are_rejected(): void
    {
        $this->report(['v' => 'not-an-id', 't' => 10])->assertStatus(422);
        $this->report(['v' => 'fPO76Jlnz6c', 't' => -5])->assertStatus(422);
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 10, 'list' => '<script>'])->assertStatus(422);
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 10, 'list' => ''])->assertStatus(422);
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 86401])->assertStatus(422);
        $this->report(['v' => 'fPO76Jlnz6c'])->assertStatus(422);
        $this->call('POST', '/modules/youtube-music/handoff', [], [], [], ['HTTP_CF_CONNECTING_IP' => self::HOME], 'nope')->assertStatus(422);

        $this->getJson('/modules/youtube-music/handoff')->assertExactJson([]);
    }

    public function test_visitors_outside_home_without_the_pin_cannot_report(): void
    {
        $this->report(['v' => 'fPO76Jlnz6c', 't' => 10], '192.0.2.1')->assertForbidden();
    }

    public function test_reports_are_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->report(['v' => 'fPO76Jlnz6c', 't' => $i])->assertNoContent();
        }

        $this->report(['v' => 'fPO76Jlnz6c', 't' => 31])->assertStatus(429);
    }

    public function test_dashboard_installs_the_youtube_inject_script(): void
    {
        $this->withHeader('CF-Connecting-IP', self::HOME)->get('/')->assertOk()
            ->assertSee('injectJsCode', false)
            ->assertSee('www.youtube.com', false)
            ->assertSee(str_replace('/', '\\/', route('modules.youtube-music.handoff.store')), false)
            ->assertDontSee('__HANDOFF_URL__');
    }
}
