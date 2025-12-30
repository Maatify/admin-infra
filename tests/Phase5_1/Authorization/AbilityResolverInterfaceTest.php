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
use ReflectionNamedType;

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

        $param0 = $parameters[0];
        $type0 = $param0->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type0);
        $this->assertEquals(AdminIdDTO::class, $type0->getName());
        $this->assertEquals('actorAdminId', $param0->getName());

        $param1 = $parameters[1];
        $type1 = $param1->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type1);
        $this->assertEquals(AbilityDTO::class, $type1->getName());
        $this->assertEquals('ability', $param1->getName());

        $param2 = $parameters[2];
        $type2 = $param2->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type2);
        $this->assertEquals(AbilityContextDTO::class, $type2->getName());
        $this->assertEquals('context', $param2->getName());

        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertEquals(AbilityDecisionResultDTO::class, $returnType->getName());
    }
}
