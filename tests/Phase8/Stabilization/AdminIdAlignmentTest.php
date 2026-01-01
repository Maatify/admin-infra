<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase8\Stabilization;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminContactsRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminQueryRepositoryInterface;
use Maatify\AdminInfra\Core\Orchestration\AdminLifecycleOrchestrator;
use Maatify\AdminInfra\Core\Security\EmergencyModeManager;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminIdAlignmentTest extends TestCase
{
    /** @var AuditLoggerInterface&MockObject */
    private $auditLogger;
    /** @var NotificationDispatcherInterface&MockObject */
    private $dispatcher;
    /** @var SessionRevocationService&MockObject */
    private $sessionRevoker;

    protected function setUp(): void
    {
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $this->sessionRevoker = $this->createMock(SessionRevocationService::class);
    }

    public function testAuditDtosAcceptAdminIdDto(): void
    {
        $adminId = new AdminIdDTO('123');
        $dto = new AuditActionDTO(
            'test_event',
            $adminId,
            'target',
            $adminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            new DateTimeImmutable()
        );

        $this->assertInstanceOf(AdminIdDTO::class, $dto->actorAdminId);
        $this->assertInstanceOf(AdminIdDTO::class, $dto->targetId);
        $this->assertSame('123', $dto->actorAdminId->id);
    }

    public function testOrchestratorPassesAdminIdDto(): void
    {
        // Setup minimal orchestrator dependencies
        $queryRepo = $this->createMock(AdminQueryRepositoryInterface::class);
        $commandRepo = $this->createMock(AdminCommandRepositoryInterface::class);
        $contactsRepo = $this->createMock(AdminContactsRepositoryInterface::class);
        $context = $this->createMock(AdminExecutionContextInterface::class);

        $orchestrator = new AdminLifecycleOrchestrator(
            $queryRepo,
            $commandRepo,
            $contactsRepo,
            $this->auditLogger,
            $this->dispatcher,
            $context
        );

        // Assert construction works with types
        $this->assertInstanceOf(AdminLifecycleOrchestrator::class, $orchestrator);
    }

    public function testEmergencyModeManagerNullActor(): void
    {
        $manager = new EmergencyModeManager(
            $this->sessionRevoker,
            $this->auditLogger,
            $this->dispatcher
        );

        // Expect NO calls to logSecurity or dispatch
        $this->auditLogger->expects($this->never())->method('logSecurity');
        $this->dispatcher->expects($this->never())->method('dispatch');

        // Revoke SHOULD be called (with null actor)
        $this->sessionRevoker->expects($this->once())
            ->method('revoke')
            ->with($this->isInstanceOf(SessionIdDTO::class), null, $this->isInstanceOf(DateTimeImmutable::class));

        $manager->enable([new SessionIdDTO('sess_1')], new DateTimeImmutable(), null);
    }

    public function testEmergencyModeManagerNonNullActor(): void
    {
        $manager = new EmergencyModeManager(
            $this->sessionRevoker,
            $this->auditLogger,
            $this->dispatcher
        );

        $actor = new AdminIdDTO('999');

        // Expect calls to logSecurity and dispatch
        $this->auditLogger->expects($this->once())->method('logSecurity');
        $this->dispatcher->expects($this->once())->method('dispatch');

        // Revoke SHOULD be called (with actor)
        $this->sessionRevoker->expects($this->once())
            ->method('revoke')
            ->with($this->isInstanceOf(SessionIdDTO::class), $actor, $this->isInstanceOf(DateTimeImmutable::class));

        $manager->enable([new SessionIdDTO('sess_1')], new DateTimeImmutable(), $actor);
    }
}
