<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Audit;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Drivers\Audit\Null\NullAuditLogger;
use PHPUnit\Framework\TestCase;

class NullAuditLoggerTest extends TestCase
{
    private NullAuditLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullAuditLogger();
    }

    public function testLogAuthDoesNothing(): void
    {
        $this->logger->logAuth(new AuditAuthEventDTO(
            'event',
            new AdminIdDTO('1'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        ));
        $this->expectNotToPerformAssertions();
    }

    public function testLogSecurityDoesNothing(): void
    {
        $this->logger->logSecurity(new AuditSecurityEventDTO(
            'event',
            new AdminIdDTO('1'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        ));
        $this->expectNotToPerformAssertions();
    }

    public function testLogActionDoesNothing(): void
    {
        $this->logger->logAction(new AuditActionDTO(
            'event',
            new AdminIdDTO('1'),
            'target',
            new AdminIdDTO('2'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        ));
        $this->expectNotToPerformAssertions();
    }

    public function testLogViewDoesNothing(): void
    {
        $this->logger->logView(new AuditViewDTO(
            'view',
            new AdminIdDTO('1'),
            new AuditContextDTO([]),
            new DateTimeImmutable()
        ));
        $this->expectNotToPerformAssertions();
    }
}
