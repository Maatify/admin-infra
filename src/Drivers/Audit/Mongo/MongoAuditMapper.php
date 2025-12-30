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
    public function mapAuth(AuditAuthEventDTO $event): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            userId: $event->adminId,
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

    public function mapSecurity(AuditSecurityEventDTO $event): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            userId: $event->adminId,
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

    public function mapAction(AuditActionDTO $event): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            userId: $event->actorAdminId,
            role: UserLogRoleEnum::ADMIN,
            type: ActivityLogTypeEnum::UPDATE,
            module: AdminInfraAppModuleEnum::ADMIN,
            action: $event->eventType,
            description: $this->buildDescription($event->context),
            refId: $event->targetId,
            ip: null,
            userAgent: null,
        );
    }

    public function mapView(AuditViewDTO $event): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            userId: $event->adminId,
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
}
