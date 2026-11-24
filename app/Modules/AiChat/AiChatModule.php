<?php

namespace App\Modules\AiChat;

use App\Dashboard\Module;
use App\Dashboard\ModuleRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;

/**
 * "Ask ChatGPT": a mic button in the side menu for voice chat over OpenAI's Realtime API (WebRTC from the
 * tablet, a one-minute client secret from App\Actions\AiChat\CreateRealtimeSession).
 * Needs OPENAI_API_KEY in .env; without it the tile says so.
 */
class AiChatModule extends Module
{
    public function key(): string
    {
        return 'ai-chat';
    }

    public function name(): string
    {
        return 'Ask ChatGPT';
    }

    /** A mic button in the side menu; the conversation happens on the button itself. */
    public function placement(): string
    {
        return 'menu';
    }

    public function register(Container $app): void
    {
        $app->bind(RealtimeSettings::class, function (Container $app): RealtimeSettings {
            $module = $app->make(ModuleRegistry::class)->get($this->key());

            return new RealtimeSettings(
                config('services.openai.key'),
                (string) config('services.openai.realtime_model', 'gpt-realtime-2.1'),
                (string) config('services.openai.realtime_voice', 'marin'),
                (string) ($module?->config('location') ?? 'Canada'),
                (bool) ($module?->config('family_safe') ?? true),
                (string) config('services.openai.search_model', 'gpt-5-mini'),
            );
        });
    }

    /** @return array{configured: bool, familySafe: bool} */
    public function data(): array
    {
        $settings = app(RealtimeSettings::class);

        return ['configured' => $settings->isConfigured(), 'familySafe' => $settings->familySafe];
    }

    public function routes(Router $router): void
    {
        $router->post('session', [AiChatController::class, 'session'])
            ->middleware('throttle:10,1')->name('session');
        $router->post('moderate', [AiChatController::class, 'moderate'])
            ->middleware('throttle:60,1')->name('moderate');
        $router->post('search', [AiChatController::class, 'search'])
            ->middleware('throttle:20,1')->name('search');
        $router->get('weather', [AiChatController::class, 'weather'])
            ->middleware('throttle:30,1')->name('weather');
    }
}
