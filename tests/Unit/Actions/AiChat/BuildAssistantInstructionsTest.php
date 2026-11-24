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
        $this->assertStringContainsString('get_weather', $instructions);
        $this->assertStringContainsString('French', $instructions);
    }

    public function test_family_safe_mode_adds_rules_for_children_that_cannot_be_overridden(): void
    {
        $instructions = (new BuildAssistantInstructions)->handle('Sainte-Catherine, Quebec', familySafe: true);

        $this->assertStringContainsString('possibly a young child', $instructions);
        $this->assertStringContainsString('sexual', $instructions);
        $this->assertStringContainsString('dating', $instructions);
        $this->assertStringContainsString('No swearing', $instructions);
        $this->assertStringContainsString('in every language', $instructions);
        $this->assertStringContainsString('role-play', $instructions);
        $this->assertStringContainsString('911', $instructions);
    }

    public function test_without_family_safe_mode_the_child_rules_are_left_out(): void
    {
        $this->assertStringNotContainsString('young child', (new BuildAssistantInstructions)->handle('Montreal', familySafe: false));
    }
}
