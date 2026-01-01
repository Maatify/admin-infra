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

    public function testMapAuthPreservesString(): void
    {
        $event = new AuditAuthEventDTO(
            'login',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapAuth($event);

        $this->assertIsString($result->userId);
        $this->assertSame('123', $result->userId);
    }

    public function testMapSecurityPreservesString(): void
    {
        $event = new AuditSecurityEventDTO(
            'alert',
            new AdminIdDTO('456'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapSecurity($event);

        $this->assertIsString($result->userId);
        $this->assertSame('456', $result->userId);
    }

    public function testMapActionPreservesString(): void
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

        $this->assertIsString($result->userId);
        $this->assertSame('789', $result->userId);

        $this->assertIsString($result->refId);
        $this->assertSame('101', $result->refId);
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

        $this->assertIsString($result->userId);
        $this->assertNull($result->refId);
    }

    public function testMapViewPreservesString(): void
    {
        $event = new AuditViewDTO(
            'dashboard',
            new AdminIdDTO('202'),
            new AuditContextDTO([]),
            new DateTimeImmutable()
        );

        $result = $this->mapper->mapView($event);

        $this->assertIsString($result->userId);
        $this->assertSame('202', $result->userId);
    }
}
