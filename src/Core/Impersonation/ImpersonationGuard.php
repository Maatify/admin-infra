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

namespace Maatify\AdminInfra\Core\Impersonation;

use DateInterval;
use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Authorization\AbilityResolverInterface;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityContextDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityDTO;
use Maatify\AdminInfra\Contracts\Authorization\DTO\AbilityTargetDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StartImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StopImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultEnum;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;

final class ImpersonationGuard
{
    public function __construct(
        private readonly AbilityResolverInterface $abilityResolver,
        private readonly AdminExecutionContextInterface $executionContext,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
        private readonly ?DateInterval $maximumDuration = null,
    ) {
    }

    public function start(
        StartImpersonationCommandDTO $command,
        bool $hasActiveImpersonation,
        ?DateTimeImmutable $requestedExpiry
    ): ImpersonationResultDTO {
        $actorId = $this->executionContext->getActorAdminId();

        if ($hasActiveImpersonation) {
            return new ImpersonationResultDTO(ImpersonationResultEnum::NOT_ALLOWED);
        }

        $abilityResult = $this->abilityResolver->can(
            $actorId,
            new AbilityDTO('security.impersonate'),
            new AbilityContextDTO($actorId, new AbilityTargetDTO('admin', (string) $command->targetAdminId->id))
        );

        if (! $abilityResult->isAllowed()) {
            return new ImpersonationResultDTO(ImpersonationResultEnum::NOT_ALLOWED);
        }

        $impersonationExpiresAt = $requestedExpiry !== null ? $this->resolveExpiry($requestedExpiry) : null;

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'impersonation_started',
            (int) $actorId->id,
            new AuditContextDTO([
                new AuditContextItemDTO('target_admin_id', (int) $command->targetAdminId->id),
                new AuditContextItemDTO('expires_at', $impersonationExpiresAt?->format(DATE_ATOM)),
            ]),
            new AuditMetadataDTO([]),
            $command->startedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'impersonation_started',
            'warning',
            new NotificationTargetDTO((int) $command->targetAdminId->id),
            'Impersonation started',
            'Your account is being impersonated for support purposes.',
            $command->startedAt
        ));

        return new ImpersonationResultDTO(ImpersonationResultEnum::STARTED);
    }

    public function stop(StopImpersonationCommandDTO $command, bool $hasActiveImpersonation): ImpersonationResultDTO
    {
        if (! $hasActiveImpersonation) {
            return new ImpersonationResultDTO(ImpersonationResultEnum::NOT_ALLOWED);
        }

        $this->auditLogger->logSecurity(new AuditSecurityEventDTO(
            'impersonation_stopped',
            (int) $this->executionContext->getActorAdminId()->id,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $command->stoppedAt
        ));

        $this->notificationDispatcher->dispatch(new NotificationDTO(
            'impersonation_stopped',
            'info',
            new NotificationTargetDTO((int) $this->executionContext->getActorAdminId()->id),
            'Impersonation ended',
            'Impersonation session has ended.',
            $command->stoppedAt
        ));

        return new ImpersonationResultDTO(ImpersonationResultEnum::STOPPED);
    }

    private function resolveExpiry(DateTimeImmutable $requestedExpiry): DateTimeImmutable
    {
        if ($this->maximumDuration === null) {
            return $requestedExpiry;
        }

        return $requestedExpiry->add($this->maximumDuration);
    }
}
