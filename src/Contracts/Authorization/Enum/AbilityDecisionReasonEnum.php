<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 06:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Authorization\Enum;

enum AbilityDecisionReasonEnum: string
{
    case ALLOWED = 'allowed';
    case DENIED_NO_PERMISSION = 'denied_no_permission';
    case DENIED_IMPERSONATION_FORBIDDEN = 'denied_impersonation_forbidden';
    case DENIED_HIERARCHY_VIOLATION = 'denied_hierarchy_violation';
    case DENIED_SYSTEM_RESTRICTION = 'denied_system_restriction';
}
