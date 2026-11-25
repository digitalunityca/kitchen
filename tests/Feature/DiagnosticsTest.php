<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DiagnosticsTest extends TestCase
{
    public function test_the_dashboard_logs_why_it_unloaded(): void
    {
        $path = storage_path('framework/testing/unload-'.uniqid().'.log');
        config(['logging.channels.unload.path' => $path]);

        $this->call('POST', '/diagnostics/unload', [], [], [], ['CONTENT_TYPE' => 'text/plain'], json_encode([
            'ranSeconds' => 1805.4, 'playing' => true, 'visibility' => 'visible', 'recent' => ['12s tap ytm-chip', '13s set actionBarTitle'],
        ]))->assertNoContent();

        $line = (string) file_get_contents($path);
        $this->assertStringContainsString('Dashboard page unloaded. {"ran_seconds":1805,"playing":true,"visibility":"visible","recent":["12s tap ytm-chip","13s set actionBarTitle"]}', $line);
        unlink($path);
    }

    public function test_garbage_is_ignored(): void
    {
        $this->call('POST', '/diagnostics/unload', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'nope')->assertNoContent();
    }

    public function test_the_dashboard_sends_the_unload_beacon(): void
    {
        $this->get('/')->assertSee('navigator.sendBeacon', false)->assertSee('diagnostics\/unload', false);
    }
}
