<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityTargetDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbilityTargetDTOTest extends TestCase
{
    public function testCanBeConstructedWithTargetTypeAndId(): void
    {
        $type = 'admin';
        $id = '123';
        $dto = new AbilityTargetDTO($type, $id);

        $this->assertSame($type, $dto->targetType);
        $this->assertSame($id, $dto->targetId);
    }

    public function testCanBeConstructedWithNullId(): void
    {
        $type = 'system';
        $dto = new AbilityTargetDTO($type, null);

        $this->assertSame($type, $dto->targetType);
        $this->assertNull($dto->targetId);
    }

    public function testPropertiesAreReadonly(): void
    {
        $reflection = new ReflectionClass(AbilityTargetDTO::class);

        $this->assertTrue($reflection->getProperty('targetType')->isReadOnly());
        $this->assertTrue($reflection->getProperty('targetId')->isReadOnly());
    }
}
