<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Integration\Sessions;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\CreateSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Command\RevokeSessionCommandDTO;
use Maatify\AdminInfra\Contracts\DTO\Sessions\Result\SessionCommandResultEnum;
use Maatify\AdminInfra\Contracts\DTO\Sessions\SessionIdDTO;
use Maatify\AdminInfra\Infrastructure\Repositories\Sessions\MySQLSessionCommandRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class MySQLSessionCommandRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySQLSessionCommandRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createTable();

        $this->repository = new MySQLSessionCommandRepository($this->pdo);
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

    public function testCreateStoresSessionCorrectly(): void
    {
        $sessionId = new SessionIdDTO('session-123');
        $adminId = new AdminIdDTO('admin-456');
        $createdAt = new DateTimeImmutable('2025-01-01 12:00:00');

        $command = new CreateSessionCommandDTO($sessionId, $adminId, $createdAt);

        $result = $this->repository->create($command);

        $this->assertEquals(SessionCommandResultEnum::SUCCESS, $result->result);

        // Verify database state
        $stmt = $this->pdo->prepare('SELECT * FROM admin_sessions WHERE id = :id');
        $stmt->execute([':id' => 'session-123']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals('session-123', $row['id']);
        $this->assertEquals('admin-456', $row['admin_id']);
        $this->assertEquals('2025-01-01 12:00:00', $row['created_at']);
        $this->assertEquals('2025-01-01 12:00:00', $row['last_activity_at']);
        $this->assertNull($row['revoked_at']);
    }

    public function testRevokeUpdatesRevokedAtCorrectly(): void
    {
        // Setup initial state
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at)
            VALUES ('session-revoke', 'admin-789', '2025-01-01 10:00:00', '2025-01-01 10:00:00', NULL)
        ");

        $sessionId = new SessionIdDTO('session-revoke');
        $revokedAt = new DateTimeImmutable('2025-01-02 15:00:00');

        $command = new RevokeSessionCommandDTO($sessionId, $revokedAt);

        $result = $this->repository->revoke($command);

        $this->assertEquals(SessionCommandResultEnum::SUCCESS, $result->result);

        // Verify database state
        $stmt = $this->pdo->prepare('SELECT revoked_at FROM admin_sessions WHERE id = :id');
        $stmt->execute([':id' => 'session-revoke']);
        $revokedVal = $stmt->fetchColumn();

        $this->assertEquals('2025-01-02 15:00:00', $revokedVal);
    }

    public function testRevokeReturnsNotFoundIfSessionDoesNotExist(): void
    {
        $sessionId = new SessionIdDTO('non-existent-session');
        $revokedAt = new DateTimeImmutable('2025-01-02 15:00:00');

        $command = new RevokeSessionCommandDTO($sessionId, $revokedAt);

        $result = $this->repository->revoke($command);

        $this->assertEquals(SessionCommandResultEnum::SESSION_NOT_FOUND, $result->result);
    }

    public function testRevokeReturnsNotFoundIfAlreadyRevoked(): void
    {
        // Setup already revoked session
        $this->pdo->exec("
            INSERT INTO admin_sessions (id, admin_id, created_at, last_activity_at, revoked_at)
            VALUES ('session-already-revoked', 'admin-789', '2025-01-01 10:00:00', '2025-01-01 10:00:00', '2025-01-01 11:00:00')
        ");

        $sessionId = new SessionIdDTO('session-already-revoked');
        $revokedAt = new DateTimeImmutable('2025-01-02 15:00:00');

        $command = new RevokeSessionCommandDTO($sessionId, $revokedAt);

        $result = $this->repository->revoke($command);

        // Based on SQL: WHERE id = :id AND revoked_at IS NULL
        // If it's already revoked, rowCount will be 0.
        // Repository returns SESSION_NOT_FOUND in that case.
        $this->assertEquals(SessionCommandResultEnum::SESSION_NOT_FOUND, $result->result);
    }
}
