<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\AbilityResolverInterface;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityContextDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDecisionResultDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbilityResolverInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(AbilityResolverInterface::class));
    }

    public function testCanMethodSignature(): void
    {
        $reflection = new ReflectionClass(AbilityResolverInterface::class);
        $method = $reflection->getMethod('can');

        $this->assertSame(3, $method->getNumberOfParameters());

        $parameters = $method->getParameters();

        $this->assertEquals(AdminIdDTO::class, $parameters[0]->getType()->getName());
        $this->assertEquals('actorAdminId', $parameters[0]->getName());

        $this->assertEquals(AbilityDTO::class, $parameters[1]->getType()->getName());
        $this->assertEquals('ability', $parameters[1]->getName());

        $this->assertEquals(AbilityContextDTO::class, $parameters[2]->getType()->getName());
        $this->assertEquals('context', $parameters[2]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals(AbilityDecisionResultDTO::class, $returnType->getName());
    }
}
