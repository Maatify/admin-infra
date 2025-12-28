<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 04:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

/**
 * Core Orchestration skeleton for authorization flows.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\AssignRoleToAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\CreateRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RenameRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RevokeRoleFromAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\PermissionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\RoleIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\PermissionListDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\PermissionViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleListDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\AdminRoleAssignmentResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\AdminRoleAssignmentResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\RoleCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\RoleCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\AdminRoleAssignmentRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\PermissionQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\RoleCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Authorization\RoleQueryRepositoryInterface;

/**
 * Coordinates role and permission orchestration while enforcing read-first then write
 * sequencing. Delegates all policy evaluation and persistence to contracts.
 */
final class AuthorizationOrchestrator
{
    public function __construct(
        private readonly RoleQueryRepositoryInterface $roleQueryRepo,
        private readonly RoleCommandRepositoryInterface $roleCommandRepo,
        private readonly PermissionQueryRepositoryInterface $permissionQueryRepo,
        private readonly AdminRoleAssignmentRepositoryInterface $roleAssignmentRepo
    )
    {
    }

    public function createRole(CreateRoleCommandDTO $command): RoleCommandResultDTO
    {
        $existing = $this->roleQueryRepo->getById($command->roleId);

        if ($existing instanceof RoleViewDTO) {
            return new RoleCommandResultDTO(RoleCommandResultEnum::ROLE_ALREADY_EXISTS);
        }

        return $this->roleCommandRepo->create($command);
    }

    public function renameRole(RenameRoleCommandDTO $command): RoleCommandResultDTO
    {
        $existing = $this->roleQueryRepo->getById($command->roleId);

        if ($existing instanceof NotFoundResultDTO) {
            return new RoleCommandResultDTO(RoleCommandResultEnum::ROLE_NOT_FOUND);
        }

        return $this->roleCommandRepo->rename($command);
    }

    public function assignRoleToAdmin(
        AssignRoleToAdminCommandDTO $command
    ): AdminRoleAssignmentResultDTO
    {
        $role = $this->roleQueryRepo->getById($command->roleId);

        if ($role instanceof NotFoundResultDTO) {
            return new AdminRoleAssignmentResultDTO(
                AdminRoleAssignmentResultEnum::ROLE_NOT_FOUND
            );
        }

        return $this->roleAssignmentRepo->assign($command);
    }

    public function revokeRoleFromAdmin(
        RevokeRoleFromAdminCommandDTO $command
    ): AdminRoleAssignmentResultDTO
    {
        $role = $this->roleQueryRepo->getById($command->roleId);

        if ($role instanceof NotFoundResultDTO) {
            return new AdminRoleAssignmentResultDTO(
                AdminRoleAssignmentResultEnum::ROLE_NOT_FOUND
            );
        }

        return $this->roleAssignmentRepo->revoke($command);
    }

    public function getRole(RoleIdDTO $roleId): RoleViewDTO|NotFoundResultDTO
    {
        return $this->roleQueryRepo->getById($roleId);
    }

    public function listRoles(PaginationDTO $pagination): RoleListDTO
    {
        return $this->roleQueryRepo->list($pagination);
    }

    public function getPermission(
        PermissionIdDTO $permissionId
    ): PermissionViewDTO|NotFoundResultDTO
    {
        return $this->permissionQueryRepo->getById($permissionId);
    }

    public function listPermissions(PaginationDTO $pagination): PermissionListDTO
    {
        return $this->permissionQueryRepo->list($pagination);
    }
}
