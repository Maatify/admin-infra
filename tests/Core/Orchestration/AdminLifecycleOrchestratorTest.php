<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 05:30
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
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusCriteriaDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusEnum;
use Maatify\AdminInfra\Contracts\DTO\Admin\Command\ChangeAdminStatusCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Command\CreateAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Result\AdminCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Result\AdminCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminListDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminListItemCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PageMetaDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\Contact\Command\AddAdminContactCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Result\ContactCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Result\ContactCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Contact\Value\ContactTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\Contact\View\AdminContactCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\View\AdminContactListDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminContactsRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminQueryRepositoryInterface;
use Maatify\AdminInfra\Core\Orchestration\AdminLifecycleOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminLifecycleOrchestratorTest extends TestCase
{
    private AdminQueryRepositoryInterface|MockObject $queryRepo;
    private AdminCommandRepositoryInterface|MockObject $commandRepo;
    private AdminContactsRepositoryInterface|MockObject $contactsRepo;
    private AuditLoggerInterface|MockObject $auditLogger;
    private NotificationDispatcherInterface|MockObject $notificationDispatcher;
    private AdminExecutionContextInterface|MockObject $executionContext;
    private AdminLifecycleOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->queryRepo = $this->createMock(AdminQueryRepositoryInterface::class);
        $this->commandRepo = $this->createMock(AdminCommandRepositoryInterface::class);
        $this->contactsRepo = $this->createMock(AdminContactsRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->notificationDispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $this->executionContext = $this->createMock(AdminExecutionContextInterface::class);

        $this->orchestrator = new AdminLifecycleOrchestrator(
            $this->queryRepo,
            $this->commandRepo,
            $this->contactsRepo,
            $this->auditLogger,
            $this->notificationDispatcher,
            $this->executionContext
        );
    }

    public function testCreateAdminSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new CreateAdminCommandDTO($adminId, new DateTimeImmutable());
        $resultDTO = new AdminCommandResultDTO(AdminCommandResultEnum::SUCCESS);
        $actorId = new AdminIdDTO('999');

        $this->commandRepo->expects($this->once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects($this->once())
            ->method('getActorAdminId')
            ->willReturn($actorId);

        $this->auditLogger->expects($this->once())
            ->method('logAction')
            ->with($this->callback(function (AuditActionDTO $dto) use ($actorId, $adminId) {
                return $dto->eventType === 'admin_created'
                    && $dto->actorAdminId === (int)$actorId->id
                    && $dto->targetId === (int)$adminId->id;
            }));

        $this->notificationDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationDTO $dto) use ($adminId) {
                return $dto->type === 'admin_created'
                    && $dto->target->adminId === (int)$adminId->id;
            }));

        $result = $this->orchestrator->createAdmin($command);
        $this->assertSame($resultDTO, $result);
    }

    public function testCreateAdminFailure(): void
    {
        // For 'create', failure usually means exception from repo (infra)
        // OR explicit failure result if defined.
        // AdminCommandResultEnum has INVALID_TRANSITION, though typically create doesn't transition.
        // Assuming repo returns a failure DTO for some business reason (e.g. duplicate).
        // Let's assume the enum has a generic failure or we reuse one.
        // Actually the Enum has: SUCCESS, ADMIN_NOT_FOUND, INVALID_TRANSITION.
        // Create usually throws if duplicate (invariant/infra).
        // But let's test that IF it returns something other than SUCCESS (e.g. imaginary failure if enum expanded),
        // we don't audit.
        // Since we can't invent enum values, I'll use INVALID_TRANSITION as a proxy for "Logic Failure".

        $adminId = new AdminIdDTO('123');
        $command = new CreateAdminCommandDTO($adminId, new DateTimeImmutable());
        $resultDTO = new AdminCommandResultDTO(AdminCommandResultEnum::INVALID_TRANSITION);

        $this->commandRepo->expects($this->once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects($this->never())->method('getActorAdminId');
        $this->auditLogger->expects($this->never())->method('logAction');
        $this->notificationDispatcher->expects($this->never())->method('dispatch');

        $result = $this->orchestrator->createAdmin($command);
        $this->assertSame($resultDTO, $result);
    }

    public function testChangeAdminStatusSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new ChangeAdminStatusCommandDTO(
            $adminId,
            AdminStatusEnum::ACTIVE,
            AdminStatusEnum::INACTIVE,
            new DateTimeImmutable()
        );
        $viewDTO = new AdminViewDTO(
            $adminId,
            AdminStatusEnum::ACTIVE,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
        $resultDTO = new AdminCommandResultDTO(AdminCommandResultEnum::SUCCESS);
        $actorId = new AdminIdDTO('999');

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($viewDTO);

        $this->commandRepo->expects($this->once())
            ->method('changeStatus')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects($this->once())
            ->method('getActorAdminId')
            ->willReturn($actorId);

        $this->auditLogger->expects($this->once())
            ->method('logAction')
            ->with($this->callback(function (AuditActionDTO $dto) use ($actorId, $adminId) {
                return $dto->eventType === 'admin_status_changed'
                    && $dto->actorAdminId === (int)$actorId->id
                    && $dto->targetId === (int)$adminId->id;
            }));

        $this->notificationDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationDTO $dto) use ($adminId) {
                return $dto->type === 'admin_status_changed'
                    && $dto->target->adminId === (int)$adminId->id;
            }));

        $result = $this->orchestrator->changeAdminStatus($command);
        $this->assertSame($resultDTO, $result);
    }

    public function testChangeAdminStatusNotFound(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new ChangeAdminStatusCommandDTO(
            $adminId,
            AdminStatusEnum::ACTIVE,
            AdminStatusEnum::INACTIVE,
            new DateTimeImmutable()
        );
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, '123');

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($notFound);

        $this->commandRepo->expects($this->never())->method('changeStatus');
        $this->executionContext->expects($this->never())->method('getActorAdminId');
        $this->auditLogger->expects($this->never())->method('logAction');
        $this->notificationDispatcher->expects($this->never())->method('dispatch');

        $result = $this->orchestrator->changeAdminStatus($command);
        $this->assertSame(AdminCommandResultEnum::ADMIN_NOT_FOUND, $result->result);
    }

    public function testAddAdminContactSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new AddAdminContactCommandDTO(
            $adminId,
            ContactTypeEnum::EMAIL,
            'test@example.com',
            true
        );
        $viewDTO = new AdminViewDTO(
            $adminId,
            AdminStatusEnum::ACTIVE,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
        $resultDTO = new ContactCommandResultDTO(ContactCommandResultEnum::SUCCESS);
        $actorId = new AdminIdDTO('999');

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($viewDTO);

        $this->contactsRepo->expects($this->once())
            ->method('add')
            ->with($command)
            ->willReturn($resultDTO);

        $this->executionContext->expects($this->once())
            ->method('getActorAdminId')
            ->willReturn($actorId);

        $this->auditLogger->expects($this->once())
            ->method('logAction')
            ->with($this->callback(function (AuditActionDTO $dto) use ($actorId, $adminId) {
                return $dto->eventType === 'admin_contact_added'
                    && $dto->actorAdminId === (int)$actorId->id
                    && $dto->targetId === (int)$adminId->id;
            }));

        // No notification for add contact as per implementation

        $result = $this->orchestrator->addAdminContact($command);
        $this->assertSame($resultDTO, $result);
    }

    public function testAddAdminContactNotFound(): void
    {
        $adminId = new AdminIdDTO('123');
        $command = new AddAdminContactCommandDTO(
            $adminId,
            ContactTypeEnum::EMAIL,
            'test@example.com',
            true
        );
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, '123');

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($notFound);

        $this->contactsRepo->expects($this->never())->method('add');
        $this->executionContext->expects($this->never())->method('getActorAdminId');
        $this->auditLogger->expects($this->never())->method('logAction');

        $result = $this->orchestrator->addAdminContact($command);
        $this->assertSame(ContactCommandResultEnum::ADMIN_NOT_FOUND, $result->result);
    }

    public function testGetAdminSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $viewDTO = new AdminViewDTO(
            $adminId,
            AdminStatusEnum::ACTIVE,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($viewDTO);

        $this->auditLogger->expects($this->never())->method('logAction');
        // Implementation might have logView?
        // Checking Orchestrator...
        // "Retrieves an admin view... return $this->queryRepo->getById($adminId);"
        // No logView in hardened implementation?
        // Let's re-read the hardened implementation I wrote.
        // It was "return $this->queryRepo->getById($adminId);" - Pure Passthrough.
        // So no logView.

        $result = $this->orchestrator->getAdmin($adminId);
        $this->assertSame($viewDTO, $result);
    }

    public function testGetAdminNotFound(): void
    {
        $adminId = new AdminIdDTO('123');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, '123');

        $this->queryRepo->expects($this->once())
            ->method('getById')
            ->with($adminId)
            ->willReturn($notFound);

        $result = $this->orchestrator->getAdmin($adminId);
        $this->assertSame($notFound, $result);
    }

    public function testListAdmins(): void
    {
        $criteria = new AdminStatusCriteriaDTO(null);
        $pagination = new PaginationDTO(1, 10);
        $listDTO = new AdminListDTO(
            new AdminListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->queryRepo->expects($this->once())
            ->method('getByStatus')
            ->with($criteria, $pagination)
            ->willReturn($listDTO);

        $this->auditLogger->expects($this->never())->method('logAction');

        $result = $this->orchestrator->listAdmins($criteria, $pagination);
        $this->assertSame($listDTO, $result);
    }

    public function testListAdminContactsSuccess(): void
    {
        $adminId = new AdminIdDTO('123');
        $listDTO = new AdminContactListDTO(new AdminContactCollectionDTO([]));

        $this->contactsRepo->expects($this->once())
            ->method('listByAdmin')
            ->with($adminId)
            ->willReturn($listDTO);

        $result = $this->orchestrator->listAdminContacts($adminId);
        $this->assertSame($listDTO, $result);
    }

    public function testListAdminContactsNotFound(): void
    {
        $adminId = new AdminIdDTO('123');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, '123');

        $this->contactsRepo->expects($this->once())
            ->method('listByAdmin')
            ->with($adminId)
            ->willReturn($notFound);

        $result = $this->orchestrator->listAdminContacts($adminId);
        $this->assertSame($notFound, $result);
    }
}
