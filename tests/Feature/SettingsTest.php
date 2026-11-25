<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Calendar\CalendarStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821']);
    }

    public function test_settings_pages_ask_for_the_pin_first(): void
    {
        $this->get('/settings')->assertRedirect('/settings/unlock?to=%2Fsettings');
        $this->get('/modules/calendar/settings')->assertRedirect('/settings/unlock?to=%2Fmodules%2Fcalendar%2Fsettings');
        $this->get('/modules/youtube-music/settings')->assertRedirect();
        $this->post('/modules/calendar/calendars', ['name' => 'X', 'color' => '#0071E3', 'url' => 'https://x.example/a.ics'])->assertRedirect();
        $this->assertSame([], app(CalendarStore::class)->all());

        $this->get('/settings/unlock')->assertOk()->assertSee('Enter the PIN');
    }

    public function test_the_right_pin_opens_settings_for_ten_minutes(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821', 'to' => '/modules/calendar/settings'])->assertRedirect('/modules/calendar/settings');

        $this->get('/settings')->assertOk()->assertSee('Calendars')->assertSee('Music playlists');
        $this->get('/modules/calendar/settings')->assertOk()->assertSee('Kitchen calendars');

        $this->travel(11)->minutes();
        $this->get('/settings')->assertRedirect();
    }

    public function test_activity_keeps_settings_open_and_lock_closes_them(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821']);

        $this->travel(8)->minutes();
        $this->get('/settings')->assertOk();
        $this->travel(8)->minutes();
        $this->get('/settings')->assertOk();

        $this->post('/settings/lock')->assertRedirect('/settings/unlock');
        $this->get('/settings')->assertRedirect();
    }

    public function test_five_wrong_pins_lock_out_for_a_minute(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/settings/unlock', ['pin' => '0000'])->assertStatus(422)->assertSee('That PIN isn’t right.', false);
        }
        $this->post('/settings/unlock', ['pin' => '0000'])->assertStatus(429)->assertSee('Too many tries');
        $this->post('/settings/unlock', ['pin' => '4821'])->assertStatus(429);

        $this->travel(61)->seconds();
        $this->post('/settings/unlock', ['pin' => '4821'])->assertRedirect('/settings');
    }

    public function test_only_local_targets_after_unlocking(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821', 'to' => '//evil.example'])->assertRedirect('/');
    }

    public function test_without_a_pin_settings_are_open(): void
    {
        config(['dashboard.pin' => '']);

        $this->get('/settings')->assertOk();
    }

    public function test_the_side_menu_has_settings_instead_of_home(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<nav class="side-menu"', 'id="menu-settings"', 'class="menu-divider"', 'id="habs-open"'], false)
            ->assertDontSee('id="menu-home"', false)
            ->assertSee('id="settings-frame"', false);
    }
}
