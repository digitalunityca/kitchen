<?php

declare(strict_types=1);

namespace App\Modules\AiChat;

use RuntimeException;

/** The voice chat can't start: no API key, or OpenAI refused or didn't answer. */
final class RealtimeUnavailable extends RuntimeException
{
    public const NOT_CONFIGURED = 'not_configured';

    public const UPSTREAM = 'upstream';

    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self(self::NOT_CONFIGURED, 'OPENAI_API_KEY is not set.');
    }

    public static function upstream(string $message): self
    {
        return new self(self::UPSTREAM, $message);
    }
}
