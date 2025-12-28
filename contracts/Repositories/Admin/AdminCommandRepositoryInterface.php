<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:05
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\Admin;

use Maatify\AdminInfra\Contracts\DTO\Admin\Command\CreateAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Command\ChangeAdminStatusCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Result\AdminCommandResultDTO;

interface AdminCommandRepositoryInterface
{
    /**
     * Create a new admin identity root.
     */
    public function create(CreateAdminCommandDTO $command): AdminCommandResultDTO;

    /**
     * Change admin lifecycle status.
     */
    public function changeStatus(
        ChangeAdminStatusCommandDTO $command
    ): AdminCommandResultDTO;
}
