<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\DTO\Contact\View;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Contact\Value\ContactTypeEnum;

final class AdminContactViewDTO
{
    public function __construct(
        public readonly ContactTypeEnum $type,
        public readonly string $value,
        public readonly bool $isPrimary,
        public readonly DateTimeImmutable $createdAt
    )
    {
    }
}
