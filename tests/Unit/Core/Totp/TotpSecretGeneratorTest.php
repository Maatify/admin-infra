<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Core\Totp;

use Maatify\AdminInfra\Core\Totp\TotpSecretGenerator;
use PHPUnit\Framework\TestCase;

final class TotpSecretGeneratorTest extends TestCase
{
    private TotpSecretGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new TotpSecretGenerator();
    }

    public function testGenerateReturnsStringOfCorrectLength(): void
    {
        // Default bytes is 20.
        // Base32 encoding of 20 bytes results in ceil(20 * 8 / 5) = 32 chars.
        $secret = $this->generator->generate();
        $this->assertIsString($secret);
        $this->assertEquals(32, strlen($secret));
    }

    public function testGenerateWithCustomLength(): void
    {
        // 10 bytes -> ceil(10 * 8 / 5) = 16 chars.
        $secret = $this->generator->generate(10);
        $this->assertEquals(16, strlen($secret));
    }

    public function testGenerateReturnsBase32CharactersOnly(): void
    {
        $secret = $this->generator->generate();
        $this->assertMatchesRegularExpression('/^[A-Z2-7=]+$/', $secret);
    }

    public function testGenerateWithPadding(): void
    {
        // 1 byte -> 8 bits. 8 bits / 5 = 1.6 blocks.
        // Block size 1. Padding needed.
        $secret = $this->generator->generate(1);

        // 1 byte -> 8 bits -> 2 base32 chars + 6 '=' padding?
        // Base32 blocks are 40 bits (5 bytes) -> 8 chars.
        // 1 byte input.
        // 8 bits. 8/5 = 1 remainder 3.
        // First 5 bits -> 1 char. Remaining 3 bits -> 1 char.
        // Total 2 chars.
        // Padding: 8 - 2 = 6 '='.

        $this->assertEquals(8, strlen($secret));
        $this->assertStringEndsWith('======', $secret);
    }
}
