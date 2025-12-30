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

namespace Maatify\AdminInfra\Core\Totp;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;

interface TotpRepositoryInterface
{
    public function get(AdminIdDTO $adminId): ?TotpRecord;

    public function saveEnrollment(AdminIdDTO $adminId, string $secret, DateTimeImmutable $enrolledAt): void;

    public function activate(AdminIdDTO $adminId, DateTimeImmutable $verifiedAt): void;

    public function disable(AdminIdDTO $adminId, DateTimeImmutable $disabledAt): void;

    public function touch(AdminIdDTO $adminId, DateTimeImmutable $usedAt): void;
}
