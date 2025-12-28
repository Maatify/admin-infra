<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 03:47
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\Repositories\System;

use Maatify\AdminInfra\Contracts\DTO\System\SettingKeyDTO;
use Maatify\AdminInfra\Contracts\DTO\System\View\SystemSettingViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;

interface SystemSettingsQueryRepositoryInterface
{
    public function get(
        SettingKeyDTO $key
    ): SystemSettingViewDTO|NotFoundResultDTO;
}
