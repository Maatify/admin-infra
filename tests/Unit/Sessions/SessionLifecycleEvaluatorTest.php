<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;
use Maatify\AdminInfra\Core\Sessions\SessionLifecycleEvaluator;
use Maatify\AdminInfra\Tests\Support\Spies\SessionPolicySpy;
use PHPUnit\Framework\TestCase;
class SessionLifecycleEvaluatorTest extends TestCase
{
    private SessionPolicySpy $policy;
    private SessionLifecycleEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SessionPolicySpy();
        $this->evaluator = new SessionLifecycleEvaluator($this->policy);
    }

    public function testActiveSessionReturnsActive(): void
    {
        // TC-01 Active Session
        $this->policy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->evaluator->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::ACTIVE, $result);
    }

    public function testRevokedSessionReturnsRevoked(): void
    {
        // TC-02 Revoked Session
        $this->policy->setReturnValue(SessionStatusEnum::REVOKED);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->evaluator->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::REVOKED, $result);
    }

    public function testExpiredSessionReturnsExpired(): void
    {
        // TC-03 Expired Session
        $this->policy->setReturnValue(SessionStatusEnum::EXPIRED);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->evaluator->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::EXPIRED, $result);
    }

    public function testRevokedPrecedence(): void
    {
        // TC-04 Revoked precedence
        // Policy returns REVOKED even if we might expect something else (though here we control the policy return)
        $this->policy->setReturnValue(SessionStatusEnum::REVOKED);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->evaluator->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::REVOKED, $result);
    }

    public function testPolicyDrivenOnly(): void
    {
        // TC-05 Policy-driven only
        // Policy returns fixed value regardless of session data
        $this->policy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO(); // Any session
        $now = new DateTimeImmutable();

        $result = $this->evaluator->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::ACTIVE, $result);
    }

    public function testPolicyInvocationContract(): void
    {
        // TC-06 Policy invocation contract
        $this->policy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $this->evaluator->evaluate($session, $now);

        $this->assertSame(1, $this->policy->getCallCount());
        $this->assertSame($session, $this->policy->getLastSession());
        $this->assertSame($now, $this->policy->getLastNow());
    }

    private function createSessionViewDTO(): SessionViewDTO
    {
        return new SessionViewDTO(
            new SessionIdDTO('sess_123'),
            new AdminIdDTO('admin_123'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            false
        );
    }
}
