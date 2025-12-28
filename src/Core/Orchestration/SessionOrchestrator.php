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
 * Core Orchestration skeleton for session lifecycle flows.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\ApproveDeviceCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeDeviceCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StartImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StopImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\DeviceCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\DeviceListDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\DeviceCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\DeviceQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\ImpersonationSessionRepositoryInterface;

final class SessionOrchestrator
{
    public function __construct(
        private readonly SessionCommandRepositoryInterface $sessionCommandRepo,
        private readonly SessionQueryRepositoryInterface $sessionQueryRepo,
        private readonly DeviceCommandRepositoryInterface $deviceCommandRepo,
        private readonly DeviceQueryRepositoryInterface $deviceQueryRepo,
        private readonly ImpersonationSessionRepositoryInterface $impersonationRepo
    )
    {
    }

    public function createSession(CreateSessionCommandDTO $command): SessionCommandResultDTO
    {
        return $this->sessionCommandRepo->create($command);
    }

    public function revokeSession(RevokeSessionCommandDTO $command): SessionCommandResultDTO
    {
        return $this->sessionCommandRepo->revoke($command);
    }

    public function approveDevice(ApproveDeviceCommandDTO $command): DeviceCommandResultDTO
    {
        return $this->deviceCommandRepo->approve($command);
    }

    public function revokeDevice(RevokeDeviceCommandDTO $command): DeviceCommandResultDTO
    {
        return $this->deviceCommandRepo->revoke($command);
    }

    public function startImpersonation(StartImpersonationCommandDTO $command): ImpersonationResultDTO
    {
        return $this->impersonationRepo->start($command);
    }

    public function stopImpersonation(StopImpersonationCommandDTO $command): ImpersonationResultDTO
    {
        return $this->impersonationRepo->stop($command);
    }

    public function getSession(SessionIdDTO $sessionId): SessionViewDTO|NotFoundResultDTO
    {
        return $this->sessionQueryRepo->getById($sessionId);
    }

    public function listSessions(AdminIdDTO $adminId, PaginationDTO $pagination): SessionListDTO
    {
        return $this->sessionQueryRepo->listByAdmin($adminId, $pagination);
    }

    public function listDevices(AdminIdDTO $adminId): DeviceListDTO
    {
        return $this->deviceQueryRepo->listByAdmin($adminId);
    }
}
