<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Phase7\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionCreateDTO;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionInfoDTO;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;
use Maatify\AdminInfra\Core\Sessions\SessionCreationService;
use PHPUnit\Framework\TestCase;

class SessionCreationServiceTest extends TestCase
{
    private SessionCreationServiceTest_SpySessionStorage $storage;
    private SessionCreationServiceTest_SpyAuditLogger $auditLogger;
    private SessionCreationServiceTest_SpyNotificationDispatcher $notificationDispatcher;
    private SessionCreationService $service;

    protected function setUp(): void
    {
        $this->storage = new SessionCreationServiceTest_SpySessionStorage();
        $this->auditLogger = new SessionCreationServiceTest_SpyAuditLogger();
        $this->notificationDispatcher = new SessionCreationServiceTest_SpyNotificationDispatcher();

        $this->service = new SessionCreationService(
            $this->storage,
            $this->auditLogger,
            $this->notificationDispatcher
        );
    }

    public function testCreateDeniedIfAdminInactive(): void
    {
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: false,
            authenticationSucceeded: true,
            totpRequired: false,
            totpPassed: false,
            isEmergencyMode: false,
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: []
        );

        $this->assertSame(SessionCommandResultEnum::NOT_ALLOWED, $result->result);
        $this->assertFalse($this->storage->createCalled);
        $this->assertEmpty($this->auditLogger->securityEvents);
        $this->assertEmpty($this->notificationDispatcher->notifications);
    }

    public function testCreateDeniedIfAuthenticationFailed(): void
    {
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: false,
            totpRequired: false,
            totpPassed: false,
            isEmergencyMode: false,
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: []
        );

        $this->assertSame(SessionCommandResultEnum::NOT_ALLOWED, $result->result);
        $this->assertFalse($this->storage->createCalled);
    }

    public function testCreateDeniedIfTotpRequiredAndFailed(): void
    {
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: true,
            totpRequired: true,
            totpPassed: false, // Failed
            isEmergencyMode: false,
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: []
        );

        $this->assertSame(SessionCommandResultEnum::NOT_ALLOWED, $result->result);
        $this->assertFalse($this->storage->createCalled);
    }

    public function testCreateDeniedIfEmergencyMode(): void
    {
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: true,
            totpRequired: false,
            totpPassed: false,
            isEmergencyMode: true, // Enabled
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: []
        );

        $this->assertSame(SessionCommandResultEnum::NOT_ALLOWED, $result->result);
        $this->assertFalse($this->storage->createCalled);
    }

    public function testCreateSuccessTrustedDevice(): void
    {
        $deviceMetadata = ['os' => 'Linux', 'browser' => 'Chrome'];
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: true,
            totpRequired: true,
            totpPassed: true,
            isEmergencyMode: false,
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: $deviceMetadata
        );

        $this->assertSame(SessionCommandResultEnum::SUCCESS, $result->result);
        $this->assertTrue($this->storage->createCalled);

        // Verify Audit
        $this->assertNotEmpty($this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertInstanceOf(AuditSecurityEventDTO::class, $event);

        $this->assertSame('session_created', $event->eventType);
        $this->assertSame(123, $event->adminId);

        $this->assertSame('sess_123', $this->findContextValue($event, 'session_id'));
        $this->assertSame('dev_123', $this->findContextValue($event, 'device_id'));
        $this->assertSame('fingerprint', $this->findContextValue($event, 'device_fingerprint'));

        // Verify Metadata conversion
        $this->assertSame('Linux', $this->findMetadataValue($event, 'os'));
        $this->assertSame('Chrome', $this->findMetadataValue($event, 'browser'));

        // Verify Notification
        $this->assertNotEmpty($this->notificationDispatcher->notifications);
        $notification = $this->notificationDispatcher->notifications[0];
        $this->assertInstanceOf(NotificationDTO::class, $notification);

        $this->assertSame('session_created', $notification->type);
        $this->assertNotNull($notification->target);
        $this->assertSame(123, $notification->target->adminId);
    }

    public function testCreateSuccessUntrustedDeviceEmitsNewDeviceNotification(): void
    {
        $result = $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: true,
            totpRequired: false,
            totpPassed: false,
            isEmergencyMode: false,
            isTrustedDevice: false, // Untrusted
            deviceFingerprint: 'fingerprint',
            deviceMetadata: []
        );

        $this->assertSame(SessionCommandResultEnum::SUCCESS, $result->result);

        // Verify Notifications
        $this->assertCount(2, $this->notificationDispatcher->notifications);

        $notification1 = $this->notificationDispatcher->notifications[0];
        $this->assertInstanceOf(NotificationDTO::class, $notification1);
        $this->assertSame('session_created', $notification1->type);

        $notification2 = $this->notificationDispatcher->notifications[1];
        $this->assertInstanceOf(NotificationDTO::class, $notification2);
        $this->assertSame('new_device', $notification2->type);
        $this->assertSame('warning', $notification2->severity);
    }

    public function testMetadataHandlesNonScalarValues(): void
    {
        // Flattened metadata
        $deviceMetadata = ['geo_lat' => 10, 'geo_lon' => 20];
        $this->service->create(
            $this->createSessionCreateDTO(),
            $this->createCommandDTO(),
            isAdminActive: true,
            authenticationSucceeded: true,
            totpRequired: false,
            totpPassed: false,
            isEmergencyMode: false,
            isTrustedDevice: true,
            deviceFingerprint: 'fingerprint',
            deviceMetadata: $deviceMetadata
        );

        $this->assertNotEmpty($this->auditLogger->securityEvents);
        $event = $this->auditLogger->securityEvents[0];
        $this->assertInstanceOf(AuditSecurityEventDTO::class, $event);

        $this->assertSame(10, $this->findMetadataValue($event, 'geo_lat'));
        $this->assertSame(20, $this->findMetadataValue($event, 'geo_lon'));
    }

    private function createSessionCreateDTO(): SessionCreateDTO
    {
        return new SessionCreateDTO(
            123,
            'dev_123',
            '127.0.0.1',
            'UserAgent',
            new DateTimeImmutable('+1 hour')
        );
    }

    private function createCommandDTO(): CreateSessionCommandDTO
    {
        return new CreateSessionCommandDTO(
            new SessionIdDTO('sess_123'),
            new AdminIdDTO('123'),
            new DateTimeImmutable()
        );
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

class SessionCreationServiceTest_SpySessionStorage implements SessionStorageInterface
{
    public bool $createCalled = false;

    public function create(SessionCreateDTO $dto): string
    {
        $this->createCalled = true;
        return 'sess_generated';
    }

    public function get(string $sessionId): ?SessionInfoDTO
    {
        return null;
    }

    public function touch(string $sessionId): void
    {
    }

    public function revoke(string $sessionId, ?int $revokedByAdminId): void
    {
    }
}

class SessionCreationServiceTest_SpyAuditLogger implements AuditLoggerInterface
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

class SessionCreationServiceTest_SpyNotificationDispatcher implements NotificationDispatcherInterface
{
    /** @var NotificationDTO[] */
    public array $notifications = [];

    public function dispatch(NotificationDTO $notification): void
    {
        $this->notifications[] = $notification;
    }
}
