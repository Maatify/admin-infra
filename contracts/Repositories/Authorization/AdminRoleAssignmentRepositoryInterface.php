<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:26
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\Authorization;

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\AssignRoleToAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RevokeRoleFromAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\AdminRoleAssignmentResultDTO;

interface AdminRoleAssignmentRepositoryInterface
{
    public function assign(
        AssignRoleToAdminCommandDTO $command
    ): AdminRoleAssignmentResultDTO;

    public function revoke(
        RevokeRoleFromAdminCommandDTO $command
    ): AdminRoleAssignmentResultDTO;
}
