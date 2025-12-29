<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-30 00:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Support\Spies;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcerInterface;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcementResultEnum;

/**
 * Spy for SessionEnforcerInterface
 *
 * Used exclusively for unit testing the Session Access layer.
 * Tracks delegation correctness and allows controlled return values.
 */
final class SessionEnforcerSpy implements SessionEnforcerInterface
{
    public SessionEnforcementResultEnum $resultToReturn = SessionEnforcementResultEnum::ALLOWED;

    public int $callCount = 0;

    public ?SessionViewDTO $lastSession = null;

    public ?DateTimeImmutable $lastNow = null;

    public function setResult(SessionEnforcementResultEnum $result): void
    {
        $this->resultToReturn = $result;
    }

    public function enforce(SessionViewDTO $session, DateTimeImmutable $now): SessionEnforcementResultEnum
    {
        $this->callCount++;
        $this->lastSession = $session;
        $this->lastNow = $now;

        return $this->resultToReturn;
    }
}
