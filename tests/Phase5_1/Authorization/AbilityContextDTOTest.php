<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityContextDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityTargetDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class AbilityContextDTOTest extends TestCase
{
    public function testCanBeConstructedWithNulls(): void
    {
        // This is safe to test via instantiation as nulls don't require the dependency to exist/load
        $dto = new AbilityContextDTO(null, null);
        $this->assertNull($dto->impersonatorAdminId);
        $this->assertNull($dto->target);
    }

    public function testConstructorSignature(): void
    {
        $reflection = new ReflectionClass(AbilityContextDTO::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);

        // Param 0: impersonatorAdminId
        $param0 = $parameters[0];
        $this->assertEquals('impersonatorAdminId', $param0->getName());
        $type0 = $param0->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type0);
        $this->assertEquals(AdminIdDTO::class, $type0->getName());
        $this->assertTrue($type0->allowsNull());

        // Param 1: target
        $param1 = $parameters[1];
        $this->assertEquals('target', $param1->getName());
        $type1 = $param1->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type1);
        $this->assertEquals(AbilityTargetDTO::class, $type1->getName());
        $this->assertTrue($type1->allowsNull());
    }

    public function testPropertiesAreReadonly(): void
    {
        $reflection = new ReflectionClass(AbilityContextDTO::class);

        $this->assertTrue($reflection->getProperty('impersonatorAdminId')->isReadOnly());
        $this->assertTrue($reflection->getProperty('target')->isReadOnly());
    }

    public function testNoExtraPublicProperties(): void
    {
        $reflection = new ReflectionClass(AbilityContextDTO::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $expectedProperties = ['impersonatorAdminId', 'target'];
        $actualProperties = array_map(fn($p) => $p->getName(), $properties);

        sort($expectedProperties);
        sort($actualProperties);

        $this->assertEquals($expectedProperties, $actualProperties);
    }
}
