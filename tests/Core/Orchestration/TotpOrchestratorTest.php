<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Core\Orchestration;

use DateTimeImmutable;
use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusEnum;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminViewDTO;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminTotpCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminTotpQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\System\SystemSettingsReaderInterface;
use Maatify\AdminInfra\Core\Orchestration\TotpOrchestrator;
use Maatify\AdminInfra\Core\Totp\TotpCodeGenerator;
use Maatify\AdminInfra\Core\Totp\TotpSecretGenerator;
use Maatify\AdminInfra\Core\Totp\TotpVerifier;
use Maatify\AdminInfra\Core\Totp\TotpWindowPolicy;
use Maatify\AdminInfra\DTO\Totp\TotpCodeDTO;
use Maatify\AdminInfra\DTO\Totp\TotpDisableFailureEnum;
use Maatify\AdminInfra\DTO\Totp\TotpEnrollFailureEnum;
use Maatify\AdminInfra\DTO\Totp\TotpStatusViewDTO;
use Maatify\AdminInfra\DTO\Totp\TotpVerifyFailureEnum;
use Maatify\AdminInfra\Tests\Support\FakeTimeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TotpOrchestratorTest extends TestCase
{
    private MockObject&SystemSettingsReaderInterface $settingsReader;
    private MockObject&AdminQueryRepositoryInterface $adminQueryRepository;
    private MockObject&AdminTotpQueryRepositoryInterface $totpQueryRepository;
    private MockObject&AdminTotpCommandRepositoryInterface $totpCommandRepository;
    private MockObject&AuditLoggerInterface $auditLogger;

    private FakeTimeProvider $timeProvider;
    private TotpOrchestrator $orchestrator;

    // Real instances for finals
    private TotpSecretGenerator $secretGenerator;
    private TotpVerifier $totpVerifier;
    private TotpCodeGenerator $codeGenerator;

    protected function setUp(): void
    {
        $this->settingsReader = $this->createMock(SystemSettingsReaderInterface::class);
        $this->adminQueryRepository = $this->createMock(AdminQueryRepositoryInterface::class);
        $this->totpQueryRepository = $this->createMock(AdminTotpQueryRepositoryInterface::class);
        $this->totpCommandRepository = $this->createMock(AdminTotpCommandRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->timeProvider = new FakeTimeProvider(new DateTimeImmutable('@1600000000'));
        $this->secretGenerator = new TotpSecretGenerator();
        $this->codeGenerator = new TotpCodeGenerator($this->timeProvider);
        $policy = new TotpWindowPolicy(1, 1);
        $this->totpVerifier = new TotpVerifier($this->codeGenerator, $policy, $this->timeProvider);

        $this->orchestrator = new TotpOrchestrator(
            $this->settingsReader,
            $this->adminQueryRepository,
            $this->totpQueryRepository,
            $this->totpCommandRepository,
            $this->secretGenerator,
            $this->totpVerifier,
            $this->auditLogger,
            $this->timeProvider
        );
    }

    private function setFeatureEnabled(bool $enabled): void
    {
        $this->settingsReader->method('getBool')->with('auth.totp.enabled')->willReturn($enabled);
    }

    private function mockAdmin(int $id, AdminStatusEnum $status = AdminStatusEnum::ACTIVE): void
    {
        $adminView = new AdminViewDTO($id, 'test@example.com', $status, new DateTimeImmutable());
        $this->adminQueryRepository->method('getById')->with($this->callback(fn($dto) => $dto->id === $id))->willReturn($adminView);
    }

    private function mockAdminNotFound(int $id): void
    {
        $this->adminQueryRepository->method('getById')->with($this->callback(fn($dto) => $dto->id === $id))->willReturn(new NotFoundResultDTO());
    }

    private function mockTotpStatus(int $id, bool $enabled, string $secret = 'SECRET'): void
    {
        $status = new TotpStatusViewDTO($id, $enabled, $enabled ? new DateTimeImmutable() : null, $secret);
        $this->totpQueryRepository->method('getStatus')->with($this->callback(fn($dto) => $dto->id === $id))->willReturn($status);
    }

    private function mockTotpStatusNotFound(int $id): void
    {
         $this->totpQueryRepository->method('getStatus')->with($this->callback(fn($dto) => $dto->id === $id))->willReturn(new NotFoundResultDTO());
    }

    // --- Enroll Tests ---

    public function testEnrollFailsIfFeatureDisabled(): void
    {
        $this->setFeatureEnabled(false);
        $result = $this->orchestrator->enroll(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpEnrollFailureEnum::FEATURE_DISABLED, $result->failure);
    }

    public function testEnrollFailsIfAdminNotFound(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdminNotFound(1);

        $result = $this->orchestrator->enroll(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpEnrollFailureEnum::ADMIN_NOT_FOUND, $result->failure);
    }

    public function testEnrollFailsIfAdminNotActive(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1, AdminStatusEnum::SUSPENDED);

        $result = $this->orchestrator->enroll(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpEnrollFailureEnum::ADMIN_NOT_ACTIVE, $result->failure);
    }

    public function testEnrollFailsIfAlreadyEnabled(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $this->mockTotpStatus(1, true);

        $result = $this->orchestrator->enroll(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpEnrollFailureEnum::ALREADY_ENABLED, $result->failure);
    }

    public function testEnrollSuccess(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $this->mockTotpStatus(1, false);

        $this->totpCommandRepository->expects($this->once())->method('saveEnrollment');
        $this->totpCommandRepository->expects($this->once())->method('activate');
        $this->auditLogger->expects($this->once())->method('logAuth');

        $result = $this->orchestrator->enroll(new AdminIdDTO(1));

        $this->assertTrue($result->success);
        $this->assertNotNull($result->secret);
    }

    // --- Verify Tests ---

    public function testVerifyFailsIfFeatureDisabled(): void
    {
        $this->setFeatureEnabled(false);
        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO('123456'));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::FEATURE_DISABLED, $result->failure);
    }

    public function testVerifyFailsIfAdminNotFound(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdminNotFound(1);

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO('123456'));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::ADMIN_NOT_FOUND, $result->failure);
    }

    public function testVerifyFailsIfAdminNotActive(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1, AdminStatusEnum::SUSPENDED);

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO('123456'));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::NOT_ENABLED, $result->failure);
    }

    public function testVerifyFailsIfNotEnabled(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $this->mockTotpStatus(1, false);

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO('123456'));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::NOT_ENABLED, $result->failure);
    }

    public function testVerifyFailsIfCodeInvalid(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        // Valid secret but we send wrong code
        $this->mockTotpStatus(1, true, 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');

        $this->auditLogger->expects($this->once())->method('logAuth')->with($this->callback(fn($dto) => $dto->event === 'auth.totp.failed'));

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO('000000'));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::INVALID_CODE, $result->failure);
    }

    public function testVerifyFailsIfCodeExpired(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->mockTotpStatus(1, true, $secret);

        // Expired (2 windows back = 60s ago)
        $expiredTime = $this->timeProvider->now()->modify('-60 seconds');
        $expiredCode = $this->codeGenerator->generate($secret, $expiredTime);

        $this->auditLogger->expects($this->once())->method('logAuth')->with($this->callback(fn($dto) => $dto->event === 'auth.totp.failed'));

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO($expiredCode));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpVerifyFailureEnum::CODE_EXPIRED, $result->failure);
    }

    public function testVerifySuccess(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->mockTotpStatus(1, true, $secret);

        $validCode = $this->codeGenerator->generate($secret, $this->timeProvider->now());

        $this->totpCommandRepository->expects($this->once())->method('touch');
        $this->auditLogger->expects($this->once())->method('logAuth')->with($this->callback(fn($dto) => $dto->event === 'auth.totp.verified'));

        $result = $this->orchestrator->verify(new AdminIdDTO(1), new TotpCodeDTO($validCode));

        $this->assertTrue($result->success);
    }

    // --- Disable Tests ---

    public function testDisableFailsIfFeatureDisabled(): void
    {
        $this->setFeatureEnabled(false);
        $result = $this->orchestrator->disable(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpDisableFailureEnum::FEATURE_DISABLED, $result->failure);
    }

    public function testDisableFailsIfAdminNotFound(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdminNotFound(1);

        $result = $this->orchestrator->disable(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpDisableFailureEnum::NOT_ENABLED, $result->failure);
    }

    public function testDisableFailsIfNotEnabled(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $this->mockTotpStatus(1, false);

        $result = $this->orchestrator->disable(new AdminIdDTO(1));

        $this->assertFalse($result->success);
        $this->assertEquals(TotpDisableFailureEnum::NOT_ENABLED, $result->failure);
    }

    public function testDisableSuccess(): void
    {
        $this->setFeatureEnabled(true);
        $this->mockAdmin(1);
        $this->mockTotpStatus(1, true);

        $this->totpCommandRepository->expects($this->once())->method('disable');
        $this->auditLogger->expects($this->once())->method('logAuth')->with($this->callback(fn($dto) => $dto->event === 'auth.totp.disabled'));

        $result = $this->orchestrator->disable(new AdminIdDTO(1));

        $this->assertTrue($result->success);
    }
}
