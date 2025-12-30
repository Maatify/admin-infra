<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-31 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\DTO\Totp;

enum TotpVerifyFailureEnum: string
{
    case FEATURE_DISABLED = 'feature_disabled';
    case ADMIN_NOT_FOUND = 'admin_not_found';
    case NOT_ENABLED = 'not_enabled';
    case INVALID_CODE = 'invalid_code';
    case CODE_EXPIRED = 'code_expired';
}
