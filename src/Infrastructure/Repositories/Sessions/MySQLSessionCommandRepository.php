<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-29 03:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Infrastructure\Repositories\Sessions;

use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionCommandRepositoryInterface;
use PDO;
use PDOException;

final class MySQLSessionCommandRepository implements SessionCommandRepositoryInterface
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function create(CreateSessionCommandDTO $command): SessionCommandResultDTO
    {
        $sql = 'INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at)
                VALUES (:id, :admin_id, :created_at, :last_activity_at, NULL)';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new PDOException('Failed to prepare session insert statement.');
        }

        $stmt->bindValue(':id', $command->sessionId->id);
        $stmt->bindValue(':admin_id', $command->adminId->id);
        $stmt->bindValue(':created_at', $command->createdAt->format('Y-m-d H:i:s'));
        $stmt->bindValue(':last_activity_at', $command->createdAt->format('Y-m-d H:i:s'));

        $stmt->execute();

        return new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);
    }

    public function revoke(RevokeSessionCommandDTO $command): SessionCommandResultDTO
    {
        $sql = 'UPDATE admin_sessions
                SET revoked_at = :revoked_at
                WHERE id = :id AND revoked_at IS NULL';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new PDOException('Failed to prepare session revoke statement.');
        }

        $stmt->bindValue(':revoked_at', $command->revokedAt->format('Y-m-d H:i:s'));
        $stmt->bindValue(':id', $command->sessionId->id);

        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return new SessionCommandResultDTO(SessionCommandResultEnum::SESSION_NOT_FOUND);
        }

        return new SessionCommandResultDTO(SessionCommandResultEnum::SUCCESS);
    }
}
