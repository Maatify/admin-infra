<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 23:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Core\Orchestration;

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\AssignRoleToAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\CreateRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RenameRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RevokeRoleFromAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\PermissionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\AdminRoleAssignmentResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\AdminRoleAssignmentResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\RoleCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\RoleCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Authorization\RoleIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\PermissionListDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\PermissionListItemCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\PermissionViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleListDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleListItemCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PageMetaDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\AdminRoleAssignmentRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\PermissionQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\RoleCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\RoleQueryRepositoryInterface;
use Maatify\AdminInfra\Core\Orchestration\AuthorizationOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthorizationOrchestratorTest extends TestCase
{
    /** @var RoleQueryRepositoryInterface&MockObject */
    private $roleQueryRepo;

    /** @var RoleCommandRepositoryInterface&MockObject */
    private $roleCommandRepo;

    /** @var PermissionQueryRepositoryInterface&MockObject */
    private $permissionQueryRepo;

    /** @var AdminRoleAssignmentRepositoryInterface&MockObject */
    private $roleAssignmentRepo;

    private AuthorizationOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->roleQueryRepo = $this->createMock(RoleQueryRepositoryInterface::class);
        $this->roleCommandRepo = $this->createMock(RoleCommandRepositoryInterface::class);
        $this->permissionQueryRepo = $this->createMock(PermissionQueryRepositoryInterface::class);
        $this->roleAssignmentRepo = $this->createMock(AdminRoleAssignmentRepositoryInterface::class);

        $this->orchestrator = new AuthorizationOrchestrator(
            $this->roleQueryRepo,
            $this->roleCommandRepo,
            $this->permissionQueryRepo,
            $this->roleAssignmentRepo
        );
    }

    public function testCreateRoleSuccess(): void
    {
        $roleId = new RoleIdDTO('role_1');
        $command = new CreateRoleCommandDTO($roleId, 'Admin');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_1');
        $resultDTO = new RoleCommandResultDTO(RoleCommandResultEnum::SUCCESS);

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($notFound);

        $this->roleCommandRepo->expects(self::once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        self::assertSame($resultDTO, $this->orchestrator->createRole($command));
    }

    public function testCreateRoleAlreadyExists(): void
    {
        $roleId = new RoleIdDTO('role_1');
        $command = new CreateRoleCommandDTO($roleId, 'Admin');
        $existing = new RoleViewDTO($roleId, 'Admin');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->willReturn($existing);

        $this->roleCommandRepo->expects(self::never())->method('create');

        $result = $this->orchestrator->createRole($command);

        self::assertSame(RoleCommandResultEnum::ROLE_ALREADY_EXISTS, $result->result);
    }

    public function testRenameRoleNotFound(): void
    {
        $roleId = new RoleIdDTO('role_1');
        $command = new RenameRoleCommandDTO($roleId, 'Super');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_1');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->willReturn($notFound);

        $this->roleCommandRepo->expects(self::never())->method('rename');

        $result = $this->orchestrator->renameRole($command);

        self::assertSame(RoleCommandResultEnum::ROLE_NOT_FOUND, $result->result);
    }

    public function testAssignRoleNotFound(): void
    {
        $adminId = new AdminIdDTO('1');
        $roleId = new RoleIdDTO('role_1');
        $command = new AssignRoleToAdminCommandDTO($adminId, $roleId);
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_1');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->willReturn($notFound);

        $this->roleAssignmentRepo->expects(self::never())->method('assign');

        $result = $this->orchestrator->assignRoleToAdmin($command);

        self::assertSame(AdminRoleAssignmentResultEnum::ROLE_NOT_FOUND, $result->result);
    }

    public function testGetRole(): void
    {
        $roleId = new RoleIdDTO('role_1');
        $view = new RoleViewDTO($roleId, 'Admin');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->willReturn($view);

        self::assertSame($view, $this->orchestrator->getRole($roleId));
    }

    public function testListRoles(): void
    {
        $pagination = new PaginationDTO(1, 10);
        $list = new RoleListDTO(
            new RoleListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->roleQueryRepo->expects(self::once())
            ->method('list')
            ->with($pagination)
            ->willReturn($list);

        self::assertSame($list, $this->orchestrator->listRoles($pagination));
    }

    public function testGetPermission(): void
    {
        $permId = new PermissionIdDTO('perm_1');
        $view = new PermissionViewDTO($permId, 'read');

        $this->permissionQueryRepo->expects(self::once())
            ->method('getById')
            ->willReturn($view);

        self::assertSame($view, $this->orchestrator->getPermission($permId));
    }

    public function testListPermissions(): void
    {
        $pagination = new PaginationDTO(1, 10);
        $list = new PermissionListDTO(
            new PermissionListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->permissionQueryRepo->expects(self::once())
            ->method('list')
            ->with($pagination)
            ->willReturn($list);

        self::assertSame($list, $this->orchestrator->listPermissions($pagination));
    }
}
