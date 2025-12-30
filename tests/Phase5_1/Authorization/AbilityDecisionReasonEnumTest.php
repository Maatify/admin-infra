<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\Enum\AbilityDecisionReasonEnum;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

class AbilityDecisionReasonEnumTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(AbilityDecisionReasonEnum::class));
    }

    public function testEnumIsStringBacked(): void
    {
        $reflection = new ReflectionEnum(AbilityDecisionReasonEnum::class);
        $this->assertTrue($reflection->isBacked());
        $this->assertEquals('string', $reflection->getBackingType()->getName());
    }

    public function testEnumCasesExhaustiveness(): void
    {
        $expected = [
            'ALLOWED' => 'allowed',
            'DENIED_NO_PERMISSION' => 'denied_no_permission',
            'DENIED_IMPERSONATION_FORBIDDEN' => 'denied_impersonation_forbidden',
            'DENIED_HIERARCHY_VIOLATION' => 'denied_hierarchy_violation',
            'DENIED_SYSTEM_RESTRICTION' => 'denied_system_restriction',
        ];

        $cases = AbilityDecisionReasonEnum::cases();
        $this->assertCount(count($expected), $cases);

        foreach ($cases as $case) {
            $this->assertArrayHasKey($case->name, $expected);
            $this->assertEquals($expected[$case->name], $case->value);
        }
    }
}
