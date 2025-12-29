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

namespace Maatify\AdminInfra\Core\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionUsageDecisionEnum;

final class SessionUsageEvaluator
{
    public function __construct(private readonly SessionLifecycleEvaluator $lifecycleEvaluator)
    {
    }

    public function decide(SessionViewDTO $session, DateTimeImmutable $now): SessionUsageDecisionEnum
    {
        $status = $this->lifecycleEvaluator->evaluate($session, $now);

        return match ($status) {
            SessionStatusEnum::ACTIVE => SessionUsageDecisionEnum::ALLOWED,
            SessionStatusEnum::EXPIRED => SessionUsageDecisionEnum::DENIED_EXPIRED,
            SessionStatusEnum::REVOKED => SessionUsageDecisionEnum::DENIED_REVOKED,
        };
    }
}
