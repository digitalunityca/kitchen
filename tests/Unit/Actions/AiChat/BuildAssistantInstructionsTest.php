<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AiChat;

use App\Actions\AiChat\BuildAssistantInstructions;
use PHPUnit\Framework\TestCase;

class BuildAssistantInstructionsTest extends TestCase
{
    public function test_the_persona_is_a_short_spoken_metric_kitchen_helper_for_the_location(): void
    {
        $instructions = (new BuildAssistantInstructions)->handle('Sainte-Catherine, Quebec');

        $this->assertStringContainsString('Sainte-Catherine, Quebec', $instructions);
        $this->assertStringContainsString('metric', $instructions);
        $this->assertStringContainsString('Celsius', $instructions);
        $this->assertStringContainsString('substitutions', $instructions);
        $this->assertStringContainsString('interrupted', $instructions);
        $this->assertStringContainsString('French', $instructions);
    }
}
