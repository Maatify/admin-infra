<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Audit;

use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\Enum\AdminInfraAppModuleEnum;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditLogger;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditMapper;
use Maatify\MongoActivity\DTO\ActivityRecordDTO;
use Maatify\MongoActivity\Enum\ActivityLogTypeEnum;
use Maatify\MongoActivity\Enum\UserLogRoleEnum;
use Maatify\MongoActivity\Manager\ActivityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MongoAuditLoggerTest extends TestCase
{
    /** @var MockObject */
    private $activityManager;
    private MongoAuditMapper $mapper;
    private MongoAuditLogger $logger;

    protected function setUp(): void
    {
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->mapper = new MongoAuditMapper();
        $this->logger = new MongoAuditLogger($this->activityManager, $this->mapper);
    }

    public function testLogAuthRecordsActivity(): void
    {
        $event = new AuditAuthEventDTO(
            'auth_login',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('record')
            ->with($this->callback(function (ActivityRecordDTO $dto) use ($event) {
                return $dto->userId === $event->adminId
                    && $dto->role === UserLogRoleEnum::ADMIN
                    && $dto->type === ActivityLogTypeEnum::SYSTEM
                    && $dto->module === AdminInfraAppModuleEnum::ADMIN
                    && $dto->action === $event->eventType;
            }));

        $this->logger->logAuth($event);
    }

    public function testLogSecurityRecordsActivity(): void
    {
        $event = new AuditSecurityEventDTO(
            'security_alert',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('record')
            ->with($this->callback(function (ActivityRecordDTO $dto) use ($event) {
                return $dto->userId === $event->adminId
                    && $dto->role === UserLogRoleEnum::ADMIN
                    && $dto->type === ActivityLogTypeEnum::SYSTEM
                    && $dto->module === AdminInfraAppModuleEnum::ADMIN
                    && $dto->action === $event->eventType;
            }));

        $this->logger->logSecurity($event);
    }

    public function testLogActionRecordsActivity(): void
    {
        $event = new AuditActionDTO(
            'create_user',
            123,
            'user',
            456,
            new AuditContextDTO([new AuditContextItemDTO('key', 'value')]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('record')
            ->with($this->callback(function (ActivityRecordDTO $dto) use ($event) {
                return $dto->userId === $event->actorAdminId
                    && $dto->role === UserLogRoleEnum::ADMIN
                    && $dto->type === ActivityLogTypeEnum::UPDATE
                    && $dto->module === AdminInfraAppModuleEnum::ADMIN
                    && $dto->action === $event->eventType
                    && $dto->refId === $event->targetId
                    && str_contains($dto->description ?? '', 'key: value');
            }));

        $this->logger->logAction($event);
    }

    public function testLogViewRecordsActivity(): void
    {
        $event = new AuditViewDTO(
            'dashboard',
            123,
            new AuditContextDTO([]),
            new \DateTimeImmutable()
        );

        $this->activityManager->expects($this->once())
            ->method('record')
            ->with($this->callback(function (ActivityRecordDTO $dto) use ($event) {
                return $dto->userId === $event->adminId
                    && $dto->role === UserLogRoleEnum::ADMIN
                    && $dto->type === ActivityLogTypeEnum::VIEW
                    && $dto->module === AdminInfraAppModuleEnum::ADMIN
                    && $dto->action === $event->viewName;
            }));

        $this->logger->logView($event);
    }

    public function testLogAuthSwallowsException(): void
    {
        $this->activityManager->method('record')->willThrowException(new \Exception('DB Error'));

        $event = new AuditAuthEventDTO(
            'auth_login',
            123,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->logger->logAuth($event);
        $this->expectNotToPerformAssertions();
    }
}
