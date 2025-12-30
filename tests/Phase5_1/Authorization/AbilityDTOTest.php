<?php
declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase5_1\Authorization;

use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbilityDTOTest extends TestCase
{
    public function testCanBeConstructedWithKey(): void
    {
        $key = 'test_ability';
        $dto = new AbilityDTO($key);
        $this->assertSame($key, $dto->key);
    }

    public function testPropertiesAreReadonly(): void
    {
        $reflection = new ReflectionClass(AbilityDTO::class);
        $property = $reflection->getProperty('key');
        $this->assertTrue($property->isReadOnly());
    }
}
