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
        $dsn = getenv('TEST_MYSQL_DSN') ?: 'mysql:host=127.0.0.1;dbname=maatify_test_db';
        $user = getenv('TEST_MYSQL_USER') ?: 'root';
        $password = getenv('TEST_MYSQL_PASSWORD') ?: '';

        $this->pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->setupSchema();

        $this->repository = new MySQLSessionCommandRepository($this->pdo);
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function setupSchema(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS admin_sessions');

        $schemaPath = __DIR__ . '/../../../docs/infrastructure/database/mysql/admin_sessions.sql';
        $sql = file_get_contents($schemaPath);

        if ($sql === false) {
            throw new \RuntimeException('Could not read schema file: ' . $schemaPath);
        }

        $this->pdo->exec($sql);
    }

    public function testCreateSessionPersistsRecord(): void
    {
        $sessionId = new SessionIdDTO('session-123');
        $adminId = new AdminIdDTO('admin-456');
        $createdAt = new DateTimeImmutable('2025-01-01 12:00:00');

        $command = new CreateSessionCommandDTO(
            $sessionId,
            $adminId,
            $createdAt,
            'req-integration',
            '127.0.0.1',
            'IntegrationAgent'
        );

        $result = $this->repository->create($command);

        $this->assertEquals(SessionCommandResultEnum::SUCCESS, $result->result);

        // Verify database state
        $stmt = $this->pdo->prepare('SELECT * FROM admin_sessions WHERE id = :id');
        $stmt->execute([':id' => 'session-123']);
        $row = $stmt->fetch();

        $this->assertIsArray($row);
        $this->assertEquals('session-123', $row['id']);
        $this->assertEquals('admin-456', $row['admin_id']);
        $this->assertEquals('2025-01-01 12:00:00', $row['created_at']);
        $this->assertEquals('2025-01-01 12:00:00', $row['last_activity_at']);
        $this->assertNull($row['revoked_at']);
    }

    public function testRevokeSessionMarksRevokedAt(): void
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

    public function testRevokeNonExistingSessionReturnsSessionNotFound(): void
    {
        $sessionId = new SessionIdDTO('non-existent-session');
        $revokedAt = new DateTimeImmutable('2025-01-02 15:00:00');

        $command = new RevokeSessionCommandDTO($sessionId, $revokedAt);

        $result = $this->repository->revoke($command);

        $this->assertEquals(SessionCommandResultEnum::SESSION_NOT_FOUND, $result->result);
    }

    public function testRevokeAlreadyRevokedReturnsSessionNotFound(): void
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

        $this->assertEquals(SessionCommandResultEnum::SESSION_NOT_FOUND, $result->result);
    }
}
