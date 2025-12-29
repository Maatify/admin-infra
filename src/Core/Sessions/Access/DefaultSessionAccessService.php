<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 06:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Sessions\Access;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Access\SessionAccessInterface;
use Maatify\AdminInfra\Contracts\Sessions\Access\SessionAccessResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcementResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcerInterface;

final class DefaultSessionAccessService implements SessionAccessInterface
{
    public function __construct(private readonly SessionEnforcerInterface $enforcer)
    {
    }

    public function canUse(SessionViewDTO $session, DateTimeImmutable $now): SessionAccessResultEnum
    {
        $result = $this->enforcer->enforce($session, $now);

        return match ($result) {
            SessionEnforcementResultEnum::ALLOWED => SessionAccessResultEnum::ALLOWED,
            SessionEnforcementResultEnum::BLOCKED_EXPIRED => SessionAccessResultEnum::BLOCKED_EXPIRED,
            SessionEnforcementResultEnum::BLOCKED_REVOKED => SessionAccessResultEnum::BLOCKED_REVOKED,
        };
    }
}
