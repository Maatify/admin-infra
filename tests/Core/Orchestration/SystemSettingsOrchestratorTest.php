<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-28 05:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Core\Orchestration;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Context\AdminExecutionContextInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\System\Command\SetSystemSettingCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\System\Result\SystemSettingCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\System\Result\SystemSettingCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\System\SettingKeyDTO;
use Maatify\AdminInfra\Contracts\DTO\System\SettingValueDTO;
use Maatify\AdminInfra\Contracts\DTO\System\View\SystemSettingViewDTO;
use Maatify\AdminInfra\Contracts\Repositories\System\SystemSettingsCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\System\DTO\SystemSettingDTO;
use Maatify\AdminInfra\Contracts\System\SystemSettingsReaderInterface;
use Maatify\AdminInfra\Core\Orchestration\SystemSettingsOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SystemSettingsOrchestratorTest extends TestCase
{
    /**
     * @var SystemSettingsReaderInterface&MockObject
     */
    private $reader;

    /**
     * @var SystemSettingsCommandRepositoryInterface&MockObject
     */
    private $commandRepo;

    /**
     * @var AuditLoggerInterface&MockObject
     */
    private $auditLogger;

    /**
     * @var AdminExecutionContextInterface&MockObject
     */
    private $executionContext;

    private SystemSettingsOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(SystemSettingsReaderInterface::class);
        $this->commandRepo = $this->createMock(SystemSettingsCommandRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->executionContext = $this->createMock(AdminExecutionContextInterface::class);

        $this->orchestrator = new SystemSettingsOrchestrator(
            $this->reader,
            $this->commandRepo,
            $this->auditLogger,
            $this->executionContext
        );
    }

    public function testGetSettingFound(): void
    {
        $key = new SettingKeyDTO('site_name');
        $dto = new SystemSettingDTO('site_name', 'My Site', new DateTimeImmutable());

        $this->reader->expects($this->once())
            ->method('get')
            ->with('site_name')
            ->willReturn($dto);

        $result = $this->orchestrator->getSetting($key);

        $this->assertInstanceOf(SystemSettingViewDTO::class, $result);
        $this->assertSame('site_name', $result->key->key);
        $this->assertSame('My Site', $result->value->value);
    }

    public function testGetSettingNotFound(): void
    {
        $key = new SettingKeyDTO('unknown_key');

        $this->reader->expects($this->once())
            ->method('get')
            ->with('unknown_key')
            ->willReturn(null);

        $result = $this->orchestrator->getSetting($key);

        $this->assertInstanceOf(NotFoundResultDTO::class, $result);
        $this->assertSame(EntityTypeEnum::SYSTEM_SETTING, $result->entity);
        $this->assertSame('unknown_key', $result->identifier);
    }

    public function testSetSettingNoChange(): void
    {
        $key = new SettingKeyDTO('site_name');
        $value = new SettingValueDTO('My Site');
        $command = new SetSystemSettingCommandDTO($key, $value);

        $existing = new SystemSettingDTO('site_name', 'My Site', new DateTimeImmutable());

        $this->reader->expects($this->once())
            ->method('get')
            ->with('site_name')
            ->willReturn($existing);

        $this->commandRepo->expects($this->never())->method('set');
        $this->auditLogger->expects($this->never())->method('logAction');

        $result = $this->orchestrator->setSetting($command);

        $this->assertInstanceOf(SystemSettingCommandResultDTO::class, $result);
        $this->assertSame(SystemSettingCommandResultEnum::SUCCESS, $result->result);
    }

    public function testSetSettingSuccess(): void
    {
        $key = new SettingKeyDTO('site_name');
        $value = new SettingValueDTO('New Name');
        $command = new SetSystemSettingCommandDTO($key, $value);

        $existing = new SystemSettingDTO('site_name', 'Old Name', new DateTimeImmutable());
        $actorId = new AdminIdDTO('123');

        $this->reader->expects($this->once())
            ->method('get')
            ->with('site_name')
            ->willReturn($existing);

        $this->commandRepo->expects($this->once())
            ->method('set')
            ->with($command)
            ->willReturn(new SystemSettingCommandResultDTO(SystemSettingCommandResultEnum::SUCCESS));

        $this->executionContext->expects($this->once())
            ->method('getActorAdminId')
            ->willReturn($actorId);

        $this->auditLogger->expects($this->once())
            ->method('logAction')
            ->with($this->callback(function (AuditActionDTO $dto) use ($actorId) {
                return $dto->eventType === 'system_setting_updated'
                    && $dto->actorAdminId === (int)$actorId->id
                    && $dto->targetId === null; // Global setting
            }));

        $result = $this->orchestrator->setSetting($command);

        $this->assertSame(SystemSettingCommandResultEnum::SUCCESS, $result->result);
    }
}
