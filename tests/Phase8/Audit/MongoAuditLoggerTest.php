<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Audit;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditLogger;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditMapper;
use Maatify\MongoActivity\DTO\ActivityRecordDTO;
use Maatify\MongoActivity\Enum\ActionLogEnum;
use Maatify\MongoActivity\Manager\ActivityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MongoAuditLoggerTest extends TestCase
{
    private ActivityManager&MockObject $activityManager;
    private MongoAuditMapper $mapper;
    private MongoAuditLogger $logger;

    protected function setUp(): void
    {
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->mapper = new MongoAuditMapper();
        $this->logger = new MongoAuditLogger($this->activityManager, $this->mapper);
    }

    public function testLogAuthDelegatesToManager(): void
    {
        $dto = new AuditAuthEventDTO(
            'login',
            123,
            new AuditContextDTO([new AuditContextItemDTO('ip', '127.0.0.1')]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('log')
            ->with($this->callback(function (ActivityRecordDTO $record) use ($dto) {
                return $record->action === ActionLogEnum::AUTH
                    && $record->actorId === $dto->adminId
                    && $record->createdAt === $dto->occurredAt;
            }));

        $this->logger->logAuth($dto);
    }

    public function testLogSecurityDelegatesToManager(): void
    {
        $dto = new AuditSecurityEventDTO(
            'password_change',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('log')
            ->with($this->callback(function (ActivityRecordDTO $record) use ($dto) {
                return $record->action === ActionLogEnum::SECURITY
                    && $record->actorId === $dto->adminId;
            }));

        $this->logger->logSecurity($dto);
    }

    public function testLogActionDelegatesToManager(): void
    {
        $dto = new AuditActionDTO(
            'create',
            123,
            'user',
            456,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('log')
            ->with($this->callback(function (ActivityRecordDTO $record) use ($dto) {
                return $record->action === ActionLogEnum::ACTION
                    && $record->actorId === $dto->actorAdminId;
            }));

        $this->logger->logAction($dto);
    }

    public function testLogViewDelegatesToManager(): void
    {
        $dto = new AuditViewDTO(
            'dashboard',
            123,
            new AuditContextDTO([]),
            new DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('log')
            ->with($this->callback(function (ActivityRecordDTO $record) use ($dto) {
                return $record->action === ActionLogEnum::VIEW
                    && $record->actorId === $dto->adminId;
            }));

        $this->logger->logView($dto);
    }

    public function testSwallowsExceptions(): void
    {
        $dto = new AuditAuthEventDTO(
            'login',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->activityManager->method('log')
            ->willThrowException(new \Exception('Mongo Error'));

        // Should not throw
        $this->logger->logAuth($dto);

        $this->expectNotToPerformAssertions();
    }
}
