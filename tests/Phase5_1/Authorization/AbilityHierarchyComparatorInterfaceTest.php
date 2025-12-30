<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\AbilityHierarchyComparatorInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

class AbilityHierarchyComparatorInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(AbilityHierarchyComparatorInterface::class));
    }

    public function testCanActOnMethodSignature(): void
    {
        $reflection = new ReflectionClass(AbilityHierarchyComparatorInterface::class);
        $method = $reflection->getMethod('canActOn');

        $this->assertSame(2, $method->getNumberOfParameters());

        $parameters = $method->getParameters();

        $param0 = $parameters[0];
        $type0 = $param0->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type0);
        $this->assertEquals(AdminIdDTO::class, $type0->getName());
        $this->assertEquals('actor', $param0->getName());

        $param1 = $parameters[1];
        $type1 = $param1->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type1);
        $this->assertEquals(AdminIdDTO::class, $type1->getName());
        $this->assertEquals('target', $param1->getName());

        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertEquals('bool', $returnType->getName());
    }
}
