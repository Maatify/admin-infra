<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Unit\Sessions\Access;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Sessions\Access\SessionAccessResultEnum;
use Maatify\AdminInfra\Contracts\Sessions\Enforcement\SessionEnforcementResultEnum;
use Maatify\AdminInfra\Core\Sessions\Access\DefaultSessionAccessService;
use Maatify\AdminInfra\Tests\Support\Spies\SessionEnforcerSpy;
use PHPUnit\Framework\TestCase;

final class DefaultSessionAccessServiceTest extends TestCase
{
    private SessionEnforcerSpy $spy;
    private DefaultSessionAccessService $service;

    protected function setUp(): void
    {
        $this->spy = new SessionEnforcerSpy();
        $this->service = new DefaultSessionAccessService($this->spy);
    }

    public function testMappingAllowedToAllowed(): void
    {
        $this->spy->setResult(SessionEnforcementResultEnum::ALLOWED);
        $result = $this->service->canUse($this->createSessionViewDTO(), new DateTimeImmutable());
        $this->assertSame(SessionAccessResultEnum::ALLOWED, $result);
    }

    public function testMappingBlockedExpiredToBlockedExpired(): void
    {
        $this->spy->setResult(SessionEnforcementResultEnum::BLOCKED_EXPIRED);
        $result = $this->service->canUse($this->createSessionViewDTO(), new DateTimeImmutable());
        $this->assertSame(SessionAccessResultEnum::BLOCKED_EXPIRED, $result);
    }

    public function testMappingBlockedRevokedToBlockedRevoked(): void
    {
        $this->spy->setResult(SessionEnforcementResultEnum::BLOCKED_REVOKED);
        $result = $this->service->canUse($this->createSessionViewDTO(), new DateTimeImmutable());
        $this->assertSame(SessionAccessResultEnum::BLOCKED_REVOKED, $result);
    }

    public function testDelegationContract(): void
    {
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable('2025-01-01 12:00:00');

        $this->service->canUse($dto, $now);

        $this->assertSame(1, $this->spy->callCount);
        $this->assertSame($dto, $this->spy->lastSession);
        $this->assertSame($now, $this->spy->lastNow);
    }

    public function testDeterministicBehavior(): void
    {
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();
        $this->spy->setResult(SessionEnforcementResultEnum::ALLOWED);

        $result1 = $this->service->canUse($dto, $now);
        $result2 = $this->service->canUse($dto, $now);

        $this->assertSame($result1, $result2);
        $this->assertSame(2, $this->spy->callCount);
    }

    public function testStatelessness(): void
    {
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();

        $this->spy->setResult(SessionEnforcementResultEnum::ALLOWED);
        $this->assertSame(SessionAccessResultEnum::ALLOWED, $this->service->canUse($dto, $now));

        $this->spy->setResult(SessionEnforcementResultEnum::BLOCKED_EXPIRED);
        $this->assertSame(SessionAccessResultEnum::BLOCKED_EXPIRED, $this->service->canUse($dto, $now));
    }

    public function testDtoImmutability(): void
    {
        $dto = $this->createSessionViewDTO();
        $now = new DateTimeImmutable();
        $this->service->canUse($dto, $now);
        $this->assertSame($dto, $this->spy->lastSession);
    }

    public function testExhaustiveMapping(): void
    {
        foreach (SessionEnforcementResultEnum::cases() as $case) {
            $this->spy->setResult($case);
            $result = $this->service->canUse($this->createSessionViewDTO(), new DateTimeImmutable());

            $expected = match ($case) {
                SessionEnforcementResultEnum::ALLOWED => SessionAccessResultEnum::ALLOWED,
                SessionEnforcementResultEnum::BLOCKED_EXPIRED => SessionAccessResultEnum::BLOCKED_EXPIRED,
                SessionEnforcementResultEnum::BLOCKED_REVOKED => SessionAccessResultEnum::BLOCKED_REVOKED,
            };

            $this->assertSame($expected, $result, "Mapping failed for case: " . $case->name);
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
