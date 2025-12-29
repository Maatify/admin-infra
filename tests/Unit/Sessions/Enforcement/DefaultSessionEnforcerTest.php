<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions\Enforcement;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcementResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Guard\SessionGuardResultEnum;
use Maatify\AdminInfra\Core\Sessions\Enforcement\DefaultSessionEnforcer;
use Maatify\AdminInfra\Tests\Support\Spies\SessionGuardSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultSessionEnforcer::class)]
final class DefaultSessionEnforcerTest extends TestCase
{
    private SessionGuardSpy $spy;
    private DefaultSessionEnforcer $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spy = new SessionGuardSpy();
        $this->sut = new DefaultSessionEnforcer($this->spy);
    }

    public function testMappingAllowedToAllowed(): void
    {
        // TC-01 Mapping: ALLOWED → ALLOWED
        $this->spy->resultToReturn = SessionGuardResultEnum::ALLOWED;
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->sut->enforce($dto, $now);

        $this->assertSame(SessionEnforcementResultEnum::ALLOWED, $result);
    }

    public function testMappingDeniedExpiredToBlockedExpired(): void
    {
        // TC-02 Mapping: DENIED_EXPIRED → BLOCKED_EXPIRED
        $this->spy->resultToReturn = SessionGuardResultEnum::DENIED_EXPIRED;
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->sut->enforce($dto, $now);

        $this->assertSame(SessionEnforcementResultEnum::BLOCKED_EXPIRED, $result);
    }

    public function testMappingDeniedRevokedToBlockedRevoked(): void
    {
        // TC-03 Mapping: DENIED_REVOKED → BLOCKED_REVOKED
        $this->spy->resultToReturn = SessionGuardResultEnum::DENIED_REVOKED;
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result = $this->sut->enforce($dto, $now);

        $this->assertSame(SessionEnforcementResultEnum::BLOCKED_REVOKED, $result);
    }

    public function testDelegationContract(): void
    {
        // TC-04 Delegation Contract
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $this->sut->enforce($dto, $now);

        $this->assertSame(1, $this->spy->getCallCount(), 'Guard should be called exactly once');
        $this->assertSame($dto, $this->spy->getLastSession(), 'Same SessionViewDTO instance should be passed');
        $this->assertSame($now, $this->spy->getLastNow(), 'Same DateTimeImmutable instance should be passed');
    }

    public function testDeterministicBehavior(): void
    {
        // TC-05 Deterministic Behavior: same input → same output
        $this->spy->resultToReturn = SessionGuardResultEnum::ALLOWED;
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $result1 = $this->sut->enforce($dto, $now);
        $result2 = $this->sut->enforce($dto, $now);

        $this->assertSame($result1, $result2);
    }

    public function testStatelessness(): void
    {
        // TC-06 Statelessness: changing spy return value changes output
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $this->spy->resultToReturn = SessionGuardResultEnum::ALLOWED;
        $result1 = $this->sut->enforce($dto, $now);
        $this->assertSame(SessionEnforcementResultEnum::ALLOWED, $result1);

        $this->spy->resultToReturn = SessionGuardResultEnum::DENIED_EXPIRED;
        $result2 = $this->sut->enforce($dto, $now);
        $this->assertSame(SessionEnforcementResultEnum::BLOCKED_EXPIRED, $result2);
    }

    public function testDtoImmutability(): void
    {
        // TC-07 DTO Immutability: SessionViewDTO is not mutated
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $this->sut->enforce($dto, $now);

        // Since properties are readonly, we verify reference integrity to ensure no mutation attempt on the object itself
        $this->assertSame($dto, $this->spy->getLastSession());
    }

    public function testExhaustiveMapping(): void
    {
        // TC-08 Exhaustive Mapping: iterate all SessionGuardResultEnum::cases()
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        foreach (SessionGuardResultEnum::cases() as $case) {
            $this->spy->resultToReturn = $case;
            $result = $this->sut->enforce($dto, $now);
            $this->assertInstanceOf(SessionEnforcementResultEnum::class, $result);
        }
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
