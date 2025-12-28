<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:06
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\Admin;

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialFetchResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialUpdateCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialUpdateResultDTO;

interface AdminCredentialsRepositoryInterface
{
    /**
     * Fetch credential material required for authentication.
     */
    public function fetchForAuth(
        AdminIdDTO $adminId
    ): CredentialFetchResultDTO;

    /**
     * Update credential material (e.g. password hash).
     */
    public function update(
        CredentialUpdateCommandDTO $command
    ): CredentialUpdateResultDTO;
}
