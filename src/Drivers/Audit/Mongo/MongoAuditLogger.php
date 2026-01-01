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
            if ($record === null) {
                trigger_error("MongoAudit: Skipped non-numeric ID in logAuth", E_USER_WARNING);
                return;
            }
            $this->activityManager->record($record);
        } catch (Throwable $e) {
            trigger_error("MongoAuditLogger::logAuth failed: " . $e->getMessage(), E_USER_WARNING);
        }
    }

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
        try {
            $record = $this->mapper->mapSecurity($event);
            if ($record === null) {
                trigger_error("MongoAudit: Skipped non-numeric ID in logSecurity", E_USER_WARNING);
                return;
            }
            $this->activityManager->record($record);
        } catch (Throwable $e) {
            trigger_error("MongoAuditLogger::logSecurity failed: " . $e->getMessage(), E_USER_WARNING);
        }
    }

    public function logAction(AuditActionDTO $event): void
    {
        try {
            $record = $this->mapper->mapAction($event);
            if ($record === null) {
                trigger_error("MongoAudit: Skipped non-numeric ID in logAction", E_USER_WARNING);
                return;
            }
            $this->activityManager->record($record);
        } catch (Throwable $e) {
            trigger_error("MongoAuditLogger::logAction failed: " . $e->getMessage(), E_USER_WARNING);
        }
    }

    public function logView(AuditViewDTO $event): void
    {
        try {
            $record = $this->mapper->mapView($event);
            if ($record === null) {
                trigger_error("MongoAudit: Skipped non-numeric ID in logView", E_USER_WARNING);
                return;
            }
            $this->activityManager->record($record);
        } catch (Throwable $e) {
            trigger_error("MongoAuditLogger::logView failed: " . $e->getMessage(), E_USER_WARNING);
        }
    }
}
