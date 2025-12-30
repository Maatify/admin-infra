<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionCreateDTO;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionInfoDTO;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;
use PHPUnit\Framework\TestCase;

class SessionRevocationServiceTest extends TestCase
{
    private SessionRevocationServiceTest_SpySessionStorage $storage;
    private SessionRevocationServiceTest_SpyAuditLogger $auditLogger;
    private SessionRevocationServiceTest_SpyNotificationDispatcher $notificationDispatcher;
    private SessionRevocationService $service;

    protected function setUp(): void
    {
        $this->storage = new SessionRevocationServiceTest_SpySessionStorage();
        $this->auditLogger = new SessionRevocationServiceTest_SpyAuditLogger();
        $this->notificationDispatcher = new SessionRevocationServiceTest_SpyNotificationDispatcher();

        $this->service = new SessionRevocationService(
            $this->storage,
            $this->auditLogger,
            $this->notificationDispatcher
        );
    }

    public function testRevokeSessionNotFound(): void
    {
        $sessionId = new SessionIdDTO('sess_unknown');
        $this->storage->existingSessions = []; // No sessions

        $result = $this->service->revoke($sessionId, 123, new DateTimeImmutable());

        $this->assertSame(SessionCommandResultEnum::SESSION_NOT_FOUND, $result->result);
        $this->assertFalse($this->storage->revokeCalled);
        $this->assertEmpty($this->auditLogger->securityEvents);
        $this->assertEmpty($this->notificationDispatcher->notifications);
    }

    public function testRevokeSuccess(): void
    {
        $sessionIdStr = 'sess_123';
        $sessionId = new SessionIdDTO($sessionIdStr);
        $adminId = 999;
        $deviceId = 'dev_123';

        $sessionInfo = new SessionInfoDTO(
            $sessionIdStr,
            $adminId,
            $deviceId,
            '127.0.0.1',
            'UA',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null
        );

        $this->storage->existingSessions[$sessionIdStr] = $sessionInfo;

        $revokedBy = 123;
        $revokedAt = new DateTimeImmutable();

        $result = $this->service->revoke($sessionId, $revokedBy, $revokedAt);

        $this->assertSame(SessionCommandResultEnum::SUCCESS, $result->result);
        $this->assertTrue($this->storage->revokeCalled);
        $this->assertSame($sessionIdStr, $this->storage->lastRevokedSessionId);
        $this->assertSame($revokedBy, $this->storage->lastRevokedByAdminId);

        // Audit
        $this->assertCount(1, $this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertSame('session_revoked', $event->eventName);
        $this->assertSame($adminId, $event->adminId); // Should log the session owner ID
        $this->assertSame($sessionIdStr, $event->context->items[0]->value);
        $this->assertSame($deviceId, $event->context->items[1]->value);

        // Notification
        $this->assertCount(1, $this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertSame('session_revoked', $notification->type);
        $this->assertSame($adminId, $notification->target->adminId);
    }
}

class SessionRevocationServiceTest_SpySessionStorage implements SessionStorageInterface
{
    /** @var array<string, SessionInfoDTO> */
    public array $existingSessions = [];
    public bool $revokeCalled = false;
    public ?string $lastRevokedSessionId = null;
    public ?int $lastRevokedByAdminId = null;

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
        $this->revokeCalled = true;
        $this->lastRevokedSessionId = $sessionId;
        $this->lastRevokedByAdminId = $revokedByAdminId;
    }
}

class SessionRevocationServiceTest_SpyAuditLogger implements AuditLoggerInterface
{
    /** @var AuditSecurityEventDTO[] */
    public array $securityEvents = [];

    public function logAuth(AuditAuthEventDTO $event): void
    {
    }

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
        $this->securityEvents[] = $event;
    }

    public function logAction(AuditActionDTO $event): void
    {
    }

    public function logView(AuditViewDTO $event): void
    {
    }
}

class SessionRevocationServiceTest_SpyNotificationDispatcher implements NotificationDispatcherInterface
{
    /** @var NotificationDTO[] */
    public array $notifications = [];

    public function dispatch(NotificationDTO $notification): void
    {
        $this->notifications[] = $notification;
    }
}
