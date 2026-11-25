<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HomeworkSheetSizeTest extends TestCase
{
    public function test_the_homework_sheet_covers_the_side_menu(): void
    {
        $css = (string) file_get_contents(public_path('css/dashboard.css'));

        $this->assertStringContainsString('body .kid-overlay { padding: 16px; z-index: 80; }', $css, 'above the side menu (z-index 40), with only a small margin');
        $this->assertStringContainsString('body .kid-sheet { width: 100%; }', $css);
    }
}
