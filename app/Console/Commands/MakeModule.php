<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : Module name in StudlyCase, e.g. Weather or ShoppingList}';

    protected $description = 'Create a dashboard module (class + card view) and show how to turn it on';

    public function handle(Filesystem $files): int
    {
        $class = Str::studly($this->argument('name'));
        $key = Str::kebab($class);
        $classPath = app_path("Modules/$class/{$class}Module.php");
        $viewPath = resource_path("views/modules/$key/card.blade.php");

        if ($files->exists($classPath)) {
            $this->error("Module $class already exists.");

            return self::FAILURE;
        }

        $replace = ['{{ class }}' => $class, '{{ key }}' => $key, '{{ name }}' => Str::headline($class)];
        foreach ([[base_path('stubs/module/module.stub'), $classPath], [base_path('stubs/module/card.stub'), $viewPath]] as [$stub, $target]) {
            $files->ensureDirectoryExists(dirname($target));
            $files->put($target, strtr($files->get($stub), $replace));
            $this->components->info('Created '.str_replace(base_path().'/', '', $target));
        }

        $this->newLine();
        $this->line('Turn it on in config/dashboard.php:');
        $this->line("    App\\Modules\\$class\\{$class}Module::class => ['column' => '1', 'row' => '1'],");

        return self::SUCCESS;
    }
}
