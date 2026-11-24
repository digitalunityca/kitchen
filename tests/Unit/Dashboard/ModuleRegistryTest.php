<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Dashboard\ModuleRegistry;
use App\Modules\Calendar\CalendarModule;
use App\Modules\Clock\ClockModule;
use App\Modules\Weather\WeatherModule;
use InvalidArgumentException;
use stdClass;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_it_builds_enabled_modules_in_config_order_keyed_by_module_key(): void
    {
        config(['dashboard.modules' => [
            CalendarModule::class => ['column' => '3'],
            WeatherModule::class => ['enabled' => false],
            ClockModule::class => [],
        ]]);

        $modules = (new ModuleRegistry)->all();

        $this->assertSame(['calendar', 'clock'], $modules->keys()->all());
        $this->assertSame('3', $modules['calendar']->column());
        $this->assertSame('auto', $modules['clock']->row());
        $this->assertNull((new ModuleRegistry)->get('weather'));
    }

    public function test_a_class_that_is_not_a_module_is_refused(): void
    {
        config(['dashboard.modules' => [stdClass::class => []]]);

        $this->expectException(InvalidArgumentException::class);

        (new ModuleRegistry)->all();
    }
}
