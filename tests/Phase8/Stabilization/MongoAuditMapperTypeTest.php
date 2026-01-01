<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Stabilization;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditMapper;
use PHPUnit\Framework\TestCase;

class MongoAuditMapperTypeTest extends TestCase
{
    private MongoAuditMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new MongoAuditMapper();
    }

    public function testMapAuthCastsToIntegerForNumericId(): void
    {
        $event = new AuditAuthEventDTO(
            'login',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapAuth($event);

        $this->assertNotNull($result);
        $this->assertIsInt($result->userId);
        $this->assertSame(123, $result->userId);
    }

    public function testMapAuthReturnsNullForNonNumericId(): void
    {
        $event = new AuditAuthEventDTO(
            'login',
            new AdminIdDTO('uuid-123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->assertNull($this->mapper->mapAuth($event));
    }

    public function testMapSecurityCastsToIntegerForNumericId(): void
    {
        $event = new AuditSecurityEventDTO(
            'alert',
            new AdminIdDTO('456'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapSecurity($event);

        $this->assertNotNull($result);
        $this->assertIsInt($result->userId);
        $this->assertSame(456, $result->userId);
    }

    public function testMapActionCastsToIntegerForNumericIds(): void
    {
        $event = new AuditActionDTO(
            'create',
            new AdminIdDTO('789'),
            'user',
            new AdminIdDTO('101'), // Target
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapAction($event);

        $this->assertNotNull($result);
        $this->assertIsInt($result->userId);
        $this->assertSame(789, $result->userId);

        $this->assertIsInt($result->refId);
        $this->assertSame(101, $result->refId);
    }

    public function testMapActionReturnsNullIfTargetIdNonNumeric(): void
    {
        $event = new AuditActionDTO(
            'create',
            new AdminIdDTO('789'),
            'user',
            new AdminIdDTO('uuid-target'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->assertNull($this->mapper->mapAction($event));
    }

    public function testMapActionWithNullTarget(): void
    {
        $event = new AuditActionDTO(
            'create',
            new AdminIdDTO('789'),
            null,
            null, // No target
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapAction($event);

        $this->assertNotNull($result);
        $this->assertIsInt($result->userId);
        $this->assertNull($result->refId);
    }

    public function testMapViewCastsToIntegerForNumericId(): void
    {
        $event = new AuditViewDTO(
            'dashboard',
            new AdminIdDTO('202'),
            new AuditContextDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapView($event);

        $this->assertNotNull($result);
        $this->assertIsInt($result->userId);
        $this->assertSame(202, $result->userId);
    }
}
