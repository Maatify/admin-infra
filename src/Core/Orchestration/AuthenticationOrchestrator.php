<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 04:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

/**
 * Core Orchestration skeleton for authentication flows.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialUpdateCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialUpdateResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Auth\CredentialUpdateResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminCredentialsRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionCommandRepositoryInterface;

/**
 * Coordinates authentication and credential lifecycle without embedding security logic.
 *
 * Coordinates authentication and credential lifecycle without embedding security logic.
 *
 * Sequences and coordinates the following contracts without defining wiring,
 * instantiation, or lifecycle management in this phase:
 * - AdminCredentialsRepositoryInterface
 * - SessionCommandRepositoryInterface
 * - SessionQueryRepositoryInterface
 * - AuditLoggerInterface
 * - NotificationDispatcherInterface
 *
 * Responsibilities:
 * - Sequence credential retrieval, verification handoff, and session issuance order.
 * - Enforce orchestration-only boundaries between authentication contracts.
 * - Emit audit and notification intents without depending on transport success.
 *
 * Non-responsibilities:
 * - Does not perform credential verification, hashing, or token generation.
 * - Does not implement authorization or policy evaluation.
 * - Does not access transport or framework layers.
 */
final class AuthenticationOrchestrator
{
    public function __construct(
        private readonly AdminCredentialsRepositoryInterface $credentialsRepo,
        private readonly SessionCommandRepositoryInterface $sessionCommandRepo,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher,
        private readonly AdminExecutionContextInterface $executionContext
    ) {
    }

    /**
     * Orchestrates the authentication flow culminating in a session creation attempt.
     * Coordinates credential fetching and lifecycle sequencing while delegating all
     * verification logic to downstream contracts.
     *
     * Side-effect intent: emits authentication audit records and non-blocking security
     * notifications without relying on their outcome.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function authenticate(CreateSessionCommandDTO $command): SessionCommandResultDTO
    {
        $result = $this->sessionCommandRepo->create($command);

        if ($result->result === SessionCommandResultEnum::SUCCESS) {
            $actorId = $this->executionContext->getActorAdminId();

            $this->auditLogger->logAction(new AuditActionDTO(
                'admin_login',
                (int)$actorId->id,
                'session',
                (int)$command->adminId->id,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                $command->createdAt
            ));

            $this->notificationDispatcher->dispatch(new NotificationDTO(
                'admin_login',
                'info',
                new NotificationTargetDTO((int)$command->adminId->id),
                'Admin Login',
                'A new session has been created.',
                $command->createdAt
            ));
        }

        return $result;
    }

    /**
     * Coordinates credential material updates while enforcing the required order of
     * operations across contracts.
     *
     * Side-effect intent: emits credential change audit records and notification intents
     * without altering the primary result flow.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function updateCredentials(CredentialUpdateCommandDTO $command): CredentialUpdateResultDTO
    {
        $result = $this->credentialsRepo->update($command);

        if ($result->result === CredentialUpdateResultEnum::SUCCESS) {
            $actorId = $this->executionContext->getActorAdminId();

            $this->auditLogger->logAction(new AuditActionDTO(
                'admin_credentials_updated',
                (int)$actorId->id,
                'credential',
                (int)$command->adminId->id,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                new \DateTimeImmutable()
            ));

            $this->notificationDispatcher->dispatch(new NotificationDTO(
                'admin_credentials_updated',
                'warning',
                new NotificationTargetDTO((int)$command->adminId->id),
                'Credentials Updated',
                'Your security credentials have been updated.',
                new \DateTimeImmutable()
            ));
        }

        return $result;
    }
}
