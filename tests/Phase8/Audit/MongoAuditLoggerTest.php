<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Audit;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditLogger;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditMapper;
use Maatify\MongoActivity\ActivityLoggerInterface;
use Maatify\MongoActivity\DTO\AuditActionDTO as MongoAuditActionDTO;
use Maatify\MongoActivity\DTO\AuditAuthEventDTO as MongoAuditAuthEventDTO;
use Maatify\MongoActivity\DTO\AuditSecurityEventDTO as MongoAuditSecurityEventDTO;
use Maatify\MongoActivity\DTO\AuditViewDTO as MongoAuditViewDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MongoAuditLoggerTest extends TestCase
{
    private ActivityLoggerInterface&MockObject $activityLogger;
    private MongoAuditLogger $auditLogger;

    protected function setUp(): void
    {
        $this->activityLogger = $this->createMock(ActivityLoggerInterface::class);
        $this->auditLogger = new MongoAuditLogger($this->activityLogger, new MongoAuditMapper());
    }

    public function testImplementsContract(): void
    {
        $this->assertInstanceOf(AuditLoggerInterface::class, $this->auditLogger);
    }

    public function testDelegatesAuthEvents(): void
    {
        $event = new AuditAuthEventDTO(
            'auth.login',
            123,
            new AuditContextDTO([new AuditContextItemDTO('ip', '127.0.0.1')]),
            new AuditMetadataDTO([new AuditContextItemDTO('agent', 'Mozilla')]),
            new DateTimeImmutable()
        );

        $this->activityLogger->expects($this->once())
            ->method('logAuth')
            ->with($this->isInstanceOf(MongoAuditAuthEventDTO::class));

        $this->auditLogger->logAuth($event);
    }

    public function testDelegatesSecurityEvents(): void
    {
        $event = new AuditSecurityEventDTO(
            'security.alert',
            123,
            new AuditContextDTO([new AuditContextItemDTO('severity', 'high')]),
            new AuditMetadataDTO([new AuditContextItemDTO('source', 'firewall')]),
            new DateTimeImmutable()
        );

        $this->activityLogger->expects($this->once())
            ->method('logSecurity')
            ->with($this->isInstanceOf(MongoAuditSecurityEventDTO::class));

        $this->auditLogger->logSecurity($event);
    }

    public function testDelegatesActionEvents(): void
    {
        $event = new AuditActionDTO(
            'action.create',
            123,
            'User',
            456,
            new AuditContextDTO([new AuditContextItemDTO('field', 'email')]),
            new AuditMetadataDTO([new AuditContextItemDTO('reason', 'manual')]),
            new DateTimeImmutable()
        );

        $this->activityLogger->expects($this->once())
            ->method('logAction')
            ->with($this->isInstanceOf(MongoAuditActionDTO::class));

        $this->auditLogger->logAction($event);
    }

    public function testDelegatesViewEvents(): void
    {
        $event = new AuditViewDTO(
            'view.dashboard',
            123,
            new AuditContextDTO([new AuditContextItemDTO('filter', 'all')]),
            new DateTimeImmutable()
        );

        $this->activityLogger->expects($this->once())
            ->method('logView')
            ->with($this->isInstanceOf(MongoAuditViewDTO::class));

        $this->auditLogger->logView($event);
    }

    public function testLogAuthSwallowsExceptions(): void
    {
        $this->activityLogger->method('logAuth')->willThrowException(new RuntimeException('Mongo error'));

        $event = new AuditAuthEventDTO(
            'auth.fail',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->auditLogger->logAuth($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogSecuritySwallowsExceptions(): void
    {
        $this->activityLogger->method('logSecurity')->willThrowException(new RuntimeException('Mongo error'));

        $event = new AuditSecurityEventDTO(
            'security.fail',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->auditLogger->logSecurity($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogActionSwallowsExceptions(): void
    {
        $this->activityLogger->method('logAction')->willThrowException(new RuntimeException('Mongo error'));

        $event = new AuditActionDTO(
            'action.fail',
            123,
            'User',
            456,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->auditLogger->logAction($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogViewSwallowsExceptions(): void
    {
        $this->activityLogger->method('logView')->willThrowException(new RuntimeException('Mongo error'));

        $event = new AuditViewDTO(
            'view.fail',
            123,
            new AuditContextDTO([]),
            new DateTimeImmutable()
        );

        $this->auditLogger->logView($event);
        $this->expectNotToPerformAssertions();
    }
}
