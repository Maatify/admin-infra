<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Mongo;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;
use Maatify\MongoActivity\ActivityLoggerInterface;
use Throwable;

final class MongoAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly ActivityLoggerInterface $logger,
        private readonly MongoAuditMapper $mapper
    ) {
    }

    public function logAuth(AuditAuthEventDTO $event): void
    {
        try {
            $this->logger->logAuth($this->mapper->mapAuth($event));
        } catch (Throwable) {
        }
    }

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
        try {
            $this->logger->logSecurity($this->mapper->mapSecurity($event));
        } catch (Throwable) {
        }
    }

    public function logAction(AuditActionDTO $event): void
    {
        try {
            $this->logger->logAction($this->mapper->mapAction($event));
        } catch (Throwable) {
        }
    }

    public function logView(AuditViewDTO $event): void
    {
        try {
            $this->logger->logView($this->mapper->mapView($event));
        } catch (Throwable) {
        }
    }
}
