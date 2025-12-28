<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\Admin;

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Command\AddAdminContactCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\View\AdminContactListDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Result\ContactCommandResultDTO;

interface AdminContactsRepositoryInterface
{
    /**
     * Add a new contact channel to an admin.
     */
    public function add(
        AddAdminContactCommandDTO $command
    ): ContactCommandResultDTO;

    /**
     * Fetch all contact channels for an admin.
     */
    public function listByAdmin(
        AdminIdDTO $adminId
    ): AdminContactListDTO|NotFoundResultDTO;
}
