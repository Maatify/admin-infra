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

namespace Maatify\AdminInfra\Core\Totp;

final class TotpVerifier
{
    public function __construct(
        private readonly TotpCodeGenerator $codeGenerator,
        private readonly TotpWindowPolicy $windowPolicy,
        private readonly TimeProviderInterface $timeProvider,
    ) {
    }

    public function verify(string $secret, string $code): TotpVerificationResult
    {
        $currentCounter = intdiv($this->timeProvider->now()->getTimestamp(), TotpCodeGenerator::TIME_STEP_SECONDS);

        for ($offset = -$this->windowPolicy->pastWindows; $offset <= $this->windowPolicy->futureWindows; $offset++) {
            $counter = $currentCounter + $offset;
            if ($counter < 0) {
                continue;
            }

            if ($this->codeGenerator->generateForCounter($secret, $counter) === $code) {
                return new TotpVerificationResult(true, false);
            }
        }

        $expiredCounter = $currentCounter - ($this->windowPolicy->pastWindows + 1);
        if ($expiredCounter >= 0 && $this->codeGenerator->generateForCounter($secret, $expiredCounter) === $code) {
            return new TotpVerificationResult(false, true);
        }

        return new TotpVerificationResult(false, false);
    }
}
