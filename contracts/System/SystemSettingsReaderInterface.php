<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-27 21:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Contracts\System;

use Maatify\AdminInfra\Contracts\System\DTO\SystemSettingDTO;

interface SystemSettingsReaderInterface
{
    public function has(string $key): bool;

    public function get(string $key): ?SystemSettingDTO;

    public function getString(string $key): ?string;

    public function getInt(string $key): ?int;

    public function getBool(string $key): ?bool;
}
