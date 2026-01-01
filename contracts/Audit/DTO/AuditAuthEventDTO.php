<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 01:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Audit\DTO;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;

final class AuditAuthEventDTO
{
    public function __construct(
        public readonly string $eventType,
        public readonly AdminIdDTO $adminId,
        public readonly AuditContextDTO $context,
        public readonly AuditMetadataDTO $metadata,
        public readonly DateTimeImmutable $occurredAt
    )
    {
    }
}

