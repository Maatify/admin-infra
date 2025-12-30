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
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Core\Sessions\SessionRevocationService;

final class EmergencyModeManager
{
    private const SYSTEM_ACTOR_ID = -1;

    public function __construct(
        private readonly SessionRevocationService $sessionRevoker,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
    ) {
    }

    /** @param SessionIdDTO[] $activeSessionIds */
    public function enable(array $activeSessionIds, DateTimeImmutable $revokedAt, ?int $systemActorAdminId = null): void
    {
        $actorAdminId = $this->resolveActorId($systemActorAdminId);

        foreach ($activeSessionIds as $sessionId) {
            $this->sessionRevoker->revoke($sessionId, $actorAdminId, $revokedAt);
        }

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'emergency_mode_enabled',
            $actorAdminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $revokedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'emergency_mode_enabled',
            'critical',
            new NotificationTargetDTO($actorAdminId),
            'Emergency mode activated',
            'Emergency security mode has been enabled and all sessions were revoked.',
            $revokedAt
        ));
    }

    public function disable(DateTimeImmutable $occurredAt, ?int $systemActorAdminId = null): void
    {
        $actorAdminId = $this->resolveActorId($systemActorAdminId);

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'emergency_mode_disabled',
            $actorAdminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $occurredAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'emergency_mode_disabled',
            'info',
            new NotificationTargetDTO($actorAdminId),
            'Emergency mode deactivated',
            'Emergency security mode has been disabled.',
            $occurredAt
        ));
    }

    private function resolveActorId(?int $systemActorAdminId): int
    {
        return $systemActorAdminId ?? self::SYSTEM_ACTOR_ID;
    }
}
