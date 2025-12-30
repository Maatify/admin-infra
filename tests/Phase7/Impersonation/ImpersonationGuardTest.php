<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Impersonation;

use DateInterval;
use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\Authorization\AbilityResolverInterface;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityContextDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDecisionResultDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StartImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StopImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultEnum;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Core\Impersonation\ImpersonationGuard;
use PHPUnit\Framework\TestCase;

class ImpersonationGuardTest extends TestCase
{
    private ImpersonationGuardTest_SpyAbilityResolver $abilityResolver;
    private ImpersonationGuardTest_SpyExecutionContext $executionContext;
    private ImpersonationGuardTest_SpyAuditLogger $auditLogger;
    private ImpersonationGuardTest_SpyNotificationDispatcher $notificationDispatcher;

    protected function setUp(): void
    {
        $this->abilityResolver = new ImpersonationGuardTest_SpyAbilityResolver();
        $this->executionContext = new ImpersonationGuardTest_SpyExecutionContext();
        $this->auditLogger = new ImpersonationGuardTest_SpyAuditLogger();
        $this->notificationDispatcher = new ImpersonationGuardTest_SpyNotificationDispatcher();
    }

    public function testStartDeniedIfAlreadyImpersonating(): void
    {
        $guard = $this->createGuard();
        $command = new StartImpersonationCommandDTO(
            new AdminIdDTO(123),
            new DateTimeImmutable()
        );

        $result = $guard->start($command, true, null);

        $this->assertSame(ImpersonationResultEnum::NOT_ALLOWED, $result->result);
        $this->assertEmpty($this->auditLogger->securityEvents);
    }

    public function testStartDeniedIfAbilityDenied(): void
    {
        $guard = $this->createGuard();
        $this->abilityResolver->allow = false; // Deny ability

        $command = new StartImpersonationCommandDTO(
            new AdminIdDTO(123),
            new DateTimeImmutable()
        );

        $result = $guard->start($command, false, null);

        $this->assertSame(ImpersonationResultEnum::NOT_ALLOWED, $result->result);
        $this->assertEmpty($this->auditLogger->securityEvents);
    }

    public function testStartSuccess(): void
    {
        $guard = $this->createGuard();
        $this->abilityResolver->allow = true;

        $targetAdminId = 123;
        $command = new StartImpersonationCommandDTO(
            new AdminIdDTO($targetAdminId),
            new DateTimeImmutable()
        );

        $result = $guard->start($command, false, null);

        $this->assertSame(ImpersonationResultEnum::STARTED, $result->result);

        // Audit
        $this->assertCount(1, $this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertSame('impersonation_started', $event->eventName);
        $this->assertSame($this->executionContext->getActorAdminId()->id, $event->adminId);
        $this->assertSame($targetAdminId, $event->context->items[0]->value);

        // Notification
        $this->assertCount(1, $this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertSame('impersonation_started', $notification->type);
        $this->assertSame($targetAdminId, $notification->target->adminId); // Should notify target
    }

    public function testStartRespectsMaxDuration(): void
    {
        $maxDuration = new DateInterval('PT1H');
        $guard = $this->createGuard($maxDuration);
        $this->abilityResolver->allow = true;

        $command = new StartImpersonationCommandDTO(
            new AdminIdDTO(123),
            new DateTimeImmutable()
        );
        $requestedExpiry = new DateTimeImmutable(); // now

        $guard->start($command, false, $requestedExpiry);

        $event = $this->auditLogger->securityEvents[0];
        // expires_at should be requested + maxDuration
        $expectedExpiry = $requestedExpiry->add($maxDuration)->format(DATE_ATOM);
        $actualExpiry = $event->context->items[1]->value;

        $this->assertSame($expectedExpiry, $actualExpiry);
    }

    public function testStopDeniedIfNoActiveImpersonation(): void
    {
        $guard = $this->createGuard();
        $command = new StopImpersonationCommandDTO(new DateTimeImmutable());

        $result = $guard->stop($command, false);

        $this->assertSame(ImpersonationResultEnum::NOT_ALLOWED, $result->result);
        $this->assertEmpty($this->auditLogger->securityEvents);
    }

    public function testStopSuccess(): void
    {
        $guard = $this->createGuard();
        $command = new StopImpersonationCommandDTO(new DateTimeImmutable());

        $result = $guard->stop($command, true);

        $this->assertSame(ImpersonationResultEnum::STOPPED, $result->result);

        // Audit
        $this->assertCount(1, $this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertSame('impersonation_stopped', $event->eventName);
        $this->assertSame($this->executionContext->getActorAdminId()->id, $event->adminId);

        // Notification
        $this->assertCount(1, $this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertSame('impersonation_stopped', $notification->type);
        $this->assertSame($this->executionContext->getActorAdminId()->id, $notification->target->adminId);
    }

    private function createGuard(?DateInterval $maxDuration = null): ImpersonationGuard
    {
        return new ImpersonationGuard(
            $this->abilityResolver,
            $this->executionContext,
            $this->auditLogger,
            $this->notificationDispatcher,
            $maxDuration
        );
    }
}

class ImpersonationGuardTest_SpyAbilityResolver implements AbilityResolverInterface
{
    public bool $allow = false;

    public function can(AdminIdDTO $actorAdminId, AbilityDTO $ability, AbilityContextDTO $context): AbilityDecisionResultDTO
    {
        return new AbilityDecisionResultDTO($this->allow);
    }
}

class ImpersonationGuardTest_SpyExecutionContext implements AdminExecutionContextInterface
{
    public function getActorAdminId(): AdminIdDTO
    {
        return new AdminIdDTO(999);
    }
}

class ImpersonationGuardTest_SpyAuditLogger implements AuditLoggerInterface
{
    /** @var AuditSecurityEventDTO[] */
    public array $securityEvents = [];

    public function logAuth(AuditAuthEventDTO $event): void {}

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
        $this->securityEvents[] = $event;
    }

    public function logAction(AuditActionDTO $event): void {}
    public function logView(AuditViewDTO $event): void {}
}

class ImpersonationGuardTest_SpyNotificationDispatcher implements NotificationDispatcherInterface
{
    /** @var NotificationDTO[] */
    public array $notifications = [];

    public function dispatch(NotificationDTO $notification): void
    {
        $this->notifications[] = $notification;
    }
}
