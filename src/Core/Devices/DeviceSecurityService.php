<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 06:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Devices;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\DeviceIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\DeviceViewDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;

final class DeviceSecurityService
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
        private readonly SessionRevocationService $sessionRevoker,
    ) {
    }

    public function notifyNewDevice(DeviceViewDTO $device, AdminIdDTO $adminId, DateTimeImmutable $seenAt, array $deviceMetadata): void
    {
        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'new_device_detected',
            (int) $adminId->id,
            new AuditContextDTO([
                new AuditContextItemDTO('device_id', $device->deviceId->id),
                new AuditContextItemDTO('fingerprint', $device->fingerprint),
            ]),
            new AuditMetadataDTO($this->convertMetadata($deviceMetadata)),
            $seenAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'new_device_detected',
            'warning',
            new NotificationTargetDTO((int) $adminId->id),
            'New device detected',
            'A login from a new device requires review.',
            $seenAt
        ));
    }

    /** @param SessionIdDTO[] $sessionIds */
    public function revokeDevice(DeviceIdDTO $deviceId, array $sessionIds, ?int $revokedByAdminId, DateTimeImmutable $revokedAt): void
    {
        if ($revokedByAdminId === null) {
            throw new \InvalidArgumentException('Revoking a device requires an explicit actor admin id.');
        }

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'device_revoked',
            $revokedByAdminId,
            new AuditContextDTO([
                new AuditContextItemDTO('device_id', $deviceId->id),
            ]),
            new AuditMetadataDTO([]),
            $revokedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'device_revoked',
            'warning',
            new NotificationTargetDTO($revokedByAdminId),
            'Device revoked',
            'A device was revoked and related sessions were terminated.',
            $revokedAt
        ));

        foreach ($sessionIds as $sessionId) {
            $this->sessionRevoker->revoke($sessionId, $revokedByAdminId, $revokedAt);
        }
    }

    private function convertMetadata(array $metadata): array
    {
        $items = [];

        foreach ($metadata as $key => $value) {
            $items[] = new AuditContextItemDTO((string) $key, is_scalar($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $items;
    }
}
