<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Integration\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\PaginationDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Value\EntityTypeEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\View\SessionViewDTO;
use Maatify\AdminInfra\Infrastructure\Repositories\Sessions\MySQLSessionQueryRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class MySQLSessionQueryRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySQLSessionQueryRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createTable();

        $this->repository = new MySQLSessionQueryRepository($this->pdo);
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function createTable(): void
    {
        $this->pdo->exec('
            CREATE TABLE admin_sessions (
                id VARCHAR(255) PRIMARY KEY,
                admin_id VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                last_activity_at DATETIME NOT NULL,
                revoked_at DATETIME
            )
        ');
    }

    public function testGetByIdReturnsSessionViewDTOWhenFound(): void
    {
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at)
            VALUES ('session-1', 'admin-1', '2025-01-01 10:00:00', '2025-01-01 12:00:00', NULL)
        ");

        $sessionId = new SessionIdDTO('session-1');
        $result = $this->repository->getById($sessionId);

        $this->assertInstanceOf(SessionViewDTO::class, $result);
        $this->assertEquals('session-1', $result->sessionId->id);
        $this->assertEquals('admin-1', $result->adminId->id);
        $this->assertEquals('2025-01-01 10:00:00', $result->createdAt->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-01 12:00:00', $result->lastActivityAt->format('Y-m-d H:i:s'));
        $this->assertFalse($result->isRevoked);
    }

    public function testGetByIdReturnsNotFoundResultDTOWhenMissing(): void
    {
        $sessionId = new SessionIdDTO('missing-session');
        $result = $this->repository->getById($sessionId);

        $this->assertInstanceOf(NotFoundResultDTO::class, $result);
        $this->assertEquals(EntityTypeEnum::SESSION, $result->entityType); // Assuming type matches
    }

    public function testGetByIdHandlesRevokedSessionCorrectly(): void
    {
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at)
            VALUES ('session-revoked', 'admin-1', '2025-01-01 10:00:00', '2025-01-01 12:00:00', '2025-01-01 13:00:00')
        ");

        $sessionId = new SessionIdDTO('session-revoked');
        $result = $this->repository->getById($sessionId);

        $this->assertInstanceOf(SessionViewDTO::class, $result);
        $this->assertTrue($result->isRevoked);
    }

    public function testListByAdminRespectsPaginationAndOrdering(): void
    {
        // Insert multiple sessions for admin-1 with different timestamps
        // Ordered by last_activity_at DESC
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at) VALUES
            ('s1', 'admin-1', '2025-01-01', '2025-01-01 10:00:00', NULL),
            ('s2', 'admin-1', '2025-01-01', '2025-01-01 12:00:00', NULL),
            ('s3', 'admin-1', '2025-01-01', '2025-01-01 11:00:00', NULL),
            ('s4', 'other-admin', '2025-01-01', '2025-01-01 13:00:00', NULL)
        ");

        $adminId = new AdminIdDTO('admin-1');
        // Page 1, Size 2. Should return s2 (12:00) and s3 (11:00) because 12 > 11 > 10.
        // Wait, s2 is 12:00, s3 is 11:00, s1 is 10:00.
        // DESC order: s2, s3, s1.

        $pagination = new PaginationDTO(1, 2);
        $result = $this->repository->listByAdmin($adminId, $pagination);

        $this->assertCount(2, $result->items->items);
        $this->assertEquals('s2', $result->items->items[0]->sessionId->id);
        $this->assertEquals('s3', $result->items->items[1]->sessionId->id);

        $this->assertEquals(1, $result->meta->currentPage);
        $this->assertEquals(2, $result->meta->pageSize);
        $this->assertEquals(3, $result->meta->totalItems); // s1, s2, s3
        $this->assertEquals(2, $result->meta->totalPages); // ceil(3/2) = 2
    }

    public function testListByAdminPaginationPage2(): void
    {
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at) VALUES
            ('s1', 'admin-1', '2025-01-01', '2025-01-01 10:00:00', NULL),
            ('s2', 'admin-1', '2025-01-01', '2025-01-01 12:00:00', NULL),
            ('s3', 'admin-1', '2025-01-01', '2025-01-01 11:00:00', NULL)
        ");

        $adminId = new AdminIdDTO('admin-1');
        $pagination = new PaginationDTO(2, 2);
        $result = $this->repository->listByAdmin($adminId, $pagination);

        $this->assertCount(1, $result->items->items);
        $this->assertEquals('s1', $result->items->items[0]->sessionId->id);

        $this->assertEquals(2, $result->meta->currentPage);
    }
}
