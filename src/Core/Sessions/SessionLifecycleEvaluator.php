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
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;

final class SessionLifecycleEvaluator
{
    public function __construct(private readonly SessionPolicyInterface $policy)
    {
    }

    public function evaluate(SessionViewDTO $session, DateTimeImmutable $now): SessionStatusEnum
    {
        return $this->policy->evaluate($session, $now);
    }
}
