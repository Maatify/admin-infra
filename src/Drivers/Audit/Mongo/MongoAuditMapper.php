<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Mongo;

use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\AdminInfra\Drivers\Audit\Mongo\Enum\AdminInfraAppModuleEnum;
use Maatify\MongoActivity\DTO\ActivityRecordDTO;
use Maatify\MongoActivity\Enum\ActivityLogTypeEnum;
use Maatify\MongoActivity\Enum\UserLogRoleEnum;

final class MongoAuditMapper
{
    public function mapAuth(AuditAuthEventDTO $event): ?ActivityRecordDTO
    {
        if (!$this->isNumericId($event->adminId->id)) {
            return null;
        }

        return new ActivityRecordDTO(
            userId: (int) $event->adminId->id,
            role: UserLogRoleEnum::ADMIN,
            type: ActivityLogTypeEnum::SYSTEM,
            module: AdminInfraAppModuleEnum::ADMIN,
            action: $event->eventType,
            description: $this->buildDescription($event->context),
            refId: null,
            ip: null,
            userAgent: null,
        );
    }

    public function mapSecurity(AuditSecurityEventDTO $event): ?ActivityRecordDTO
    {
        if (!$this->isNumericId($event->adminId->id)) {
            return null;
        }

        return new ActivityRecordDTO(
            userId: (int) $event->adminId->id,
            role: UserLogRoleEnum::ADMIN,
            type: ActivityLogTypeEnum::SYSTEM,
            module: AdminInfraAppModuleEnum::ADMIN,
            action: $event->eventType,
            description: $this->buildDescription($event->context),
            refId: null,
            ip: null,
            userAgent: null,
        );
    }

    public function mapAction(AuditActionDTO $event): ?ActivityRecordDTO
    {
        if (!$this->isNumericId($event->actorAdminId->id)) {
            return null;
        }

        if ($event->targetId !== null && !$this->isNumericId($event->targetId->id)) {
            return null;
        }

        return new ActivityRecordDTO(
            userId: (int) $event->actorAdminId->id,
            role: UserLogRoleEnum::ADMIN,
            type: ActivityLogTypeEnum::UPDATE,
            module: AdminInfraAppModuleEnum::ADMIN,
            action: $event->eventType,
            description: $this->buildDescription($event->context),
            refId: $event->targetId ? (int) $event->targetId->id : null,
            ip: null,
            userAgent: null,
        );
    }

    public function mapView(AuditViewDTO $event): ?ActivityRecordDTO
    {
        if (!$this->isNumericId($event->adminId->id)) {
            return null;
        }

        return new ActivityRecordDTO(
            userId: (int) $event->adminId->id,
            role: UserLogRoleEnum::ADMIN,
            type: ActivityLogTypeEnum::VIEW,
            module: AdminInfraAppModuleEnum::ADMIN,
            action: $event->viewName,
            description: $this->buildDescription($event->context),
            refId: null,
            ip: null,
            userAgent: null,
        );
    }

    private function buildDescription(AuditContextDTO $context): string
    {
        $desc = [];
        foreach ($context->items as $item) {
            $desc[] = $item->key . ': ' . $item->value;
        }
        return implode(', ', $desc);
    }

    private function isNumericId(string $id): bool
    {
        return is_numeric($id) && (string)(int)$id === $id;
    }
}
