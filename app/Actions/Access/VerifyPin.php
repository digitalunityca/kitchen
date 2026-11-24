<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Http\Access\AccessSettings;

class VerifyPin
{
    public function __construct(private readonly AccessSettings $settings) {}

    /** True only when a PIN is set and the attempt matches it (constant-time compare). */
    public function handle(string $attempt): bool
    {
        return $this->settings->pinEnabled() && hash_equals($this->settings->pin, $attempt);
    }
}
