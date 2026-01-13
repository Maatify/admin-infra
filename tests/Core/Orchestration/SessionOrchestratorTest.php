<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Core\Orchestration;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\ApproveDeviceCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeDeviceCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StartImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\StopImpersonationCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\DeviceIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\DeviceCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\DeviceCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\ImpersonationResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\DeviceListDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\DeviceCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\DeviceQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\ImpersonationSessionRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionQueryRepositoryInterface;
use Maatify\AdminInfra\Core\Orchestration\SessionOrchestrator;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListItemCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PageMetaDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\DeviceCollectionDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SessionOrchestratorTest extends TestCase
{
    /** @var SessionCommandRepositoryInterface&MockObject */
    private $sessionCommandRepo;

    /** @var SessionQueryRepositoryInterface&MockObject */
    private $sessionQueryRepo;

    /** @var DeviceCommandRepositoryInterface&MockObject */
    private $deviceCommandRepo;

    /** @var DeviceQueryRepositoryInterface&MockObject */
    private $deviceQueryRepo;

    /** @var ImpersonationSessionRepositoryInterface&MockObject */
    private $impersonationRepo;

    private SessionOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->sessionCommandRepo = $this->createMock(SessionCommandRepositoryInterface::class);
        $this->sessionQueryRepo   = $this->createMock(SessionQueryRepositoryInterface::class);
        $this->deviceCommandRepo  = $this->createMock(DeviceCommandRepositoryInterface::class);
        $this->deviceQueryRepo    = $this->createMock(DeviceQueryRepositoryInterface::class);
        $this->impersonationRepo  = $this->createMock(ImpersonationSessionRepositoryInterface::class);

        $this->orchestrator = new SessionOrchestrator(
            $this->sessionCommandRepo,
            $this->sessionQueryRepo,
            $this->deviceCommandRepo,
            $this->deviceQueryRepo,
            $this->impersonationRepo
        );
    }

    public function testCreateSession(): void
    {
        $command = new CreateSessionCommandDTO(
            new SessionIdDTO('sess_1'),
            new AdminIdDTO('1'),
            new DateTimeImmutable(),
            'req-123',
            '127.0.0.1',
            'TestAgent'
        );

        $result = new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);

        $this->sessionCommandRepo
            ->expects(self::once())
            ->method('create')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->createSession($command));
    }

    public function testRevokeSession(): void
    {
        $command = new RevokeSessionCommandDTO(
            new SessionIdDTO('sess_1'),
            new DateTimeImmutable()
        );

        $result = new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);

        $this->sessionCommandRepo
            ->expects(self::once())
            ->method('revoke')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->revokeSession($command));
    }

    public function testApproveDevice(): void
    {
        $command = new ApproveDeviceCommandDTO(
            new DeviceIdDTO('device_1'),
            new DateTimeImmutable()
        );

        $result = new DeviceCommandResultDTO(DeviceCommandResultEnum::SUCCESS);

        $this->deviceCommandRepo
            ->expects(self::once())
            ->method('approve')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->approveDevice($command));
    }

    public function testRevokeDevice(): void
    {
        $command = new RevokeDeviceCommandDTO(
            new DeviceIdDTO('device_1'),
            new DateTimeImmutable()
        );

        $result = new DeviceCommandResultDTO(DeviceCommandResultEnum::SUCCESS);

        $this->deviceCommandRepo
            ->expects(self::once())
            ->method('revoke')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->revokeDevice($command));
    }

    public function testStartImpersonation(): void
    {
        $command = new StartImpersonationCommandDTO(
            new AdminIdDTO('1'),
            new AdminIdDTO('2'),
            new DateTimeImmutable()
        );

        $result = new ImpersonationResultDTO(ImpersonationResultEnum::STARTED);

        $this->impersonationRepo
            ->expects(self::once())
            ->method('start')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->startImpersonation($command));
    }

    public function testStopImpersonation(): void
    {
        $command = new StopImpersonationCommandDTO(
            new DateTimeImmutable()
        );

        $result = new ImpersonationResultDTO(ImpersonationResultEnum::STOPPED);

        $this->impersonationRepo
            ->expects(self::once())
            ->method('stop')
            ->with($command)
            ->willReturn($result);

        self::assertSame($result, $this->orchestrator->stopImpersonation($command));
    }

    public function testGetSession(): void
    {
        $sessionId = new SessionIdDTO('sess_1');

        $view = new SessionViewDTO(
            new SessionIdDTO('sess_1'),
            new AdminIdDTO('1'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            false
        );


        $this->sessionQueryRepo
            ->expects(self::once())
            ->method('getById')
            ->with($sessionId)
            ->willReturn($view);

        self::assertSame($view, $this->orchestrator->getSession($sessionId));
    }

    public function testGetSessionNotFound(): void
    {
        $sessionId = new SessionIdDTO('sess_x');

        $notFound = new NotFoundResultDTO(EntityTypeEnum::SESSION, 'sess_x');

        $this->sessionQueryRepo
            ->expects(self::once())
            ->method('getById')
            ->with($sessionId)
            ->willReturn($notFound);

        self::assertSame($notFound, $this->orchestrator->getSession($sessionId));
    }

    public function testListSessions(): void
    {
        $adminId = new AdminIdDTO('1');
        $pagination = new PaginationDTO(1, 10);

        $list = new SessionListDTO(
            new SessionListItemCollectionDTO([]),
            new PageMetaDTO(1, 10, 0, 0)
        );

        $this->sessionQueryRepo
            ->expects(self::once())
            ->method('listByAdmin')
            ->with($adminId, $pagination)
            ->willReturn($list);

        self::assertSame($list, $this->orchestrator->listSessions($adminId, $pagination));
    }

    public function testListDevices(): void
    {
        $adminId = new AdminIdDTO('1');

        $list = new DeviceListDTO(
            new DeviceCollectionDTO([])
        );

        $this->deviceQueryRepo
            ->expects(self::once())
            ->method('listByAdmin')
            ->with($adminId)
            ->willReturn($list);

        self::assertSame($list, $this->orchestrator->listDevices($adminId));
    }
}
