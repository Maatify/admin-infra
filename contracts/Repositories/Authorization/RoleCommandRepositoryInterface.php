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

use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\CreateRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Command\RenameRoleCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\Result\RoleCommandResultDTO;

interface RoleCommandRepositoryInterface
{
    public function create(
        CreateRoleCommandDTO $command
    ): RoleCommandResultDTO;

    public function rename(
        RenameRoleCommandDTO $command
    ): RoleCommandResultDTO;
}
