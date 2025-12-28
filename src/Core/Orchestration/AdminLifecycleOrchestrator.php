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
 * Core Orchestration skeleton for admin lifecycle flows.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusCriteriaDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Command\ChangeAdminStatusCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Command\CreateAdminCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Result\AdminCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\Result\AdminCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminListDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\View\AdminViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Command\AddAdminContactCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Result\ContactCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\Result\ContactCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Contact\View\AdminContactCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Contact\View\AdminContactListDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationDTO;
use Maatify\AdminInfra\Contracts\Notifications\DTO\NotificationTargetDTO;
use Maatify\AdminInfra\Contracts\Notifications\NotificationDispatcherInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminContactsRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminQueryRepositoryInterface;

/**
 * Coordinates admin identity lifecycle orchestration including creation, status
 * transitions, and contact channel management.
 *
 * Sequences and coordinates the following contracts without defining wiring,
 * instantiation, or lifecycle management in this phase:
 * - AdminQueryRepositoryInterface
 * - AdminCommandRepositoryInterface
 * - AdminContactsRepositoryInterface
 * - AuditLoggerInterface
 * - NotificationDispatcherInterface
 *
 * Non-responsibilities:
 * - Does not persist admin state directly or manage session storage.
 * - Does not enforce authorization decisions for lifecycle changes.
 * - Does not deliver notifications or audit messages synchronously.
 */
final class AdminLifecycleOrchestrator
{
    public function __construct(
        private readonly AdminQueryRepositoryInterface $queryRepo,
        private readonly AdminCommandRepositoryInterface $commandRepo,
        private readonly AdminContactsRepositoryInterface $contactsRepo,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly NotificationDispatcherInterface $notificationDispatcher
    ) {
    }

    /**
     * Sequences admin identity creation across contracts while maintaining lifecycle
     * invariants and orchestration boundaries.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function createAdmin(CreateAdminCommandDTO $command): AdminCommandResultDTO
    {
        $result = $this->commandRepo->create($command);

        if ($result->result === AdminCommandResultEnum::SUCCESS) {
            $this->auditLogger->logAction(new AuditActionDTO(
                'admin_created',
                0, // System/Unknown actor
                'admin',
                (int)$command->adminId->id,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                $command->createdAt
            ));

            $this->notificationDispatcher->dispatch(new NotificationDTO(
                'admin_created',
                'info',
                new NotificationTargetDTO((int)$command->adminId->id),
                'Admin Created',
                'A new admin account has been created.',
                $command->createdAt
            ));
        }

        return $result;
    }

    /**
     * Coordinates lifecycle status transitions for an admin entity.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function changeAdminStatus(ChangeAdminStatusCommandDTO $command): AdminCommandResultDTO
    {
        $admin = $this->queryRepo->getById($command->adminId);

        if ($admin instanceof NotFoundResultDTO) {
            return new AdminCommandResultDTO(AdminCommandResultEnum::ADMIN_NOT_FOUND);
        }

        $result = $this->commandRepo->changeStatus($command);

        if ($result->result === AdminCommandResultEnum::SUCCESS) {
            $this->auditLogger->logAction(new AuditActionDTO(
                'admin_status_changed',
                0,
                'admin',
                (int)$command->adminId->id,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                $command->changedAt
            ));

            $this->notificationDispatcher->dispatch(new NotificationDTO(
                'admin_status_changed',
                'info',
                new NotificationTargetDTO((int)$command->adminId->id),
                'Admin Status Changed',
                'The status of the admin account has been updated.',
                $command->changedAt
            ));
        }

        return $result;
    }

    /**
     * Retrieves an admin view within orchestration while preserving not-found semantics.
     */
    public function getAdmin(AdminIdDTO $adminId): AdminViewDTO|NotFoundResultDTO
    {
        $result = $this->queryRepo->getById($adminId);

        if ($result instanceof AdminViewDTO) {
            $this->auditLogger->logView(new AuditViewDTO(
                'admin_profile',
                0,
                new AuditContextDTO([]),
                new \DateTimeImmutable()
            ));
        }

        return $result;
    }

    /**
     * Lists admins filtered by lifecycle criteria with strict pagination boundaries.
     */
    public function listAdmins(AdminStatusCriteriaDTO $criteria, PaginationDTO $pagination): AdminListDTO
    {
        $result = $this->queryRepo->getByStatus($criteria, $pagination);

        $this->auditLogger->logView(new AuditViewDTO(
            'admin_list',
            0,
            new AuditContextDTO([]),
            new \DateTimeImmutable()
        ));

        return $result;
    }

    /**
     * Coordinates addition of contact channels to an admin identity without selecting
     * delivery mechanisms.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function addAdminContact(AddAdminContactCommandDTO $command): ContactCommandResultDTO
    {
        $admin = $this->queryRepo->getById($command->adminId);

        if ($admin instanceof NotFoundResultDTO) {
            return new ContactCommandResultDTO(ContactCommandResultEnum::ADMIN_NOT_FOUND);
        }

        $result = $this->contactsRepo->add($command);

        if ($result->result === ContactCommandResultEnum::SUCCESS) {
            $this->auditLogger->logAction(new AuditActionDTO(
                'admin_contact_added',
                0,
                'admin_contact',
                (int)$command->adminId->id,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                new \DateTimeImmutable()
            ));
        }

        return $result;
    }

    /**
     * Lists contact channels for an admin identity without performing notification logic.
     */
    public function listAdminContacts(AdminIdDTO $adminId): AdminContactListDTO
    {
        $admin = $this->queryRepo->getById($adminId);

        if ($admin instanceof NotFoundResultDTO) {
            return new AdminContactListDTO(new AdminContactCollectionDTO([]));
        }

        $result = $this->contactsRepo->listByAdmin($adminId);

        $this->auditLogger->logView(new AuditViewDTO(
            'admin_contact_list',
            0,
            new AuditContextDTO([]),
            new \DateTimeImmutable()
        ));

        return $result;
    }
}
