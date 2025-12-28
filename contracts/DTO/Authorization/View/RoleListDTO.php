<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\DTO\Authorization\View;

use Maatify\AdminInfra\Contracts\DTO\Common\PageMetaDTO;

final class RoleListDTO
{
    public function __construct(
        public readonly RoleListItemCollectionDTO $items,
        public readonly PageMetaDTO $meta
    )
    {
    }
}
