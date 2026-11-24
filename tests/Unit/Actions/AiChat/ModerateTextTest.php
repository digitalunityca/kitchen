<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AiChat;

use App\Actions\AiChat\ModerateText;
use App\Modules\AiChat\RealtimeSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ModerateTextTest extends TestCase
{
    private const KEY = 'sk-test-not-a-real-key';

    private function moderate(string $text, bool $familySafe = true, ?string $key = self::KEY): array
    {
        $this->app->instance(RealtimeSettings::class, new RealtimeSettings($key, 'gpt-realtime-2.1', 'marin', 'Sainte-Catherine, Quebec', $familySafe));

        return $this->app->make(ModerateText::class)->handle($text);
    }

    public function test_a_flagged_line_reports_its_categories(): void
    {
        Http::fake([ModerateText::URL => Http::response(['results' => [[
            'flagged' => true,
            'categories' => ['sexual' => true, 'violence' => false, 'harassment' => true],
        ]]])]);

        $this->assertSame(['flagged' => true, 'categories' => ['sexual', 'harassment'], 'checked' => true], $this->moderate('something unsuitable'));
        Http::assertSent(fn (Request $request): bool => $request['model'] === 'omni-moderation-latest'
            && $request['input'] === 'something unsuitable'
            && $request->hasHeader('Authorization', 'Bearer '.self::KEY));
    }

    public function test_a_normal_line_is_not_flagged(): void
    {
        Http::fake([ModerateText::URL => Http::response(['results' => [['flagged' => false, 'categories' => ['sexual' => false]]]])]);

        $this->assertSame(['flagged' => false, 'categories' => [], 'checked' => true], $this->moderate('How long do I boil an egg?'));
    }

    public function test_a_missing_moderations_permission_is_logged_once_without_the_key_and_the_chat_carries_on(): void
    {
        Log::spy();
        Http::fake([ModerateText::URL => Http::response(['error' => ['code' => 'insufficient_permissions', 'message' => 'Missing scopes']], 403)]);

        $this->assertFalse($this->moderate('first line')['checked']);
        $this->assertFalse($this->moderate('second line')['flagged']);

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['status'] === 403
            && $context['code'] === 'insufficient_permissions'
            && str_contains($context['hint'], 'Moderations')
            && ! str_contains(json_encode($context), self::KEY));
    }

    public function test_nothing_is_sent_when_family_safe_mode_is_off_or_there_is_no_key_or_text(): void
    {
        Http::fake();

        $this->assertFalse($this->moderate('anything', familySafe: false)['checked']);
        $this->assertFalse($this->moderate('anything', key: null)['checked']);
        $this->assertFalse($this->moderate('   ')['checked']);
        Http::assertNothingSent();
    }
}
