<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 18:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types = 1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions\Guard;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;
use Maatify\AdminInfra\Core\Sessions\Guard\DefaultSessionGuard;
use Maatify\AdminInfra\Core\Sessions\SessionLifecycleEvaluator;
use Maatify\AdminInfra\Core\Sessions\SessionUsageEvaluator;
use PHPUnit\Framework\TestCase;use Maatify\AdminInfra\Tests\Support\Spies\SessionPolicySpy;

final class DefaultSessionGuardTest extends TestCase
{
    private SessionPolicySpy $policySpy;
    private DefaultSessionGuard $guard;

    protected function setUp(): void
    {
        $this->policySpy = new SessionPolicySpy();

        $lifecycleEvaluator = new SessionLifecycleEvaluator($this->policySpy);
        $usageEvaluator    = new SessionUsageEvaluator($lifecycleEvaluator);
        $this->guard       = new DefaultSessionGuard($usageEvaluator);
    }

    public function testMappingAllowed(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);

        $result = $this->guard->allow(
            $this->createSession(),
            new DateTimeImmutable()
        );

        $this->assertSame(SessionGuardResultEnum::ALLOWED, $result);
    }

    public function testMappingDeniedExpired(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::EXPIRED);

        $result = $this->guard->allow(
            $this->createSession(),
            new DateTimeImmutable()
        );

        $this->assertSame(SessionGuardResultEnum::DENIED_EXPIRED, $result);
    }

    public function testMappingDeniedRevoked(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::REVOKED);

        $result = $this->guard->allow(
            $this->createSession(),
            new DateTimeImmutable()
        );

        $this->assertSame(SessionGuardResultEnum::DENIED_REVOKED, $result);
    }

    public function testDelegationContract(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);

        $session = $this->createSession();
        $now     = new DateTimeImmutable();

        $this->guard->allow($session, $now);

        $this->assertSame(1, $this->policySpy->getCallCount());
        $this->assertSame($session, $this->policySpy->getLastSession());
        $this->assertSame($now, $this->policySpy->getLastNow());
    }

    private function createSession(): SessionViewDTO
    {
        return new SessionViewDTO(
            new SessionIdDTO('sess'),
            new AdminIdDTO('admin'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            false
        );
    }
}

