<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Dashboard\ModuleRegistry;
use App\Modules\Homework\HomeworkModule;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeworkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_each_kid_gets_a_button_under_home_and_a_homework_sheet(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<nav class="side-menu"', 'id="menu-home"', 'data-kid="theo"', 'data-kid="arthur"', 'id="habs-open"', '</nav>'], false)
            ->assertSee('Theo’s homework', false)
            ->assertSee('Arthur’s homework', false)
            ->assertSee('Coming soon');
    }

    public function test_kids_come_from_config(): void
    {
        config(['dashboard.modules.'.HomeworkModule::class.'.kids' => [['name' => 'Léa', 'badge' => '🦄', 'color' => '#AF52DE']]]);
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->get('/')->assertOk()->assertSee('data-kid="lea"', false)->assertSee('🦄')->assertDontSee('data-kid="theo"', false);
    }
}
