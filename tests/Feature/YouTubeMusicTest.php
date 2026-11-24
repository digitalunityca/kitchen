<?php

namespace Tests\Feature;

use App\Modules\YouTubeMusic\PlaylistStore;
use App\Modules\YouTubeMusic\YouTubeMusicModule;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouTubeMusicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_dashboard_shows_empty_state_then_added_playlist(): void
    {
        $this->get('/')->assertOk()->assertSee('Add your first playlist');

        $this->post('/modules/youtube-music/playlists', [
            'link' => 'https://music.youtube.com/playlist?list=PLabcdefghij123',
            'name' => 'Dinner jazz',
        ])->assertRedirect('/modules/youtube-music/settings');

        $this->get('/')->assertOk()->assertSee('Dinner jazz')->assertSee('data-list="PLabcdefghij123"', false);
    }

    public function test_premium_button_carries_the_tv_user_agent_from_config(): void
    {
        $ua = config('dashboard.modules.'.YouTubeMusicModule::class.'.tv_user_agent');
        $this->assertStringContainsString('SMART-TV', $ua);

        $this->get('/')->assertOk()
            ->assertSee('href="https://www.youtube.com/tv"', false)
            ->assertSee('data-tv-ua="'.e($ua).'"', false);
    }

    public function test_it_rejects_links_without_a_playlist(): void
    {
        $this->from('/modules/youtube-music/settings')
            ->post('/modules/youtube-music/playlists', ['link' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'])
            ->assertSessionHasErrors('link');
    }

    public function test_remove_and_reorder(): void
    {
        $store = app(PlaylistStore::class);
        $store->save([['name' => 'A', 'id' => 'PLaaaaaaaaaa'], ['name' => 'B', 'id' => 'PLbbbbbbbbbb']]);

        $this->post('/modules/youtube-music/playlists/1/up')->assertRedirect();
        $this->assertSame(['B', 'A'], array_column($store->all(), 'name'));

        $this->post('/modules/youtube-music/playlists/0/delete')->assertRedirect();
        $this->assertSame(['A'], array_column($store->all(), 'name'));
    }

    public function test_make_module_command_scaffolds_a_module(): void
    {
        $this->artisan('make:module', ['name' => 'ShoppingList'])->assertSuccessful();
        $this->assertFileExists(app_path('Modules/ShoppingList/ShoppingListModule.php'));
        $this->assertFileExists(resource_path('views/modules/shopping-list/card.blade.php'));

        unlink(app_path('Modules/ShoppingList/ShoppingListModule.php'));
        rmdir(app_path('Modules/ShoppingList'));
        unlink(resource_path('views/modules/shopping-list/card.blade.php'));
        rmdir(resource_path('views/modules/shopping-list'));
    }

    public function test_the_card_has_a_volume_slider_that_uses_fullys_system_volume(): void
    {
        app(PlaylistStore::class)->save([['name' => 'A', 'id' => 'PLaaaaaaaaaa']]);

        $this->get('/')->assertOk()
            ->assertSee('id="ytm-vol"', false)
            ->assertSee('role="slider"', false)
            ->assertSee('fully.setAudioVolume(level, 3)', false)
            ->assertSee('id="ytm-vol-mute"', false);
    }
}
