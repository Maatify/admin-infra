<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Null;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditActionDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditSecurityEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditViewDTO;

class NullAuditLogger implements AuditLoggerInterface
{
    public function logAuth(AuditAuthEventDTO $event): void
    {
    }

    public function logSecurity(AuditSecurityEventDTO $event): void
    {
    }

    public function logAction(AuditActionDTO $event): void
    {
    }

    public function logView(AuditViewDTO $event): void
    {
    }
}
