<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\Authorization;

use Maatify\AdminInfra\Contracts\DTO\Authorization\RoleIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Authorization\View\RoleListDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;

interface RoleQueryRepositoryInterface
{
    public function getById(
        RoleIdDTO $roleId
    ): RoleViewDTO|NotFoundResultDTO;

    public function list(
        PaginationDTO $pagination
    ): RoleListDTO;
}
