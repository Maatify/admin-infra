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
use PHPUnit\Framework\TestCase;

class EmergencyModeManagerTest extends TestCase
{
    private EmergencyModeManagerTest_SpySessionStorage $storage;
    private EmergencyModeManagerTest_SpyAuditLogger $auditLogger;
    private EmergencyModeManagerTest_SpyNotificationDispatcher $notificationDispatcher;
    private EmergencyModeManager $manager;

    protected function setUp(): void
    {
        $this->storage = new EmergencyModeManagerTest_SpySessionStorage();
        $this->auditLogger = new EmergencyModeManagerTest_SpyAuditLogger();
        $this->notificationDispatcher = new EmergencyModeManagerTest_SpyNotificationDispatcher();

        // Inject dependencies into the concrete SessionRevocationService
        $revocationService = new SessionRevocationService(
            $this->storage,
            $this->auditLogger,
            $this->notificationDispatcher
        );

        $this->manager = new EmergencyModeManager(
            $revocationService,
            $this->auditLogger,
            $this->notificationDispatcher
        );
    }

    public function testEnableRevokesAllSessionsAndEmitsEvents(): void
    {
        // Setup existing sessions
        $sess1Id = 'sess_1';
        $sess2Id = 'sess_2';
        $admin1 = new AdminIdDTO('100');
        $admin2 = new AdminIdDTO('200');

        $this->storage->existingSessions[$sess1Id] = new SessionInfoDTO($sess1Id, $admin1, 'dev1', '1.1.1.1', 'UA', new DateTimeImmutable(), new DateTimeImmutable(), null);
        $this->storage->existingSessions[$sess2Id] = new SessionInfoDTO($sess2Id, $admin2, 'dev2', '1.1.1.1', 'UA', new DateTimeImmutable(), new DateTimeImmutable(), null);

        $sessionIds = [new SessionIdDTO($sess1Id), new SessionIdDTO($sess2Id)];
        $revokedAt = new DateTimeImmutable();
        $systemActor = new AdminIdDTO('999');

        // Act
        $this->manager->enable($sessionIds, $revokedAt, $systemActor);

        // Assert Storage Revocations
        $this->assertCount(2, $this->storage->revokedSessions);
        $this->assertContains($sess1Id, $this->storage->revokedSessions);
        $this->assertContains($sess2Id, $this->storage->revokedSessions);

        // Verify revokedBy
        $this->assertArrayHasKey('999', $this->storage->revokedBy);
        $this->assertSame(['999' => '999'], $this->storage->revokedBy);

        // Assert Audit Events
        // 1. Session Revoked (sess_1)
        // 2. Session Revoked (sess_2)
        // 3. Emergency Mode Enabled
        $this->assertGreaterThanOrEqual(3, count($this->auditLogger->securityEvents));

        $revokedEvents = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'session_revoked');
        $this->assertCount(2, $revokedEvents);

        $emergencyEvent = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyEvent);
        $this->assertInstanceOf(AuditSecurityEventDTO::class, reset($emergencyEvent));
        $this->assertSame('999', reset($emergencyEvent)->adminId->id);

        // Assert Notifications
        // 1. Session Revoked (admin1)
        // 2. Session Revoked (admin2)
        // 3. Emergency Mode Enabled
        $this->assertGreaterThanOrEqual(3, count($this->notificationDispatcher->notifications));

        $emergencyNotification = array_filter($this->notificationDispatcher->notifications, fn($n) => $n instanceof NotificationDTO && $n->type === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyNotification);
        $this->assertInstanceOf(NotificationDTO::class, reset($emergencyNotification));
        $this->assertSame('critical', reset($emergencyNotification)->severity);
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
}

class EmergencyModeManagerTest_SpySessionStorage implements SessionStorageInterface
{
    /** @var array<string, SessionInfoDTO> */
    public array $existingSessions = [];
    /** @var array<string> */
    public array $revokedSessions = [];
    /** @var array<string, string> */
    public array $revokedBy = [];

    public function create(SessionCreateDTO $dto): string
    {
        return 'id';
    }

    public function get(string $sessionId): ?SessionInfoDTO
    {
        return $this->existingSessions[$sessionId] ?? null;
    }

    public function touch(string $sessionId): void
    {
    }

    public function revoke(string $sessionId, ?string $revokedByAdminId): void
    {
        $this->revokedSessions[] = $sessionId;
        if ($revokedByAdminId !== null) {
            $this->revokedBy[$revokedByAdminId] = $revokedByAdminId;
        }
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
