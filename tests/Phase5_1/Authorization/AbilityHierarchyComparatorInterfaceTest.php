<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\AbilityHierarchyComparatorInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

        $this->assertEquals(AdminIdDTO::class, $parameters[0]->getType()->getName());
        $this->assertEquals('actor', $parameters[0]->getName());

        $this->assertEquals(AdminIdDTO::class, $parameters[1]->getType()->getName());
        $this->assertEquals('target', $parameters[1]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }
}
