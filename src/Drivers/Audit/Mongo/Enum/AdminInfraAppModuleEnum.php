<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Drivers\Audit\Mongo\Enum;

use Maatify\MongoActivity\Contract\AppLogModuleInterface;

enum AdminInfraAppModuleEnum: string implements AppLogModuleInterface
{
    case ADMIN = 'admin';

    public static function list(): array
    {
        return array_column(self::cases(), 'value');
    }
}
