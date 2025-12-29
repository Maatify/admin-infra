<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 19:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Support\Spies;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardInterface;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardResultEnum;

/**
 * Spy for SessionGuardInterface
 *
 * Used exclusively for unit testing the Session Enforcement layer.
 * Tracks delegation correctness and allows controlled return values.
 */
final class SessionGuardSpy implements SessionGuardInterface
{
    /** @var SessionGuardResultEnum */
    public SessionGuardResultEnum $resultToReturn = SessionGuardResultEnum::ALLOWED;

    /** @var int */
    private int $callCount = 0;

    /** @var SessionViewDTO|null */
    private ?SessionViewDTO $lastSession = null;

    /** @var DateTimeImmutable|null */
    private ?DateTimeImmutable $lastNow = null;

    public function allow(SessionViewDTO $session, DateTimeImmutable $now): SessionGuardResultEnum
    {
        $this->callCount++;
        $this->lastSession = $session;
        $this->lastNow = $now;

        return $this->resultToReturn;
    }

    // ----------------------------
    // Assertions helpers
    // ----------------------------

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
