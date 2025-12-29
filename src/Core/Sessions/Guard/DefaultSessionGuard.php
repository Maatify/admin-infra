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

namespace Maatify\AdminInfra\Core\Sessions\Guard;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardInterface;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionUsageDecisionEnum;
use Maatify\AdminInfra\Core\Sessions\SessionUsageEvaluator;

final class DefaultSessionGuard implements SessionGuardInterface
{
    public function __construct(private readonly SessionUsageEvaluator $usageEvaluator)
    {
    }

    public function allow(SessionViewDTO $session, DateTimeImmutable $now): SessionGuardResultEnum
    {
        $decision = $this->usageEvaluator->decide($session, $now);

        return match ($decision) {
            SessionUsageDecisionEnum::ALLOWED => SessionGuardResultEnum::ALLOWED,
            SessionUsageDecisionEnum::DENIED_EXPIRED => SessionGuardResultEnum::DENIED_EXPIRED,
            SessionUsageDecisionEnum::DENIED_REVOKED => SessionGuardResultEnum::DENIED_REVOKED,
        };
    }
}
