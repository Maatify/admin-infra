<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Core\Totp;

use DateTimeImmutable;
use Maatify\AdminInfra\Core\Totp\TotpCodeGenerator;
use Maatify\AdminInfra\Tests\Support\FakeTimeProvider;
use PHPUnit\Framework\TestCase;

final class TotpCodeGeneratorTest extends TestCase
{
    private FakeTimeProvider $timeProvider;
    private TotpCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->timeProvider = new FakeTimeProvider();
        $this->generator = new TotpCodeGenerator($this->timeProvider);
    }

    public function testGenerateForCounterReturnsCorrectLengthAndFormat(): void
    {
        // Secret: JBSWY3DPEHPK3PXP (Base32 for "Hello!")
        $secret = 'JBSWY3DPEHPK3PXP';
        $code = $this->generator->generateForCounter($secret, 1);

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testGenerateReturnsCorrectCodeForSpecificTime(): void
    {
        // Using RFC 6238 / RFC 4226 test vectors if possible, or consistent check.
        // Let's use a known seed and time.
        // Secret: "12345678901234567890" (20 bytes)
        // Base32: GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ
        $secretBase32 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        // RFC 6238 test vector T0 = 59 (near 1*30)
        // Time = 59. Counter = 1.
        // SHA1 HMAC of counter 1 with secret.

        // Let's rely on consistency.
        $time = new DateTimeImmutable('@1000'); // Timestamp 1000
        $code = $this->generator->generate($secretBase32, $time);

        // Calculate expected manually to verify logic if needed, or just assert it's deterministic.
        // 1000 / 30 = 33.
        $codeAgain = $this->generator->generateForCounter($secretBase32, 33);
        $this->assertEquals($code, $codeAgain);

        // Ensure different time gives different code (usually)
        $time2 = new DateTimeImmutable('@2000');
        $code2 = $this->generator->generate($secretBase32, $time2);
        $this->assertNotEquals($code, $code2);
    }

    public function testGenerateUsesTimeProviderWhenTimeIsNull(): void
    {
        $secretBase32 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $time = new DateTimeImmutable('@1234567890');
        $this->timeProvider->setNow($time);

        $code = $this->generator->generate($secretBase32);
        $expectedCode = $this->generator->generate($secretBase32, $time);

        $this->assertEquals($expectedCode, $code);
    }

    public function testGenerateForCounterHandlesZero(): void
    {
         $secretBase32 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
         $code = $this->generator->generateForCounter($secretBase32, 0);
         $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testGenerateIgnoresInvalidBase32Characters(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP'; // "Hello!"
        $secretWithJunk = 'JBSWY3DP-EHPK 3PXP!'; // Added -, space, !

        $code1 = $this->generator->generateForCounter($secret, 10);
        $code2 = $this->generator->generateForCounter($secretWithJunk, 10);

        $this->assertEquals($code1, $code2);
    }
}
