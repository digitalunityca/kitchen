<?php

declare(strict_types=1);

namespace App\Modules\AiChat;

/** OpenAI Realtime settings for the voice chat, from config/services.php and the module's block in config/dashboard.php. */
final class RealtimeSettings
{
    public function __construct(
        #[\SensitiveParameter] public readonly ?string $apiKey,
        public readonly string $model,
        public readonly string $voice,
        public readonly string $location,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== null && trim($this->apiKey) !== '';
    }

    /** Keep the key out of dumps and exception traces. */
    public function __debugInfo(): array
    {
        return ['apiKey' => $this->isConfigured() ? '[set]' : null, 'model' => $this->model, 'voice' => $this->voice, 'location' => $this->location];
    }
}
