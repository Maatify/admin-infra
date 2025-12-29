<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionUsageDecisionEnum;
use Maatify\AdminInfra\Contracts\Sessions\Policy\SessionPolicyInterface;
use Maatify\AdminInfra\Core\Sessions\SessionLifecycleEvaluator;
use Maatify\AdminInfra\Core\Sessions\SessionUsageEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Maatify\AdminInfra\Core\Sessions\SessionUsageEvaluator
 */
class SessionUsageEvaluatorTest extends TestCase
{
    private SessionPolicySpy $policySpy;
    private SessionUsageEvaluator $usageEvaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policySpy = new SessionPolicySpy();

        // SessionLifecycleEvaluator is final, so we cannot mock it directly.
        // However, it is a thin wrapper around SessionPolicyInterface.
        // By injecting a Spy Policy into the real LifecycleEvaluator,
        // we can control the lifecycle result and verify delegation.
        // This effectively treats the combination of LifecycleEvaluator + PolicySpy as the "Double".
        $lifecycleEvaluator = new SessionLifecycleEvaluator($this->policySpy);
        $this->usageEvaluator = new SessionUsageEvaluator($lifecycleEvaluator);
    }

    /**
     * TC-01, TC-02, TC-03, TC-07
     * @dataProvider statusProvider
     */
    public function testDecideMapsStatusesToDecisions(
        SessionStatusEnum $lifecycleStatus,
        SessionUsageDecisionEnum $expectedDecision
    ): void
    {
        $this->policySpy->setReturnValue($lifecycleStatus);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable('2025-01-01 12:05:00'); // 5 mins after session start

        $decision = $this->usageEvaluator->decide($session, $now);

        $this->assertSame($expectedDecision, $decision);
    }

    /**
     * @return array<string, array{SessionStatusEnum, SessionUsageDecisionEnum}>
     */
    public static function statusProvider(): array
    {
        return [
            'Active -> Allowed' => [SessionStatusEnum::ACTIVE, SessionUsageDecisionEnum::ALLOWED],
            'Expired -> Denied Expired' => [SessionStatusEnum::EXPIRED, SessionUsageDecisionEnum::DENIED_EXPIRED],
            'Revoked -> Denied Revoked' => [SessionStatusEnum::REVOKED, SessionUsageDecisionEnum::DENIED_REVOKED],
        ];
    }

    /**
     * TC-07: Enum exhaustiveness
     */
    public function testEnumExhaustiveness(): void
    {
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable('2025-01-01 12:05:00');
        $testedCases = [];

        foreach (SessionStatusEnum::cases() as $status) {
            $this->policySpy->setReturnValue($status);

            // This will throw UnhandledMatchError if the case is not covered in decide()
            $result = $this->usageEvaluator->decide($session, $now);

            $this->assertInstanceOf(SessionUsageDecisionEnum::class, $result);
            $testedCases[] = $status;
        }

        // Verify we actually tested as many cases as exist (double check against loop)
        $this->assertCount(count(SessionStatusEnum::cases()), $testedCases);
    }

    /**
     * TC-04
     */
    public function testEvaluateIsCalledExactlyOnce(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable('2025-01-01 12:05:00');

        $this->usageEvaluator->decide($session, $now);

        $this->assertSame(1, $this->policySpy->getCallCount());
        $this->assertSame($session, $this->policySpy->getLastSession());
        $this->assertSame($now, $this->policySpy->getLastNow());
    }

    /**
     * TC-05
     */
    public function testDeterministicBehavior(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO();
        $now = new DateTimeImmutable('2025-01-01 12:05:00');

        $decision1 = $this->usageEvaluator->decide($session, $now);
        $decision2 = $this->usageEvaluator->decide($session, $now);

        $this->assertSame($decision1, $decision2);
        $this->assertSame(2, $this->policySpy->getCallCount());
    }

    /**
     * TC-06
     */
    public function testSessionViewDTOIsNotMutated(): void
    {
        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);
        $session = $this->createSessionViewDTO();
        $originalSession = clone $session;
        $now = new DateTimeImmutable('2025-01-01 12:05:00');

        $this->usageEvaluator->decide($session, $now);

        $this->assertEquals($originalSession, $session);
        // Verify identity passed to policy
        $this->assertSame($session, $this->policySpy->getLastSession());
    }

    /**
     * TC-08
     */
    public function testPurity(): void
    {
        // Purity: No state retained between calls
        $session1 = $this->createSessionViewDTO();
        $now1 = new DateTimeImmutable('2025-01-01 12:05:00');

        $this->policySpy->setReturnValue(SessionStatusEnum::ACTIVE);
        $result1 = $this->usageEvaluator->decide($session1, $now1);
        $this->assertSame(SessionUsageDecisionEnum::ALLOWED, $result1);

        // Second call with different inputs
        $session2 = $this->createSessionViewDTO();
        $now2 = new DateTimeImmutable('2025-01-01 13:00:00');
        $this->policySpy->setReturnValue(SessionStatusEnum::EXPIRED);

        $result2 = $this->usageEvaluator->decide($session2, $now2);
        $this->assertSame(SessionUsageDecisionEnum::DENIED_EXPIRED, $result2);

        $this->assertSame(2, $this->policySpy->getCallCount());
    }

    private function createSessionViewDTO(): SessionViewDTO
    {
        return new SessionViewDTO(
            new SessionIdDTO('sess_test'),
            new AdminIdDTO('admin_test'),
            new DateTimeImmutable('2025-01-01 12:00:00'),
            new DateTimeImmutable('2025-01-01 12:30:00'),
            false
        );
    }
}

class SessionPolicySpy implements SessionPolicyInterface
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
