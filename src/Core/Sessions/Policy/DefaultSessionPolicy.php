<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 05:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Sessions\Policy;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;

final class DefaultSessionPolicy implements SessionPolicyInterface
{
    public function __construct(private readonly SessionPolicyConfigDTO $config)
    {
    }

    public function evaluate(SessionViewDTO $session, DateTimeImmutable $now): SessionStatusEnum
    {
        if ($session->isRevoked) {
            return SessionStatusEnum::REVOKED;
        }

        $createdAtTimestamp = $session->createdAt->getTimestamp();
        $lastActivityTimestamp = $session->lastActivityAt->getTimestamp();
        $nowTimestamp = $now->getTimestamp();

        $ttlExpiry = $this->config->ttlSeconds !== null ? ($createdAtTimestamp + $this->config->ttlSeconds) : null;
        if ($ttlExpiry !== null && $ttlExpiry < $createdAtTimestamp) {
            $ttlExpiry = PHP_INT_MAX;
        }

        $inactivityExpiry = $this->config->inactivitySeconds !== null ? ($lastActivityTimestamp + $this->config->inactivitySeconds) : null;
        if ($inactivityExpiry !== null && $inactivityExpiry < $lastActivityTimestamp) {
            $inactivityExpiry = PHP_INT_MAX;
        }

        $isExpiredByTtl = $ttlExpiry !== null && $nowTimestamp > $ttlExpiry;
        $isExpiredByInactivity = $inactivityExpiry !== null && $nowTimestamp > $inactivityExpiry;

        if ($isExpiredByTtl || $isExpiredByInactivity) {
            return SessionStatusEnum::EXPIRED;
        }

        return SessionStatusEnum::ACTIVE;
    }
}
