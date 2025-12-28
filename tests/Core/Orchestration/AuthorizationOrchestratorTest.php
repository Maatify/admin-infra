<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 06:15
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

class AuthorizationOrchestratorTest extends TestCase
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
        $roleId = new RoleIdDTO('role_123');
        $command = new CreateRoleCommandDTO($roleId, 'Admin');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_123'); // Placeholder enum usage
        $resultDTO = new RoleCommandResultDTO(RoleCommandResultEnum::SUCCESS);

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($notFound);

        $this->roleCommandRepo->expects(self::once())
            ->method('create')
            ->with($command)
            ->willReturn($resultDTO);

        $result = $this->orchestrator->createRole($command);
        self::assertSame($resultDTO, $result);
    }

    public function testCreateRoleAlreadyExists(): void
    {
        $roleId = new RoleIdDTO('role_123');
        $command = new CreateRoleCommandDTO($roleId, 'Admin');
        $existingRole = new RoleViewDTO($roleId, 'Admin');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($existingRole);

        $this->roleCommandRepo->expects(self::never())
            ->method('create');

        $result = $this->orchestrator->createRole($command);
        self::assertSame(RoleCommandResultEnum::ROLE_ALREADY_EXISTS, $result->result);
    }

    public function testRenameRoleSuccess(): void
    {
        $roleId = new RoleIdDTO('role_123');
        $command = new RenameRoleCommandDTO($roleId, 'Super Admin');
        $existingRole = new RoleViewDTO($roleId, 'Admin');
        $resultDTO = new RoleCommandResultDTO(RoleCommandResultEnum::SUCCESS);

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($existingRole);

        $this->roleCommandRepo->expects(self::once())
            ->method('rename')
            ->with($command)
            ->willReturn($resultDTO);

        $result = $this->orchestrator->renameRole($command);
        self::assertSame($resultDTO, $result);
    }

    public function testRenameRoleNotFound(): void
    {
        $roleId = new RoleIdDTO('role_123');
        $command = new RenameRoleCommandDTO($roleId, 'Super Admin');
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_123');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($notFound);

        $this->roleCommandRepo->expects(self::never())
            ->method('rename');

        $result = $this->orchestrator->renameRole($command);
        self::assertSame(RoleCommandResultEnum::ROLE_NOT_FOUND, $result->result);
    }

    public function testAssignRoleSuccess(): void
    {
        $adminId = new AdminIdDTO('1');
        $roleId = new RoleIdDTO('role_123');
        $command = new AssignRoleToAdminCommandDTO($adminId, $roleId);
        $existingRole = new RoleViewDTO($roleId, 'Admin');
        $resultDTO = new AdminRoleAssignmentResultDTO(AdminRoleAssignmentResultEnum::SUCCESS);

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($existingRole);

        $this->roleAssignmentRepo->expects(self::once())
            ->method('assign')
            ->with($command)
            ->willReturn($resultDTO);

        $result = $this->orchestrator->assignRoleToAdmin($command);
        self::assertSame($resultDTO, $result);
    }

    public function testAssignRoleNotFound(): void
    {
        $adminId = new AdminIdDTO('1');
        $roleId = new RoleIdDTO('role_123');
        $command = new AssignRoleToAdminCommandDTO($adminId, $roleId);
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_123');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($notFound);

        $this->roleAssignmentRepo->expects(self::never())
            ->method('assign');

        $result = $this->orchestrator->assignRoleToAdmin($command);
        self::assertSame(AdminRoleAssignmentResultEnum::ROLE_NOT_FOUND, $result->result);
    }

    public function testRevokeRoleSuccess(): void
    {
        $adminId = new AdminIdDTO('1');
        $roleId = new RoleIdDTO('role_123');
        $command = new RevokeRoleFromAdminCommandDTO($adminId, $roleId);
        $existingRole = new RoleViewDTO($roleId, 'Admin');
        $resultDTO = new AdminRoleAssignmentResultDTO(AdminRoleAssignmentResultEnum::SUCCESS);

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($existingRole);

        $this->roleAssignmentRepo->expects(self::once())
            ->method('revoke')
            ->with($command)
            ->willReturn($resultDTO);

        $result = $this->orchestrator->revokeRoleFromAdmin($command);
        self::assertSame($resultDTO, $result);
    }

    public function testRevokeRoleNotFound(): void
    {
        $adminId = new AdminIdDTO('1');
        $roleId = new RoleIdDTO('role_123');
        $command = new RevokeRoleFromAdminCommandDTO($adminId, $roleId);
        $notFound = new NotFoundResultDTO(EntityTypeEnum::ADMIN, 'role_123');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($notFound);

        $this->roleAssignmentRepo->expects(self::never())
            ->method('revoke');

        $result = $this->orchestrator->revokeRoleFromAdmin($command);
        self::assertSame(AdminRoleAssignmentResultEnum::ROLE_NOT_FOUND, $result->result);
    }

    public function testGetRole(): void
    {
        $roleId = new RoleIdDTO('role_123');
        $viewDTO = new RoleViewDTO($roleId, 'Admin');

        $this->roleQueryRepo->expects(self::once())
            ->method('getById')
            ->with($roleId)
            ->willReturn($viewDTO);

        $result = $this->orchestrator->getRole($roleId);
        self::assertSame($viewDTO, $result);
    }

    public function testListRoles(): void
    {
        $pagination = new PaginationDTO(1, 10);
        $listDTO = new RoleListDTO(
            new RoleListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->roleQueryRepo->expects(self::once())
            ->method('list')
            ->with($pagination)
            ->willReturn($listDTO);

        $result = $this->orchestrator->listRoles($pagination);
        self::assertSame($listDTO, $result);
    }

    public function testGetPermission(): void
    {
        $permId = new PermissionIdDTO('perm_123');
        $viewDTO = new PermissionViewDTO($permId, 'read');

        $this->permissionQueryRepo->expects(self::once())
            ->method('getById')
            ->with($permId)
            ->willReturn($viewDTO);

        $result = $this->orchestrator->getPermission($permId);
        self::assertSame($viewDTO, $result);
    }

    public function testListPermissions(): void
    {
        $pagination = new PaginationDTO(1, 10);
        $listDTO = new PermissionListDTO(
            new PermissionListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->permissionQueryRepo->expects(self::once())
            ->method('list')
            ->with($pagination)
            ->willReturn($listDTO);

        $result = $this->orchestrator->listPermissions($pagination);
        self::assertSame($listDTO, $result);
    }
}
