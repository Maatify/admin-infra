<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\DTO\Authorization\Result;

enum AdminRoleAssignmentResultEnum: string
{
    case SUCCESS = 'success';
    case ADMIN_NOT_FOUND = 'admin_not_found';
    case ROLE_NOT_FOUND = 'role_not_found';
    case ALREADY_ASSIGNED = 'already_assigned';
    case NOT_ASSIGNED = 'not_assigned';
}
