<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Mongo;

use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextItemDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\MongoActivity\DTO\ActivityRecordDTO;
use Maatify\MongoActivity\Enum\ActionLogEnum;
use Maatify\MongoActivity\Enum\AppLogModulesEnum;
use Maatify\MongoActivity\Enum\UserLogRoleEnum;

class MongoAuditMapper
{
    public function mapAuth(AuditAuthEventDTO $dto): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            module: AppLogModulesEnum::ADMIN,
            action: ActionLogEnum::AUTH,
            role: UserLogRoleEnum::ADMIN,
            actorId: $dto->adminId,
            createdAt: $dto->occurredAt,
            payload: $this->buildPayload(
                $dto->context,
                $dto->metadata,
                ['event_type' => $dto->eventType]
            )
        );
    }

    public function mapSecurity(AuditSecurityEventDTO $dto): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            module: AppLogModulesEnum::ADMIN,
            action: ActionLogEnum::SECURITY,
            role: UserLogRoleEnum::ADMIN,
            actorId: $dto->adminId,
            createdAt: $dto->occurredAt,
            payload: $this->buildPayload(
                $dto->context,
                $dto->metadata,
                ['event_type' => $dto->eventType]
            )
        );
    }

    public function mapAction(AuditActionDTO $dto): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            module: AppLogModulesEnum::ADMIN,
            action: ActionLogEnum::ACTION,
            role: UserLogRoleEnum::ADMIN,
            actorId: $dto->actorAdminId,
            createdAt: $dto->occurredAt,
            payload: $this->buildPayload(
                $dto->context,
                $dto->metadata,
                [
                    'event_type' => $dto->eventType,
                    'target_type' => $dto->targetType,
                    'target_id' => $dto->targetId,
                ]
            )
        );
    }

    public function mapView(AuditViewDTO $dto): ActivityRecordDTO
    {
        return new ActivityRecordDTO(
            module: AppLogModulesEnum::ADMIN,
            action: ActionLogEnum::VIEW,
            role: UserLogRoleEnum::ADMIN,
            actorId: $dto->adminId,
            createdAt: $dto->occurredAt,
            payload: $this->buildPayload(
                $dto->context,
                null,
                ['view_name' => $dto->viewName]
            )
        );
    }

    /**
     * @param   AuditContextDTO     $context
     * @param   AuditMetadataDTO|null $metadata
     * @param   array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function buildPayload(AuditContextDTO $context, ?AuditMetadataDTO $metadata, array $extra = []): array
    {
        $payload = [];

        foreach ($context->items as $item) {
            $payload[$item->key] = $item->value;
        }

        if ($metadata !== null) {
            foreach ($metadata->items as $item) {
                $payload[$item->key] = $item->value;
            }
        }

        return array_merge($payload, $extra);
    }
}
