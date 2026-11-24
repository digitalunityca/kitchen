<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VolumeTest extends TestCase
{
    public function test_the_volume_slider_sits_in_the_side_menu_under_the_chatgpt_mic(): void
    {
        Storage::fake('local');

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<nav class="side-menu"', 'id="habs-open"', 'id="aic-fab"', 'id="vol-slider"', 'id="vol-mute"', '</nav>'], false)
            ->assertSee('role="slider"', false)
            ->assertSee('fully.setAudioVolume(level, 3)', false)
            ->assertDontSee('id="ytm-vol"', false);
    }
}
