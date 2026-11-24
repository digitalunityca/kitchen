<?php

namespace App\Modules\AiChat;

use App\Dashboard\Module;

class AiChatModule extends Module
{
    public function key(): string
    {
        return 'ai-chat';
    }

    public function name(): string
    {
        return 'AI Chat';
    }

    public function data(): array
    {
        return ['url' => $this->config('url', '#')];
    }
}
