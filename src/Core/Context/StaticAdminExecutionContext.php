<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 01:23
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Context;

use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;

/**
 * StaticAdminExecutionContext
 *
 * Phase 5.1 implementation.
 *
 * - Provides a fixed AdminIdDTO as the actor identity
 * - No session access
 * - No HTTP awareness
 * - No authentication logic
 * - No impersonation handling
 * - No infrastructure dependency
 */
final class StaticAdminExecutionContext implements AdminExecutionContextInterface
{
    private AdminIdDTO $actorAdminId;

    public function __construct(AdminIdDTO $actorAdminId)
    {
        $this->actorAdminId = $actorAdminId;
    }

    public function getActorAdminId(): AdminIdDTO
    {
        return $this->actorAdminId;
    }
}


