<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 01:56
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Notifications\DTO;

use DateTimeImmutable;

final class NotificationDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $severity,
        public readonly NotificationTargetDTO $target,
        public readonly string $title,
        public readonly string $body,
        public readonly DateTimeImmutable $occurredAt
    )
    {
    }
}
