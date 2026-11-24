<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Actions\Access\AttemptUnlock;
use App\Actions\Access\CheckUnlockLockout;
use App\Actions\Access\IssueUnlockCookie;
use App\Actions\Access\VerifyPin;
use App\Actions\Access\VerifyUnlockCookie;
use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use App\Http\Access\UnlockOutcome;
use Tests\TestCase;

class UnlockActionsTest extends TestCase
{
    private const IP = '192.0.2.1';

    private function pin(string $pin): void
    {
        $this->app->instance(AccessSettings::class, new AccessSettings([], $pin, 'test-key'));
    }

    public function test_verify_pin_needs_a_pin_to_be_set(): void
    {
        $this->pin('4821');
        $this->assertTrue($this->app->make(VerifyPin::class)->handle('4821'));
        $this->assertFalse($this->app->make(VerifyPin::class)->handle('0000'));

        $this->pin('');
        $this->assertFalse($this->app->make(VerifyPin::class)->handle(''));
    }

    public function test_issued_cookie_verifies_until_it_expires(): void
    {
        $this->pin('4821');
        $cookie = $this->app->make(IssueUnlockCookie::class)->handle();
        $verify = $this->app->make(VerifyUnlockCookie::class);

        $this->assertSame(AccessGate::COOKIE, $cookie->getName());
        $this->assertTrue($verify->handle($cookie->getValue()));

        $this->travel(AccessGate::COOKIE_DAYS + 1)->days();
        $this->assertFalse($verify->handle($cookie->getValue()));
    }

    public function test_cookie_is_rejected_after_the_pin_changes_or_is_removed(): void
    {
        $this->pin('4821');
        $value = $this->app->make(IssueUnlockCookie::class)->handle()->getValue();

        $this->pin('9999');
        $this->assertFalse($this->app->make(VerifyUnlockCookie::class)->handle($value));

        $this->pin('');
        $this->assertFalse($this->app->make(VerifyUnlockCookie::class)->handle($value));
    }

    public function test_garbage_cookies_are_rejected(): void
    {
        $this->pin('4821');
        $verify = $this->app->make(VerifyUnlockCookie::class);

        $this->assertFalse($verify->handle(null));
        $this->assertFalse($verify->handle(''));
        $this->assertFalse($verify->handle('nope'));
        $this->assertFalse($verify->handle(encrypt('not json', false)));
    }

    public function test_the_tenth_wrong_pin_locks_the_ip_out_and_a_right_pin_resets_the_count(): void
    {
        $this->pin('4821');
        $attempt = $this->app->make(AttemptUnlock::class);
        $lockout = $this->app->make(CheckUnlockLockout::class);

        for ($i = 1; $i < CheckUnlockLockout::MAX_FAILURES; $i++) {
            $this->assertSame(UnlockOutcome::WrongPin, $attempt->handle(self::IP, '0000'));
        }
        $this->assertSame(UnlockOutcome::Unlocked, $attempt->handle(self::IP, '4821'));
        $this->assertNull($lockout->handle(self::IP));

        for ($i = 1; $i < CheckUnlockLockout::MAX_FAILURES; $i++) {
            $attempt->handle(self::IP, '0000');
        }
        $this->assertSame(UnlockOutcome::LockedOut, $attempt->handle(self::IP, '0000'));
        $this->assertEqualsWithDelta(CheckUnlockLockout::LOCKOUT_SECONDS, $lockout->handle(self::IP), 2);
        $this->assertNull($lockout->handle('192.0.2.99'));
    }

    public function test_safe_target_only_allows_local_paths(): void
    {
        $this->assertSame('/modules/youtube-music/settings', AccessGate::safeTarget('/modules/youtube-music/settings'));
        $this->assertSame('/', AccessGate::safeTarget('//evil.example/x'));
        $this->assertSame('/', AccessGate::safeTarget('https://evil.example'));
        $this->assertSame('/', AccessGate::safeTarget('/\\evil.example'));
        $this->assertSame('/', AccessGate::safeTarget(['/']));
        $this->assertSame('/', AccessGate::safeTarget(null));
    }
}
