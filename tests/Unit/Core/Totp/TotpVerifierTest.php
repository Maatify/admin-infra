<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Core\Totp;

use DateTimeImmutable;
use Maatify\AdminInfra\Core\Totp\TotpCodeGenerator;
use Maatify\AdminInfra\Core\Totp\TotpVerifier;
use Maatify\AdminInfra\Core\Totp\TotpWindowPolicy;
use Maatify\AdminInfra\Tests\Support\FakeTimeProvider;
use PHPUnit\Framework\TestCase;

final class TotpVerifierTest extends TestCase
{
    private FakeTimeProvider $timeProvider;
    private TotpCodeGenerator $codeGenerator;
    private TotpVerifier $verifier;
    private string $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        $this->timeProvider = new FakeTimeProvider();
        $this->codeGenerator = new TotpCodeGenerator($this->timeProvider);
        // Default policy: 1 past, 1 future
        $policy = new TotpWindowPolicy(1, 1);
        $this->verifier = new TotpVerifier($this->codeGenerator, $policy, $this->timeProvider);
    }

    public function testVerifyValidCodeInCurrentWindow(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        $code = $this->codeGenerator->generate($this->secret, $now);
        $result = $this->verifier->verify($this->secret, $code);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isExpired);
    }

    public function testVerifyValidCodeInPastWindow(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        // Past window (1 window back = 30 seconds)
        $pastTime = $now->modify('-30 seconds');
        $code = $this->codeGenerator->generate($this->secret, $pastTime);

        $result = $this->verifier->verify($this->secret, $code);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isExpired);
    }

    public function testVerifyValidCodeInFutureWindow(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        // Future window (1 window forward = 30 seconds)
        $futureTime = $now->modify('+30 seconds');
        $code = $this->codeGenerator->generate($this->secret, $futureTime);

        $result = $this->verifier->verify($this->secret, $code);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isExpired);
    }

    public function testVerifyExpiredCode(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        // Expired (2 windows back = 60 seconds). Policy allows 1 past.
        // But logic says check `expiredCounter = current - (past + 1)` which is -2.
        // The expired check in Verifier checks specifically for `currentCounter - (pastWindows + 1)`.
        $expiredTime = $now->modify('-60 seconds');
        $code = $this->codeGenerator->generate($this->secret, $expiredTime);

        $result = $this->verifier->verify($this->secret, $code);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->isExpired);
    }

    public function testVerifyTooOldCodeNotExpiredJustInvalid(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        // Way past (3 windows back = 90 seconds).
        $wayPastTime = $now->modify('-90 seconds');
        $code = $this->codeGenerator->generate($this->secret, $wayPastTime);

        $result = $this->verifier->verify($this->secret, $code);

        $this->assertFalse($result->isValid);
        $this->assertFalse($result->isExpired); // Should be false because it's older than the "expired" window check (which checks specific window).
    }

    public function testVerifyInvalidCode(): void
    {
        $now = new DateTimeImmutable('@1000');
        $this->timeProvider->setNow($now);

        $result = $this->verifier->verify($this->secret, '000000');

        $this->assertFalse($result->isValid);
        $this->assertFalse($result->isExpired);
    }

    public function testVerifyHandlesNegativeCountersGracefully(): void
    {
        // Set time to near epoch, e.g. 5 seconds.
        $now = new DateTimeImmutable('@5');
        $this->timeProvider->setNow($now);

        // Counter is 0. Past window is 1. Offset -1 -> Counter -1.
        // Should continue loop without error.

        $code = $this->codeGenerator->generate($this->secret, $now);
        $result = $this->verifier->verify($this->secret, $code);

        $this->assertTrue($result->isValid);
    }
}
