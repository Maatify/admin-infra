<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDecisionResultDTO;
use Maatify\AdminInfra\Contracts\Authorization\Enum\AbilityDecisionReasonEnum;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbilityDecisionResultDTOTest extends TestCase
{
    public function testCanBeConstructedWithReason(): void
    {
        $dto = new AbilityDecisionResultDTO(AbilityDecisionReasonEnum::ALLOWED);
        $this->assertSame(AbilityDecisionReasonEnum::ALLOWED, $dto->reason);
    }

    public function testIsAllowedReturnsTrueOnlyForAllowed(): void
    {
        $allowedDto = new AbilityDecisionResultDTO(AbilityDecisionReasonEnum::ALLOWED);
        $this->assertTrue($allowedDto->isAllowed());
    }

    public function testIsAllowedReturnsFalseForOtherReasons(): void
    {
        $deniedReasons = array_filter(
            AbilityDecisionReasonEnum::cases(),
            fn($case) => $case !== AbilityDecisionReasonEnum::ALLOWED
        );

        foreach ($deniedReasons as $reason) {
            $dto = new AbilityDecisionResultDTO($reason);
            $this->assertFalse($dto->isAllowed(), "Expected isAllowed to be false for {$reason->name}");
        }
    }

    public function testPropertiesAreReadonly(): void
    {
        $reflection = new ReflectionClass(AbilityDecisionResultDTO::class);
        $property = $reflection->getProperty('reason');
        $this->assertTrue($property->isReadOnly());
    }
}
