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
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\Enum\AdminInfraAppModuleEnum;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditLogger;
use Maatify\AdminInfra\Drivers\Audit\Mongo\MongoAuditMapper;
use Maatify\MongoActivity\Enum\ActivityLogTypeEnum;
use Maatify\MongoActivity\Enum\UserLogRoleEnum;
use Maatify\MongoActivity\Manager\ActivityManager;
use Maatify\MongoActivity\Repository\ActivityRepository;
use MongoDB\Client;
use MongoDB\Collection;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MongoAuditLoggerTest extends TestCase
{
    /** @var MockObject */
    private $collection;
    private ActivityManager $activityManager;
    private MongoAuditMapper $mapper;
    private MongoAuditLogger $logger;

    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $client = $this->createMock(Client::class);
        $client->method('selectCollection')->willReturn($this->collection);

        $repository = new ActivityRepository($client);
        $this->activityManager = new ActivityManager($repository);
        $this->mapper = new MongoAuditMapper();
        $this->logger = new MongoAuditLogger($this->activityManager, $this->mapper);
    }

    public function testLogAuthRecordsActivity(): void
    {
        $event = new AuditAuthEventDTO(
            'auth_login',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($this->callback(function (array $data) use ($event) {
                return $data['user_id'] === 123
                    && $data['role'] === UserLogRoleEnum::ADMIN->value
                    && $data['type'] === ActivityLogTypeEnum::SYSTEM->value
                    && $data['module'] === AdminInfraAppModuleEnum::ADMIN->value
                    && $data['action'] === $event->eventType;
            }));

        $this->logger->logAuth($event);
    }

    public function testLogSecurityRecordsActivity(): void
    {
        $event = new AuditSecurityEventDTO(
            'security_alert',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($this->callback(function (array $data) use ($event) {
                return $data['user_id'] === 123
                    && $data['role'] === UserLogRoleEnum::ADMIN->value
                    && $data['type'] === ActivityLogTypeEnum::SYSTEM->value
                    && $data['module'] === AdminInfraAppModuleEnum::ADMIN->value
                    && $data['action'] === $event->eventType;
            }));

        $this->logger->logSecurity($event);
    }

    public function testLogActionRecordsActivity(): void
    {
        $event = new AuditActionDTO(
            'create_user',
            new AdminIdDTO('123'),
            'user',
            new AdminIdDTO('456'),
            new AuditContextDTO([new AuditContextItemDTO('key', 'value')]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($this->callback(function (array $data) use ($event) {
                return $data['user_id'] === 123
                    && $data['role'] === UserLogRoleEnum::ADMIN->value
                    && $data['type'] === ActivityLogTypeEnum::UPDATE->value
                    && $data['module'] === AdminInfraAppModuleEnum::ADMIN->value
                    && $data['action'] === $event->eventType
                    && $data['ref_id'] === 456
                    && str_contains($data['description'] ?? '', 'key: value');
            }));

        $this->logger->logAction($event);
    }

    public function testLogViewRecordsActivity(): void
    {
        $event = new AuditViewDTO(
            'dashboard',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new \DateTimeImmutable()
        );

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($this->callback(function (array $data) use ($event) {
                return $data['user_id'] === 123
                    && $data['role'] === UserLogRoleEnum::ADMIN->value
                    && $data['type'] === ActivityLogTypeEnum::VIEW->value
                    && $data['module'] === AdminInfraAppModuleEnum::ADMIN->value
                    && $data['action'] === $event->viewName;
            }));

        $this->logger->logView($event);
    }

    public function testLogAuthSwallowsException(): void
    {
        $this->collection->method('insertOne')->willThrowException(new \Exception('DB Error'));

        $event = new AuditAuthEventDTO(
            'auth_login',
            new AdminIdDTO('123'),
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new \DateTimeImmutable()
        );

        $this->expectException(Warning::class);
        $this->expectExceptionMessage('MongoAuditLogger::logAuth failed');

        $this->logger->logAuth($event);
        $this->expectNotToPerformAssertions();
    }
}
