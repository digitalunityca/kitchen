<?php

namespace App\Dashboard;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Router;

/**
 * Base class for a dashboard module (a card on the kitchen screen).
 *
 * To add a module: run `php artisan make:module Weather`, then add its class to
 * config/dashboard.php under "modules". Each module owns its card view, its
 * optional routes (mounted under /modules/{key}) and its own config block.
 */
abstract class Module
{
    /** @param array<string, mixed> $config the module's block from config/dashboard.php */
    public function __construct(protected array $config = []) {}

    /** URL-safe id, e.g. "youtube-music". Used for routes, views and DOM ids. */
    abstract public function key(): string;

    /** Human name shown in admin screens. */
    abstract public function name(): string;

    /**
     * Data handed to the card view.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [];
    }

    /** The card's Blade view. Defaults to resources/views/modules/{key}/card.blade.php */
    public function view(): string
    {
        return 'modules.'.$this->key().'.card';
    }

    /**
     * Container bindings for the module's services (stores, settings), so
     * controllers and actions get them injected instead of asking the registry.
     * Bind with closures that read config when resolved, not at boot.
     */
    public function register(Container $app): void {}

    /** Extra routes for this module, mounted under /modules/{key} with name prefix "modules.{key}.". */
    public function routes(Router $router): void {}

    /**
     * Where the card sits on the 3x2 grid, as CSS grid-area values.
     * Override per install in config/dashboard.php with 'column' and 'row'.
     */
    public function column(): string
    {
        return $this->config['column'] ?? 'auto';
    }

    public function row(): string
    {
        return $this->config['row'] ?? 'auto';
    }

    /** "grid" for a card on the grid, "ticker" for a strip above it, "menu" for a button in the side menu. */
    public function placement(): string
    {
        return $this->config['placement'] ?? 'grid';
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function render(): View
    {
        return view($this->view(), array_merge($this->data(), ['module' => $this]));
    }
}
