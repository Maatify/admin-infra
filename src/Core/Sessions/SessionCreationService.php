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

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Sessions\DTO\SessionCreateDTO;
use Maatify\AdminInfra\Contracts\Sessions\SessionStorageInterface;

final class SessionCreationService
{
    public function __construct(
        private readonly SessionStorageInterface $storage,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
    ) {
    }

    /** @param array<string, scalar|null> $deviceMetadata */
    public function create(
        SessionCreateDTO $session,
        CreateSessionCommandDTO $command,
        bool $isAdminActive,
        bool $authenticationSucceeded,
        bool $totpRequired,
        bool $totpPassed,
        bool $isEmergencyMode,
        bool $isTrustedDevice,
        string $deviceFingerprint,
        array $deviceMetadata
    ): SessionCommandResultDTO {
        if (! $isAdminActive || ! $authenticationSucceeded || ($totpRequired && ! $totpPassed) || $isEmergencyMode) {
            return new SessionCommandResultDTO(SessionCommandResultEnum::NOT_ALLOWED);
        }

        $this->storage->create($session);

        $adminId = $command->adminId;

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'session_created',
            $adminId,
            new AuditContextDTO([
                new AuditContextItemDTO('session_id', $command->sessionId->id),
                new AuditContextItemDTO('device_id', $session->deviceId),
                new AuditContextItemDTO('device_fingerprint', $deviceFingerprint),
            ]),
            new AuditMetadataDTO($this->convertMetadata($deviceMetadata)),
            $command->createdAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'session_created',
            'info',
            new NotificationTargetDTO($adminId),
            'New session created',
            'A new admin session has been created.',
            $command->createdAt
        ));

        if (! $isTrustedDevice) {
            $this->notificationDispatcher->dispatch(new NotificationDTO(
                'new_device',
                'warning',
                new NotificationTargetDTO($adminId),
                'New device detected',
                'A login from a new device was detected.',
                $command->createdAt
            ));
        }

        return new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);
    }

    /**
     * @param array<string, scalar|null> $metadata
     * @return AuditContextItemDTO[]
     */
    private function convertMetadata(array $metadata): array
    {
        $items = [];

        foreach ($metadata as $key => $value) {
            $items[] = new AuditContextItemDTO((string) $key, is_scalar($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $items;
    }
}
