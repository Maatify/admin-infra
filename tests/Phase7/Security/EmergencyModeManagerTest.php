<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Security;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionCreateDTO;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionInfoDTO;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;
use Maatify\AdminInfra\Core\Security\EmergencyModeManager;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmergencyModeManagerTest extends TestCase
{
    /** @var SessionRevocationService&MockObject */
    private $sessionRevoker;
    private EmergencyModeManagerTest_SpyAuditLogger $auditLogger;
    private EmergencyModeManagerTest_SpyNotificationDispatcher $notificationDispatcher;
    private EmergencyModeManager $manager;

    protected function setUp(): void
    {
        $this->sessionRevoker = $this->createMock(SessionRevocationService::class);
        $this->auditLogger = new EmergencyModeManagerTest_SpyAuditLogger();
        $this->notificationDispatcher = new EmergencyModeManagerTest_SpyNotificationDispatcher();

        $this->manager = new EmergencyModeManager(
            $this->sessionRevoker,
            $this->auditLogger,
            $this->notificationDispatcher
        );
    }

    public function testEnableRevokesAllSessionsAndEmitsEvents(): void
    {
        $sess1Id = 'sess_1';
        $sess2Id = 'sess_2';
        $sessionIds = [new SessionIdDTO($sess1Id), new SessionIdDTO($sess2Id)];
        $revokedAt = new DateTimeImmutable();

        // Use a default system actor (e.g., -1 represented as ID) or specific one
        // Previous behavior used -1 int. Now we use AdminIdDTO.
        // If the test implies default system actor, we should probably pass one.
        // But the method signature requires ?AdminIdDTO.
        // Let's pass a specific one to be explicit, as "default" logic is gone (NO magic IDs rule).
        $systemActor = new AdminIdDTO('999');

        $this->sessionRevoker->expects($this->exactly(2))
            ->method('revoke')
            ->with(
                $this->isInstanceOf(SessionIdDTO::class),
                $this->callback(fn($arg) => $arg instanceof AdminIdDTO && $arg->id === '999'),
                $revokedAt
            );

        // Act
        $this->manager->enable($sessionIds, $revokedAt, $systemActor);

        // Assert Audit Events
        $emergencyEvent = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyEvent);
        $this->assertInstanceOf(AuditSecurityEventDTO::class, reset($emergencyEvent));
        $this->assertSame('999', reset($emergencyEvent)->adminId->id);

        // Assert Notifications
        $emergencyNotification = array_filter($this->notificationDispatcher->notifications, fn($n) => $n instanceof NotificationDTO && $n->type === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyNotification);
        $this->assertInstanceOf(NotificationDTO::class, reset($emergencyNotification));
        $this->assertSame('critical', reset($emergencyNotification)->severity);
    }

    public function testEnableWithNullSystemActor(): void
    {
        $sessionIds = [new SessionIdDTO('s1')];
        $revokedAt = new DateTimeImmutable();

        // Revoke should still be called
        $this->sessionRevoker->expects($this->once())
            ->method('revoke')
            ->with($this->isInstanceOf(SessionIdDTO::class), null, $revokedAt);

        $this->manager->enable($sessionIds, $revokedAt, null);

        // Verify NO audit or notification
        $this->assertEmpty($this->auditLogger->securityEvents);
        $this->assertEmpty($this->notificationDispatcher->notifications);
    }

    public function testDisableEmitsEventsOnly(): void
    {
        $occurredAt = new DateTimeImmutable();
        $systemActor = new AdminIdDTO('888');

        $this->manager->disable($occurredAt, $systemActor);

        // Audit
        $this->assertCount(1, $this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertInstanceOf(AuditSecurityEventDTO::class, $event);
        $this->assertSame('emergency_mode_disabled', $event->eventType);
        $this->assertSame('888', $event->adminId->id);

        // Notification
        $this->assertCount(1, $this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertInstanceOf(NotificationDTO::class, $notification);
        $this->assertSame('emergency_mode_disabled', $notification->type);
        $this->assertNotNull($notification->target);
        $this->assertSame('888', $notification->target->adminId->id);
    }

    public function testDisableWithNullActor(): void
    {
        $occurredAt = new DateTimeImmutable();
        $this->manager->disable($occurredAt, null);

        $this->assertEmpty($this->auditLogger->securityEvents);
        $this->assertEmpty($this->notificationDispatcher->notifications);
    }
}

class EmergencyModeManagerTest_SpyAuditLogger implements AuditLoggerInterface
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

class EmergencyModeManagerTest_SpyNotificationDispatcher implements NotificationDispatcherInterface
{
    /** @var NotificationDTO[] */
    public array $notifications = [];

    public function dispatch(NotificationDTO $notification): void
    {
        $this->notifications[] = $notification;
    }
}
