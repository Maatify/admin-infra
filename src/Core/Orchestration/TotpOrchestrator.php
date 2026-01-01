<?php

/**
 * @copyright   ©2025 Maatify.dev
 * @Library     maatify/admin-infra
 * @Project     maatify:admin-infra
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-12-31 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-infra view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminInfra\Core\Orchestration;

use Maatify\AdminInfra\Contracts\Audit\AuditLoggerInterface;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditAuthEventDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditContextDTO;
use Maatify\AdminInfra\Contracts\Audit\DTO\AuditMetadataDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminIdDTO;
use Maatify\AdminInfra\Contracts\DTO\Admin\AdminStatusEnum;
use Maatify\AdminInfra\Contracts\DTO\Common\Result\NotFoundResultDTO;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminTotpCommandRepositoryInterface;
use Maatify\AdminInfra\Contracts\Repositories\Admin\AdminTotpQueryRepositoryInterface;
use Maatify\AdminInfra\Contracts\System\SystemSettingsReaderInterface;
use Maatify\AdminInfra\Core\Totp\TimeProviderInterface;
use Maatify\AdminInfra\Core\Totp\TotpSecretGenerator;
use Maatify\AdminInfra\Core\Totp\TotpVerifier;
use Maatify\AdminInfra\DTO\Totp\TotpCodeDTO;
use Maatify\AdminInfra\DTO\Totp\TotpDisableFailureEnum;
use Maatify\AdminInfra\DTO\Totp\TotpDisableResultDTO;
use Maatify\AdminInfra\DTO\Totp\TotpEnrollFailureEnum;
use Maatify\AdminInfra\DTO\Totp\TotpEnrollResultDTO;
use Maatify\AdminInfra\DTO\Totp\TotpStatusViewDTO;
use Maatify\AdminInfra\DTO\Totp\TotpVerifyFailureEnum;
use Maatify\AdminInfra\DTO\Totp\TotpVerifyResultDTO;

final class TotpOrchestrator
{
    private const FEATURE_FLAG_KEY = 'auth.totp.enabled';

    public function __construct(
        private readonly SystemSettingsReaderInterface $settingsReader,
        private readonly AdminQueryRepositoryInterface $adminQueryRepository,
        private readonly AdminTotpQueryRepositoryInterface $totpQueryRepository,
        private readonly AdminTotpCommandRepositoryInterface $totpCommandRepository,
        private readonly TotpSecretGenerator $secretGenerator,
        private readonly TotpVerifier $totpVerifier,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly TimeProviderInterface $timeProvider,
    ) {
    }

    public function enroll(AdminIdDTO $adminId): TotpEnrollResultDTO
    {
        if (!$this->isFeatureEnabled()) {
            return new TotpEnrollResultDTO(false, TotpEnrollFailureEnum::FEATURE_DISABLED, null);
        }

        $adminView = $this->adminQueryRepository->getById($adminId);
        if ($adminView instanceof NotFoundResultDTO) {
            return new TotpEnrollResultDTO(false, TotpEnrollFailureEnum::ADMIN_NOT_FOUND, null);
        }

        if ($adminView->status !== AdminStatusEnum::ACTIVE) {
            return new TotpEnrollResultDTO(false, TotpEnrollFailureEnum::ADMIN_NOT_ACTIVE, null);
        }

        $existing = $this->totpQueryRepository->getStatus($adminId);
        if ($existing instanceof TotpStatusViewDTO && $existing->isEnabled) {
            return new TotpEnrollResultDTO(false, TotpEnrollFailureEnum::ALREADY_ENABLED, null);
        }

        $secret = $this->secretGenerator->generate();
        $enrolledAt = $this->timeProvider->now();
        $this->totpCommandRepository->saveEnrollment($adminId, $secret, $enrolledAt);
        $this->totpCommandRepository->activate($adminId, $enrolledAt);

        $this->auditLogger->logAuth(new AuditAuthEventDTO(
            'auth.totp.enrolled',
            $adminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $enrolledAt
        ));

        return new TotpEnrollResultDTO(true, null, $secret);
    }

    public function verify(AdminIdDTO $adminId, TotpCodeDTO $code): TotpVerifyResultDTO
    {
        if (!$this->isFeatureEnabled()) {
            return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::FEATURE_DISABLED);
        }

        $adminView = $this->adminQueryRepository->getById($adminId);
        if ($adminView instanceof NotFoundResultDTO) {
            return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::ADMIN_NOT_FOUND);
        }

        if ($adminView->status !== AdminStatusEnum::ACTIVE) {
            return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::NOT_ENABLED);
        }

        $status = $this->totpQueryRepository->getStatus($adminId);
        if (!$status instanceof TotpStatusViewDTO || !$status->isEnabled) {
            return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::NOT_ENABLED);
        }

        $verification = $this->totpVerifier->verify($status->secret, $code->code);
        $occurredAt = $this->timeProvider->now();

        if (!$verification->isValid) {
            $this->auditLogger->logAuth(new AuditAuthEventDTO(
                'auth.totp.failed',
                $adminId,
                new AuditContextDTO([]),
                new AuditMetadataDTO([]),
                $occurredAt
            ));

            if ($verification->isExpired) {
                return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::CODE_EXPIRED);
            }

            return new TotpVerifyResultDTO(false, TotpVerifyFailureEnum::INVALID_CODE);
        }

        $this->totpCommandRepository->touch($adminId, $occurredAt);
        $this->auditLogger->logAuth(new AuditAuthEventDTO(
            'auth.totp.verified',
            $adminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $occurredAt
        ));

        return new TotpVerifyResultDTO(true, null);
    }

    public function disable(AdminIdDTO $adminId): TotpDisableResultDTO
    {
        if (!$this->isFeatureEnabled()) {
            return new TotpDisableResultDTO(false, TotpDisableFailureEnum::FEATURE_DISABLED);
        }

        $adminView = $this->adminQueryRepository->getById($adminId);
        if ($adminView instanceof NotFoundResultDTO || $adminView->status !== AdminStatusEnum::ACTIVE) {
            return new TotpDisableResultDTO(false, TotpDisableFailureEnum::NOT_ENABLED);
        }

        $status = $this->totpQueryRepository->getStatus($adminId);
        if (!$status instanceof TotpStatusViewDTO || !$status->isEnabled) {
            return new TotpDisableResultDTO(false, TotpDisableFailureEnum::NOT_ENABLED);
        }

        $disabledAt = $this->timeProvider->now();
        $this->totpCommandRepository->disable($adminId, $disabledAt);

        $this->auditLogger->logAuth(new AuditAuthEventDTO(
            'auth.totp.disabled',
            $adminId,
            new AuditContextDTO([]),
            new AuditMetadataDTO([]),
            $disabledAt
        ));

        return new TotpDisableResultDTO(true, null);
    }

    private function isFeatureEnabled(): bool
    {
        $flag = $this->settingsReader->getBool(self::FEATURE_FLAG_KEY);

        return $flag === null ? false : $flag;
    }
}
