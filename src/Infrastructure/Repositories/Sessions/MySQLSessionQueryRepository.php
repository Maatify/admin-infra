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

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PageMetaDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListItemCollectionDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionListItemDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Contracts\Repositories\Sessions\SessionQueryRepositoryInterface;
use PDO;
use PDOException;

final class MySQLSessionQueryRepository implements SessionQueryRepositoryInterface
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function getById(SessionIdDTO $sessionId): SessionViewDTO|NotFoundResultDTO
    {
        $sql = 'SELECT id, admin_id, created_at, last_activity_at, revoked_at
                FROM admin_sessions
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new PDOException('Failed to prepare session select statement.');
        }

        $stmt->bindValue(':id', $sessionId->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return new NotFoundResultDTO(EntityTypeEnum::SESSION, $sessionId->id);
        }

        /** @var array{id: string, admin_id: string, created_at: string, last_activity_at: string, revoked_at: string|null} $row */

        return $this->hydrateSessionView($row);
    }

    public function listByAdmin(AdminIdDTO $adminId, PaginationDTO $pagination): SessionListDTO
    {
        $countSql = 'SELECT COUNT(*) as total FROM admin_sessions WHERE admin_id = :admin_id';
        $countStmt = $this->connection->prepare($countSql);

        if ($countStmt === false) {
            throw new PDOException('Failed to prepare session count statement.');
        }

        $countStmt->bindValue(':admin_id', $adminId->id);
        $countStmt->execute();

        $total = (int) $countStmt->fetchColumn();

        $offset = ($pagination->page - 1) * $pagination->pageSize;
        $limit = $pagination->pageSize;

        $listSql = 'SELECT id, last_activity_at, revoked_at
                    FROM admin_sessions
                    WHERE admin_id = :admin_id
                    ORDER BY last_activity_at DESC
                    LIMIT :limit OFFSET :offset';

        $listStmt = $this->connection->prepare($listSql);

        if ($listStmt === false) {
            throw new PDOException('Failed to prepare session list statement.');
        }

        $listStmt->bindValue(':admin_id', $adminId->id);
        $listStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $listStmt->execute();

        $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        /** @var array<int, array{id: string, last_activity_at: string, revoked_at: string|null}> $rows */

        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->hydrateSessionListItem($row);
        }

        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 0;

        return new SessionListDTO(
            new SessionListItemCollectionDTO($items),
            new PageMetaDTO($pagination->page, $pagination->pageSize, $total, $totalPages)
        );
    }

    /**
     * @param array{id: string, admin_id: string, created_at: string, last_activity_at: string, revoked_at: string|null} $row
     */
    private function hydrateSessionView(array $row): SessionViewDTO
    {
        return new SessionViewDTO(
            new SessionIdDTO($row['id']),
            new AdminIdDTO($row['admin_id']),
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['last_activity_at']),
            $row['revoked_at'] !== null
        );
    }

    /**
     * @param array{id: string, last_activity_at: string, revoked_at: string|null} $row
     */
    private function hydrateSessionListItem(array $row): SessionListItemDTO
    {
        return new SessionListItemDTO(
            new SessionIdDTO($row['id']),
            new DateTimeImmutable($row['last_activity_at']),
            $row['revoked_at'] !== null
        );
    }
}
