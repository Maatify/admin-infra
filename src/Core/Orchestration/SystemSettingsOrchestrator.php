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
 * Core Orchestration skeleton for system settings flows.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\System\Command\SetSystemSettingCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\System\Result\SystemSettingCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\System\Result\SystemSettingCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\System\SettingKeyDTO;
use Maatify\AdminInfra\Contracts\DTO\System\SettingValueDTO;
use Maatify\AdminInfra\Contracts\DTO\System\View\SystemSettingViewDTO;
use Maatify\AdminInfra\Contracts\Repositories\System\SystemSettingsCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\System\SystemSettingsReaderInterface;

/**
 * Coordinates system setting retrieval and mutation sequencing across command and query
 * repositories while documenting feature enforcement boundaries.
 *
 * Sequences and coordinates the following contracts without defining wiring,
 * instantiation, or lifecycle management in this phase:
 * - SystemSettingsQueryRepositoryInterface
 * - SystemSettingsCommandRepositoryInterface
 * - SystemSettingsReaderInterface
 * - AuditLoggerInterface
 * - NotificationDispatcherInterface
 *
 * Non-responsibilities:
 * - Does not persist configuration values directly.
 * - Does not evaluate feature flags or transport concerns.
 * - Does not handle audit/notification delivery guarantees.
 */
final class SystemSettingsOrchestrator
{
    public function __construct(
        private readonly SystemSettingsReaderInterface $reader,
        private readonly SystemSettingsCommandRepositoryInterface $commandRepo
    ) {
    }

    /**
     * Coordinates retrieval of a system setting while preserving not-found semantics and
     * feature enforcement ordering.
     */
    public function getSetting(SettingKeyDTO $key): SystemSettingViewDTO|NotFoundResultDTO
    {
        $dto = $this->reader->get($key->key);

        if ($dto === null) {
            return new NotFoundResultDTO(EntityTypeEnum::SYSTEM_SETTING, $key->key);
        }

        return new SystemSettingViewDTO(
            new SettingKeyDTO($dto->key),
            new SettingValueDTO($dto->value)
        );
    }

    /**
     * Sequences system setting mutation while enforcing orchestration boundaries and
     * documenting intended side effects.
     *
     * @throws \DomainException When contract preconditions or invariants are violated.
     */
    public function setSetting(SetSystemSettingCommandDTO $command): SystemSettingCommandResultDTO
    {
        $existing = $this->reader->get($command->key->key);
        $oldValue = $existing?->value;

        if ($oldValue === $command->value->value) {
            return new SystemSettingCommandResultDTO(SystemSettingCommandResultEnum::SUCCESS);
        }

        return $this->commandRepo->set($command);
    }
}
