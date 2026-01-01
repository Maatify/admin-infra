<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Devices;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\DeviceIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\DeviceViewDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionCreateDTO;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionInfoDTO;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;
use Maatify\AdminInfra\Core\Devices\DeviceSecurityService;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;
use PHPUnit\Framework\TestCase;

class DeviceSecurityServiceTest extends TestCase
{
    private DeviceSecurityServiceTest_SpySessionStorage $storage;
    private DeviceSecurityServiceTest_SpyAuditLogger $auditLogger;
    private DeviceSecurityServiceTest_SpyNotificationDispatcher $notificationDispatcher;
    private DeviceSecurityService $service;

    protected function setUp(): void
    {
        $this->storage = new DeviceSecurityServiceTest_SpySessionStorage();
        $this->auditLogger = new DeviceSecurityServiceTest_SpyAuditLogger();
        $this->notificationDispatcher = new DeviceSecurityServiceTest_SpyNotificationDispatcher();

        $revocationService = new SessionRevocationService(
            $this->storage,
            $this->auditLogger,
            $this->notificationDispatcher
        );

        $this->service = new DeviceSecurityService(
            $this->auditLogger,
            $this->notificationDispatcher,
            $revocationService
        );
    }

    public function testNotifyNewDevice(): void
    {
        $deviceId = new DeviceIdDTO('dev_123');
        $deviceView = new DeviceViewDTO(
            $deviceId,
            'fingerprint',
            new DateTimeImmutable(),
            false
        );
        $adminId = new AdminIdDTO('123');
        $seenAt = new DateTimeImmutable();
        $metadata = ['os' => 'Linux'];

        $this->service->notifyNewDevice($deviceView, $adminId, $seenAt, $metadata);

        // Audit
        $this->assertNotEmpty($this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertInstanceOf(AuditSecurityEventDTO::class, $event);

        $this->assertSame('new_device_detected', $event->eventType);
        $this->assertSame('123', $event->adminId->id);

        $this->assertSame('dev_123', $this->findContextValue($event, 'device_id'));
        $this->assertSame('fingerprint', $this->findContextValue($event, 'fingerprint'));
        $this->assertSame('Linux', $this->findMetadataValue($event, 'os'));

        // Notification
        $this->assertNotEmpty($this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertInstanceOf(NotificationDTO::class, $notification);

        $this->assertSame('new_device_detected', $notification->type);
        $this->assertNotNull($notification->target);
        $this->assertSame('123', $notification->target->adminId->id);
    }

    public function testRevokeDeviceWithNullActorSuccess(): void
    {
        $deviceId = new DeviceIdDTO('dev_1');
        $sessionIds = [new SessionIdDTO('s1')];
        $revokedAt = new DateTimeImmutable();

        $this->service->revokeDevice($deviceId, $sessionIds, null, $revokedAt);

        // Assert Storage Revocations occurred
        // The storage spy doesn't track *who* revoked if null is passed (because array key),
        // but sessionRevoker handles null.
        // We verify that NO audit or notification happened for device_revoked.
        $deviceRevoked = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'device_revoked');
        $this->assertEmpty($deviceRevoked);

        $deviceRevokedNotif = array_filter($this->notificationDispatcher->notifications, fn($n) => $n instanceof NotificationDTO && $n->type === 'device_revoked');
        $this->assertEmpty($deviceRevokedNotif);
    }

    public function testRevokeDeviceSuccess(): void
    {
        // Setup sessions
        $sess1Id = 's1';
        $sess2Id = 's2';
        $adminId = new AdminIdDTO('123'); // owner

        $this->storage->existingSessions[$sess1Id] = new SessionInfoDTO($sess1Id, $adminId, 'd', 'i', 'u', new DateTimeImmutable(), new DateTimeImmutable(), null);
        $this->storage->existingSessions[$sess2Id] = new SessionInfoDTO($sess2Id, $adminId, 'd', 'i', 'u', new DateTimeImmutable(), new DateTimeImmutable(), null);

        $deviceId = new DeviceIdDTO('dev_123');
        $sessionIds = [new SessionIdDTO($sess1Id), new SessionIdDTO($sess2Id)];
        $revokedBy = new AdminIdDTO('999');
        $revokedAt = new DateTimeImmutable();

        $this->service->revokeDevice($deviceId, $sessionIds, $revokedBy, $revokedAt);

        // Assert Storage Revocations
        $this->assertCount(2, $this->storage->revokedSessions);
        $this->assertContains($sess1Id, $this->storage->revokedSessions);
        $this->assertContains($sess2Id, $this->storage->revokedSessions);
        $this->assertSame(['999' => '999'], $this->storage->revokedBy);

        // Audit Events
        // 1. Device Revoked (by 999)
        // 2. Session Revoked (s1 - by 999)
        // 3. Session Revoked (s2 - by 999)
        $this->assertGreaterThanOrEqual(3, count($this->auditLogger->securityEvents));

        $deviceRevoked = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'device_revoked');
        $this->assertCount(1, $deviceRevoked);
        $this->assertInstanceOf(AuditSecurityEventDTO::class, reset($deviceRevoked));
        $this->assertSame('999', reset($deviceRevoked)->adminId->id);

        $sessionRevoked = array_filter($this->auditLogger->securityEvents, fn($e) => $e instanceof AuditSecurityEventDTO && $e->eventType === 'session_revoked');
        $this->assertCount(2, $sessionRevoked);

        // Notifications
        // 1. Device Revoked (to 999 - warning)
        // 2. Session Revoked (to 123 - warning) x2
        $this->assertGreaterThanOrEqual(3, count($this->notificationDispatcher->notifications));

        $deviceRevokedNotif = array_filter($this->notificationDispatcher->notifications, fn($n) => $n instanceof NotificationDTO && $n->type === 'device_revoked');
        $this->assertCount(1, $deviceRevokedNotif);
        $this->assertInstanceOf(NotificationDTO::class, reset($deviceRevokedNotif));
        $this->assertNotNull(reset($deviceRevokedNotif)->target);
        $this->assertSame('999', reset($deviceRevokedNotif)->target->adminId->id);
    }

    private function findContextValue(AuditSecurityEventDTO $event, string $key): mixed
    {
        foreach ($event->context->items as $item) {
            if ($item->key === $key) {
                return $item->value;
            }
        }
        return null;
    }

    private function findMetadataValue(AuditSecurityEventDTO $event, string $key): mixed
    {
        foreach ($event->metadata->items as $item) {
            if ($item->key === $key) {
                return $item->value;
            }
        }
        return null;
    }
}

class DeviceSecurityServiceTest_SpySessionStorage implements SessionStorageInterface
{
    /** @var array<string, SessionInfoDTO> */
    public array $existingSessions = [];
    /** @var array<int, string> */
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

class DeviceSecurityServiceTest_SpyAuditLogger implements AuditLoggerInterface
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

class DeviceSecurityServiceTest_SpyNotificationDispatcher implements NotificationDispatcherInterface
{
    /** @var NotificationDTO[] */
    public array $notifications = [];

    public function dispatch(NotificationDTO $notification): void
    {
        $this->notifications[] = $notification;
    }
}
