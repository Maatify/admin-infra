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

    private SystemSettingsOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(SystemSettingsReaderInterface::class);
        $this->commandRepo = $this->createMock(SystemSettingsCommandRepositoryInterface::class);

        $this->orchestrator = new SystemSettingsOrchestrator(
            $this->reader,
            $this->commandRepo
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

        self::assertInstanceOf(SystemSettingViewDTO::class, $result);
        self::assertSame('site_name', $result->key->key);
        self::assertSame('My Site', $result->value->value);
    }

    public function testGetSettingNotFound(): void
    {
        $key = new SettingKeyDTO('unknown_key');

        $this->reader->expects($this->once())
            ->method('get')
            ->with('unknown_key')
            ->willReturn(null);

        $result = $this->orchestrator->getSetting($key);

        self::assertInstanceOf(NotFoundResultDTO::class, $result);
        self::assertSame(EntityTypeEnum::ADMIN, $result->entity);
        self::assertSame('unknown_key', $result->identifier);
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

        $result = $this->orchestrator->setSetting($command);

        self::assertInstanceOf(SystemSettingCommandResultDTO::class, $result);
        self::assertSame(SystemSettingCommandResultEnum::SUCCESS, $result->result);
    }

    public function testSetSettingSuccess(): void
    {
        $key = new SettingKeyDTO('site_name');
        $value = new SettingValueDTO('New Name');
        $command = new SetSystemSettingCommandDTO($key, $value);

        $existing = new SystemSettingDTO('site_name', 'Old Name', new DateTimeImmutable());

        $this->reader->expects($this->once())
            ->method('get')
            ->with('site_name')
            ->willReturn($existing);

        $this->commandRepo->expects($this->once())
            ->method('set')
            ->with($command)
            ->willReturn(new SystemSettingCommandResultDTO(SystemSettingCommandResultEnum::SUCCESS));

        $result = $this->orchestrator->setSetting($command);

        self::assertSame(SystemSettingCommandResultEnum::SUCCESS, $result->result);
    }
}
