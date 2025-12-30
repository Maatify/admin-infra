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

namespace Maatify\AdminInfra\Core\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;

final class SessionRevocationService
{
    public function __construct(
        private readonly SessionStorageInterface $storage,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
    ) {
    }

    public function revoke(SessionIdDTO $sessionId, ?int $revokedByAdminId, DateTimeImmutable $revokedAt): SessionCommandResultDTO
    {
        $session = $this->storage->get($sessionId->id);

        if ($session === null) {
            return new SessionCommandResultDTO(SessionCommandResultEnum::SESSION_NOT_FOUND);
        }

        $this->storage->revoke($sessionId->id, $revokedByAdminId);

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'session_revoked',
            $session->adminId,
            new AuditContextDTO([
                new AuditContextItemDTO('session_id', $sessionId->id),
                new AuditContextItemDTO('device_id', $session->deviceId),
            ]),
            new AuditMetadataDTO([]),
            $revokedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'session_revoked',
            'warning',
            new NotificationTargetDTO($session->adminId),
            'Session revoked',
            'Your session has been revoked.',
            $revokedAt
        ));

        return new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);
    }
}
