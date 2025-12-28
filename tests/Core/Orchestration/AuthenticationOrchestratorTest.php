<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 05:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Core\Orchestration;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionCommandRepositoryInterface;
use Maatify\AdminInfra\Core\Orchestration\AuthenticationOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticationOrchestratorTest extends TestCase
{
    /**
     * @var SessionCommandRepositoryInterface&MockObject
     */
    private $sessionCommandRepo;

    /**
     * @var AuditLoggerInterface&MockObject
     */
    private $auditLogger;

    /**
     * @var NotificationDispatcherInterface&MockObject
     */
    private $notificationDispatcher;

    /**
     * @var AdminExecutionContextInterface&MockObject
     */
    private $executionContext;

    private AuthenticationOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->sessionCommandRepo = $this->createMock(SessionCommandRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->notificationDispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $this->executionContext = $this->createMock(AdminExecutionContextInterface::class);

        $this->orchestrator = new AuthenticationOrchestrator(
            $this->sessionCommandRepo,
            $this->auditLogger,
            $this->notificationDispatcher,
            $this->executionContext
        );
    }

    public function testAuthenticateSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new CreateSessionCommandDTO(
            new SessionIdDTO('sess_abc'),
            $adminId,
            new DateTimeImmutable()
        );
        $resultDTO = new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);
        $actorId = new AdminIdDTO('999');

        $this->sessionCommandRepo->expects(self::once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects(self::once())
            ->method('getActorAdminId')
            ->willReturn($actorId);

        $this->auditLogger->expects(self::once())
            ->method('logAction')
            ->with(self::callback(function (AuditActionDTO $dto) use ($actorId, $adminId) {
                return $dto->eventType === 'admin_login'
                    && $dto->actorAdminId === (int)$actorId->id
                    && $dto->targetId === (int)$adminId->id;
            }));

        $this->notificationDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (NotificationDTO $dto) use ($adminId) {
                return $dto->type === 'admin_login'
                    && $dto->target->adminId === (int)$adminId->id;
            }));

        $result = $this->orchestrator->authenticate($command);
        self::assertSame($resultDTO, $result);
    }

    public function testAuthenticateFailure(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new CreateSessionCommandDTO(
            new SessionIdDTO('sess_abc'),
            $adminId,
            new DateTimeImmutable()
        );
        // Using SESSION_NOT_FOUND as a generic failure proxy since enum is limited
        $resultDTO = new SessionCommandResultDTO(SessionCommandResultEnum::SESSION_NOT_FOUND);

        $this->sessionCommandRepo->expects(self::once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects(self::never())->method('getActorAdminId');
        $this->auditLogger->expects(self::never())->method('logAction');
        $this->notificationDispatcher->expects(self::never())->method('dispatch');

        $result = $this->orchestrator->authenticate($command);
        self::assertSame($resultDTO, $result);
    }
}
