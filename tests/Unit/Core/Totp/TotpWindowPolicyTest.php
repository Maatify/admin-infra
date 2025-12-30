<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Core\Totp;

use Maatify\AdminInfra\Core\Totp\TotpWindowPolicy;
use PHPUnit\Framework\TestCase;

final class TotpWindowPolicyTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $policy = new TotpWindowPolicy(2, 3);
        $this->assertEquals(2, $policy->pastWindows);
        $this->assertEquals(3, $policy->futureWindows);
    }

    public function testConstructorEnforcesNonNegativeValues(): void
    {
        $policy = new TotpWindowPolicy(-1, -5);
        $this->assertEquals(0, $policy->pastWindows);
        $this->assertEquals(0, $policy->futureWindows);
    }

    public function testDefaults(): void
    {
        $policy = new TotpWindowPolicy();
        $this->assertEquals(1, $policy->pastWindows);
        $this->assertEquals(1, $policy->futureWindows);
    }
}
