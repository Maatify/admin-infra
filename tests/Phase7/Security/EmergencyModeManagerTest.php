<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Security;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
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
        $admin1 = 100;
        $admin2 = 200;

        $this->storage->existingSessions[$sess1Id] = new SessionInfoDTO($sess1Id, $admin1, 'dev1', '1.1.1.1', 'UA', new DateTimeImmutable(), new DateTimeImmutable(), null);
        $this->storage->existingSessions[$sess2Id] = new SessionInfoDTO($sess2Id, $admin2, 'dev2', '1.1.1.1', 'UA', new DateTimeImmutable(), new DateTimeImmutable(), null);

        $sessionIds = [new SessionIdDTO($sess1Id), new SessionIdDTO($sess2Id)];
        $revokedAt = new DateTimeImmutable();

        // Act
        $this->manager->enable($sessionIds, $revokedAt);

        // Assert Storage Revocations
        $this->assertCount(2, $this->storage->revokedSessions);
        $this->assertContains($sess1Id, $this->storage->revokedSessions);
        $this->assertContains($sess2Id, $this->storage->revokedSessions);

        // Verify revokedBy is -1 (default system actor)
        $this->assertSame(-1, $this->storage->revokedBy[-1]);

        // Assert Audit Events
        // 1. Session Revoked (sess_1)
        // 2. Session Revoked (sess_2)
        // 3. Emergency Mode Enabled
        $this->assertGreaterThanOrEqual(3, count($this->auditLogger->securityEvents));

        $revokedEvents = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'session_revoked');
        $this->assertCount(2, $revokedEvents);

        $emergencyEvent = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyEvent);
        $this->assertSame(-1, reset($emergencyEvent)->adminId);

        // Assert Notifications
        // 1. Session Revoked (admin1)
        // 2. Session Revoked (admin2)
        // 3. Emergency Mode Enabled (admin -1)
        $this->assertGreaterThanOrEqual(3, count($this->notificationDispatcher->notifications));

        $emergencyNotification = array_filter($this->notificationDispatcher->notifications, fn($n) => $n instanceof NotificationDTO && $n->type === 'emergency_mode_enabled');
        $this->assertCount(1, $emergencyNotification);
        $this->assertSame('critical', reset($emergencyNotification)->severity);
    }

    public function testEnableWithCustomSystemActor(): void
    {
        $this->storage->existingSessions['s1'] = new SessionInfoDTO('s1', 1, 'd', 'i', 'u', new DateTimeImmutable(), new DateTimeImmutable(), null);

        $this->manager->enable([new SessionIdDTO('s1')], new DateTimeImmutable(), 999);

        // Verify revocation used 999
        $this->assertSame(999, $this->storage->revokedBy[999]);

        // Verify Emergency Event uses 999
        $emergencyEvent = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'emergency_mode_enabled');
        $this->assertSame(999, reset($emergencyEvent)->adminId);
    }

    public function testDisableEmitsEventsOnly(): void
    {
        $occurredAt = new DateTimeImmutable();

        $this->manager->disable($occurredAt);

        // Audit
        $this->assertCount(1, $this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertInstanceOf(AuditSecurityEventDTO::class, $event);
        $this->assertSame('emergency_mode_disabled', $event->eventType);
        $this->assertSame(-1, $event->adminId);

        // Notification
        $this->assertCount(1, $this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertInstanceOf(NotificationDTO::class, $notification);
        $this->assertSame('emergency_mode_disabled', $notification->type);
        $this->assertNotNull($notification->target);
        $this->assertSame(-1, $notification->target->adminId);
    }
}

class EmergencyModeManagerTest_SpySessionStorage implements SessionStorageInterface
{
    /** @var array<string, SessionInfoDTO> */
    public array $existingSessions = [];
    /** @var array<string> */
    public array $revokedSessions = [];
    /** @var array<int, int> */
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

    public function revoke(string $sessionId, ?int $revokedByAdminId): void
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
