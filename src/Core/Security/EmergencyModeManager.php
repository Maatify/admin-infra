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

namespace Maatify\AdminInfra\Core\Security;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;

final class EmergencyModeManager
{
    public function __construct(
        private readonly SessionRevocationService $sessionRevoker,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
    ) {
    }

    /** @param SessionIdDTO[] $activeSessionIds */
    public function enable(array $activeSessionIds, DateTimeImmutable $revokedAt, AdminIdDTO $systemActorAdminId): void
    {
        foreach ($activeSessionIds as $sessionId) {
            $this->sessionRevoker->revoke($sessionId, $systemActorAdminId, $revokedAt);
        }

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'emergency_mode_enabled',
            $systemActorAdminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $revokedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'emergency_mode_enabled',
            'critical',
            new NotificationTargetDTO($systemActorAdminId),
            'Emergency mode activated',
            'Emergency security mode has been enabled and all sessions were revoked.',
            $revokedAt
        ));
    }

    public function disable(DateTimeImmutable $occurredAt, AdminIdDTO $systemActorAdminId): void
    {
        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'emergency_mode_disabled',
            $systemActorAdminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $occurredAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'emergency_mode_disabled',
            'info',
            new NotificationTargetDTO($systemActorAdminId),
            'Emergency mode deactivated',
            'Emergency security mode has been disabled.',
            $occurredAt
        ));
    }
}
