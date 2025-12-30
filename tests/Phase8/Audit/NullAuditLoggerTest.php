<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Audit;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Drivers\Audit\Null\NullAuditLogger;
use PHPUnit\Framework\TestCase;

final class NullAuditLoggerTest extends TestCase
{
    private NullAuditLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullAuditLogger();
    }

    public function testImplementsContract(): void
    {
        $this->assertInstanceOf(AuditLoggerInterface::class, $this->logger);
    }

    public function testLogAuthIsNoOp(): void
    {
        $event = new AuditAuthEventDTO(
            'auth.test',
            1,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );
        $this->logger->logAuth($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogSecurityIsNoOp(): void
    {
        $event = new AuditSecurityEventDTO(
            'security.test',
            1,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );
        $this->logger->logSecurity($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogActionIsNoOp(): void
    {
        $event = new AuditActionDTO(
            'action.test',
            1,
            'User',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );
        $this->logger->logAction($event);
        $this->expectNotToPerformAssertions();
    }

    public function testLogViewIsNoOp(): void
    {
        $event = new AuditViewDTO(
            'view.test',
            1,
            new AuditContextDTO([]),
            new DateTimeImmutable()
        );
        $this->logger->logView($event);
        $this->expectNotToPerformAssertions();
    }
}
