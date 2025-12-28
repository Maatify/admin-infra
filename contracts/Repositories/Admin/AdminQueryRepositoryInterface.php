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

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusCriteriaDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminListDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;

interface AdminQueryRepositoryInterface
{
    /**
     * Fetch a single admin view by identifier.
     */
    public function getById(AdminIdDTO $adminId): AdminViewDTO|NotFoundResultDTO;

    /**
     * Fetch admins matching a status criteria with pagination.
     */
    public function getByStatus(
        AdminStatusCriteriaDTO $criteria,
        PaginationDTO $pagination
    ): AdminListDTO;
}
