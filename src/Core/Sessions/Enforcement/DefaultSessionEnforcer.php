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

namespace Maatify\AdminInfra\Core\Sessions\Enforcement;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcementResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcerInterface;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardInterface;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardResultEnum;

final class DefaultSessionEnforcer implements SessionEnforcerInterface
{
    public function __construct(private readonly SessionGuardInterface $guard)
    {
    }

    public function enforce(SessionViewDTO $session, DateTimeImmutable $now): SessionEnforcementResultEnum
    {
        $result = $this->guard->allow($session, $now);

        return match ($result) {
            SessionGuardResultEnum::ALLOWED => SessionEnforcementResultEnum::ALLOWED,
            SessionGuardResultEnum::DENIED_EXPIRED => SessionEnforcementResultEnum::BLOCKED_EXPIRED,
            SessionGuardResultEnum::DENIED_REVOKED => SessionEnforcementResultEnum::BLOCKED_REVOKED,
        };
    }
}
