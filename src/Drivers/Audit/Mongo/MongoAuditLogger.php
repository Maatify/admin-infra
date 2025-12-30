<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Mongo;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\MongoActivity\Manager\ActivityManager;
use Throwable;

final class MongoAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly ActivityManager $activityManager,
        private readonly MongoAuditMapper $mapper
    ) {
    }

    public function logAuth(AuditAuthEventDTO $event): void
    {
        try {
            $record = $this->mapper->mapAuth($event);
            $this->activityManager->log($record);
        } catch (Throwable) {
            // Swallow all throwables
        }
    }

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
        try {
            $record = $this->mapper->mapSecurity($event);
            $this->activityManager->log($record);
        } catch (Throwable) {
            // Swallow all throwables
        }
    }

    public function logAction(AuditActionDTO $event): void
    {
        try {
            $record = $this->mapper->mapAction($event);
            $this->activityManager->log($record);
        } catch (Throwable) {
            // Swallow all throwables
        }
    }

    public function logView(AuditViewDTO $event): void
    {
        try {
            $record = $this->mapper->mapView($event);
            $this->activityManager->log($record);
        } catch (Throwable) {
            // Swallow all throwables
        }
    }
}
