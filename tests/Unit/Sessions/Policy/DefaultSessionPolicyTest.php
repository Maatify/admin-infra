<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions\Policy;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enum\SessionStatusEnum;
use Maatify\AdminInfra\Core\Sessions\Policy\DefaultSessionPolicy;
use Maatify\AdminInfra\Core\Sessions\Policy\SessionPolicyConfigDTO;
use PHPUnit\Framework\TestCase;

final class DefaultSessionPolicyTest extends TestCase
{
    private function createSession(
        DateTimeImmutable $createdAt,
        DateTimeImmutable $lastActivityAt,
        bool $isRevoked = false
    ): SessionViewDTO {
        return new SessionViewDTO(
            new SessionIdDTO('s1'),
            new AdminIdDTO('a1'),
            $createdAt,
            $lastActivityAt,
            $isRevoked
        );
    }

    private function createPolicy(?int $ttlSeconds, ?int $inactivitySeconds): DefaultSessionPolicy
    {
        return new DefaultSessionPolicy(
            new SessionPolicyConfigDTO($ttlSeconds, $inactivitySeconds)
        );
    }

    // TC-01 Revoked Session Always Returns REVOKED
    public function testRevokedSessionAlwaysReturnsRevoked(): void
    {
        $now = new DateTimeImmutable();
        $createdAt = $now->modify('-1 hour');
        $lastActivityAt = $now->modify('-1 hour');

        $session = $this->createSession($createdAt, $lastActivityAt, true);
        $policy = $this->createPolicy(null, null);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::REVOKED, $result);
    }

    // TC-02 Active Session With No TTL And No Inactivity
    public function testActiveSessionWithNoTtlAndNoInactivity(): void
    {
        $now = new DateTimeImmutable();
        $createdAt = $now->modify('-1 hour');
        $lastActivityAt = $now->modify('-1 hour');

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        $policy = $this->createPolicy(null, null);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::ACTIVE, $result);
    }

    // TC-03 Session Expires By TTL
    public function testSessionExpiresByTtl(): void
    {
        $now = new DateTimeImmutable();
        // Created 61 seconds ago, TTL is 60
        $createdAt = $now->modify('-61 seconds');
        $lastActivityAt = $now; // Activity doesn't matter for TTL expiration

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        $policy = $this->createPolicy(60, null);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::EXPIRED, $result);
    }

    // TC-04 Session Still Active Within TTL
    public function testSessionStillActiveWithinTtl(): void
    {
        $now = new DateTimeImmutable();
        // Created 59 seconds ago, TTL is 60
        $createdAt = $now->modify('-59 seconds');
        $lastActivityAt = $now;

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        $policy = $this->createPolicy(60, null);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::ACTIVE, $result);
    }

    // TC-05 Session Expires By Inactivity
    public function testSessionExpiresByInactivity(): void
    {
        $now = new DateTimeImmutable();
        $createdAt = $now->modify('-2 hours');
        // Last activity 61 seconds ago, inactivity limit is 60
        $lastActivityAt = $now->modify('-61 seconds');

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        $policy = $this->createPolicy(null, 60);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::EXPIRED, $result);
    }

    // TC-06 Session Active Within Inactivity Window
    public function testSessionActiveWithinInactivityWindow(): void
    {
        $now = new DateTimeImmutable();
        $createdAt = $now->modify('-2 hours');
        // Last activity 59 seconds ago, inactivity limit is 60
        $lastActivityAt = $now->modify('-59 seconds');

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        $policy = $this->createPolicy(null, 60);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::ACTIVE, $result);
    }

    // TC-07 TTL And Inactivity Both Configured (TTL Wins)
    public function testTtlAndInactivityBothConfiguredTtlWins(): void
    {
        $now = new DateTimeImmutable();
        // Created 61 seconds ago (TTL expired), Last activity 10 seconds ago (Inactivity valid)
        $createdAt = $now->modify('-61 seconds');
        $lastActivityAt = $now->modify('-10 seconds');

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        // TTL 60, Inactivity 300
        $policy = $this->createPolicy(60, 300);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::EXPIRED, $result);
    }

    // TC-08 Inactivity Triggers Even If TTL Valid
    public function testInactivityTriggersEvenIfTtlValid(): void
    {
        $now = new DateTimeImmutable();
        // Created 10 seconds ago (TTL valid), Last activity 61 seconds ago (Inactivity expired)
        $createdAt = $now->modify('-10 seconds');
        $lastActivityAt = $now->modify('-61 seconds');

        $session = $this->createSession($createdAt, $lastActivityAt, false);
        // TTL 300, Inactivity 60
        $policy = $this->createPolicy(300, 60);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::EXPIRED, $result);
    }

    // TC-09 Revoked Has Absolute Precedence
    public function testRevokedHasAbsolutePrecedence(): void
    {
        $now = new DateTimeImmutable();
        // Created and active long ago, normally active or expired depending on config
        // But it is revoked.
        $createdAt = $now->modify('-1000 seconds');
        $lastActivityAt = $now->modify('-1000 seconds');

        $session = $this->createSession($createdAt, $lastActivityAt, true);
        // Config that would normally expire it (e.g. 60s)
        $policy = $this->createPolicy(60, 60);

        $result = $policy->evaluate($session, $now);

        $this->assertSame(SessionStatusEnum::REVOKED, $result);
    }

    // TC-10 Deterministic Output (Same Input → Same Output)
    public function testDeterministicOutput(): void
    {
        $now = new DateTimeImmutable();
        $createdAt = $now->modify('-1 hour');
        $lastActivityAt = $now->modify('-10 minutes');
        $isRevoked = false;

        $session = $this->createSession($createdAt, $lastActivityAt, $isRevoked);
        $policy = $this->createPolicy(3600, 600); // Created exactly 1hr ago (on edge), Activity exact 10m ago (on edge)

        // Wait, edge cases?
        // 3600 seconds ago > 3600? No, 3600 == 3600.
        // The implementation uses > (strictly greater).
        // $nowTimestamp > ($createdAtTimestamp + $ttlSeconds)

        // Let's use clear values to ensure no edge case confusion for determinism test,
        // though strictly the test just wants same output for same input twice.

        $result1 = $policy->evaluate($session, $now);
        $result2 = $policy->evaluate($session, $now);

        $this->assertSame($result1, $result2);

        // Let's try with a revoked one
        $sessionRevoked = $this->createSession($createdAt, $lastActivityAt, true);
        $result3 = $policy->evaluate($sessionRevoked, $now);
        $result4 = $policy->evaluate($sessionRevoked, $now);

        $this->assertSame($result3, $result4);
    }
}
