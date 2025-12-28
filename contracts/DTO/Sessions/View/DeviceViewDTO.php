<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:39
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\DTO\Sessions\View;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\DeviceIdDTO;

final class DeviceViewDTO
{
    public function __construct(
        public readonly DeviceIdDTO $deviceId,
        public readonly string $fingerprint,
        public readonly DateTimeImmutable $approvedAt,
        public readonly bool $isRevoked
    )
    {
    }
}
