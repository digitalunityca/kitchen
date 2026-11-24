<?php

namespace App\Modules\AiChat;

use App\Dashboard\Module;
use App\Dashboard\ModuleRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;

/**
 * "Ask ChatGPT": a floating mic button for voice chat over OpenAI's Realtime API (WebRTC from the
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

    /** Always the floating mic button; the conversation happens on the button itself. */
    public function placement(): string
    {
        return 'floating';
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
            );
        });
    }

    /** @return array{configured: bool} */
    public function data(): array
    {
        return ['configured' => app(RealtimeSettings::class)->isConfigured()];
    }

    public function routes(Router $router): void
    {
        $router->post('session', [AiChatController::class, 'session'])
            ->middleware('throttle:10,1')->name('session');
    }
}
