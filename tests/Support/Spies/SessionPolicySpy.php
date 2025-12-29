<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 16:44
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Support\Spies;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;

final class SessionPolicySpy implements SessionPolicyInterface
{
    private SessionStatusEnum $returnValue;
    private int $callCount = 0;
    private ?SessionViewDTO $lastSession = null;
    private ?DateTimeImmutable $lastNow = null;

    public function setReturnValue(SessionStatusEnum $value): void
    {
        $this->returnValue = $value;
    }

    public function evaluate(SessionViewDTO $session, DateTimeImmutable $now): SessionStatusEnum
    {
        $this->callCount++;
        $this->lastSession = $session;
        $this->lastNow = $now;

        return $this->returnValue ?? SessionStatusEnum::ACTIVE;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function getLastSession(): ?SessionViewDTO
    {
        return $this->lastSession;
    }

    public function getLastNow(): ?DateTimeImmutable
    {
        return $this->lastNow;
    }
}
