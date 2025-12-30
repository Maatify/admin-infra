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
use Maatify\MongoActivity\DTO\AuditActionDTO as MongoAuditActionDTO;
use Maatify\MongoActivity\DTO\AuditAuthEventDTO as MongoAuditAuthEventDTO;
use Maatify\MongoActivity\DTO\AuditContextDTO as MongoAuditContextDTO;
use Maatify\MongoActivity\DTO\AuditContextItemDTO as MongoAuditContextItemDTO;
use Maatify\MongoActivity\DTO\AuditMetadataDTO as MongoAuditMetadataDTO;
use Maatify\MongoActivity\DTO\AuditSecurityEventDTO as MongoAuditSecurityEventDTO;
use Maatify\MongoActivity\DTO\AuditViewDTO as MongoAuditViewDTO;

final class MongoAuditMapper
{
    public function mapAuth(AuditAuthEventDTO $event): MongoAuditAuthEventDTO
    {
        return new MongoAuditAuthEventDTO(
            $event->eventType,
            $event->adminId,
            $this->mapContext($event->context),
            $this->mapMetadata($event->metadata),
            $event->occurredAt,
        );
    }

    public function mapSecurity(AuditSecurityEventDTO $event): MongoAuditSecurityEventDTO
    {
        return new MongoAuditSecurityEventDTO(
            $event->eventType,
            $event->adminId,
            $this->mapContext($event->context),
            $this->mapMetadata($event->metadata),
            $event->occurredAt,
        );
    }

    public function mapAction(AuditActionDTO $event): MongoAuditActionDTO
    {
        return new MongoAuditActionDTO(
            $event->eventType,
            $event->actorAdminId,
            $event->targetType,
            $event->targetId,
            $this->mapContext($event->context),
            $this->mapMetadata($event->metadata),
            $event->occurredAt,
        );
    }

    public function mapView(AuditViewDTO $event): MongoAuditViewDTO
    {
        return new MongoAuditViewDTO(
            $event->viewName,
            $event->adminId,
            $this->mapContext($event->context),
            $event->occurredAt,
        );
    }

    private function mapContext(AuditContextDTO $context): MongoAuditContextDTO
    {
        $items = array_map(
            fn (AuditContextItemDTO $item) => new MongoAuditContextItemDTO($item->key, $item->value),
            $context->items,
        );

        return new MongoAuditContextDTO($items);
    }

    private function mapMetadata(AuditMetadataDTO $metadata): MongoAuditMetadataDTO
    {
        $items = array_map(
            fn (AuditContextItemDTO $item) => new MongoAuditContextItemDTO($item->key, $item->value),
            $metadata->items,
        );

        return new MongoAuditMetadataDTO($items);
    }
}
