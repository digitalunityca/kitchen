<?php

namespace Tests;

use App\Models\TaskType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** @var array<string, array<string, mixed>|int> NHL API path => JSON body, or an HTTP status for a failure */
    protected array $nhl = [];

    protected function setUp(): void
    {
        parent::setUp();
        TaskType::flushIds();

        // No PIN unless a test sets one (a developer's .env may have HOME_HUB_PIN).
        config(['dashboard.pin' => '']);

        // The dashboard renders the Canadiens strip, so no test may reach the real
        // NHL API. Tests answer it through $nhl; anything else is a 503 (strip hidden).
        Http::fake(['api-web.nhle.com/*' => function (Request $request) {
            $path = str_replace('https://api-web.nhle.com/v1/', '', strtok($request->url(), '?'));
            $answer = $this->nhl[$path] ?? 503;

            return is_int($answer) ? Http::response([], $answer) : Http::response($answer);
        }]);
    }

    /** @return array<string, mixed> */
    protected function nhlFixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/Fixtures/nhl/'.$name.'.json'), true);
    }
}
