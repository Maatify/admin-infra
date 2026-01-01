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

use DateTimeImmutable;

final class TotpCodeGenerator
{
    public const TIME_STEP_SECONDS = 30;
    private const CODE_LENGTH = 6;

    public function __construct(
        private readonly TimeProviderInterface $timeProvider
    ) {
    }

    public function generate(string $secret, ?DateTimeImmutable $time = null): string
    {
        $time ??= $this->timeProvider->now();

        $counter = intdiv($time->getTimestamp(), self::TIME_STEP_SECONDS);

        return $this->generateForCounter($secret, $counter);
    }

    public function generateForCounter(string $secret, int $counter): string
    {
        if ($counter < 0) {
            throw new \InvalidArgumentException('Counter cannot be negative.');
        }

        $normalizedCounter = $counter;

        $secretBinary = $this->base32Decode($secret);
        $counterBytes = pack('N*', 0, $normalizedCounter);
        $hash = hash_hmac('sha1', $counterBytes, $secretBinary, true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % 10 ** self::CODE_LENGTH;

        return str_pad((string)$otp, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $normalized = rtrim(strtoupper($secret), '=');
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $alphabetMap = array_flip(str_split($alphabet));
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($normalized) as $character) {
            if (!isset($alphabetMap[$character])) {
                continue;
            }

            $buffer = ($buffer << 5) | $alphabetMap[$character];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $byte = ($buffer >> $bitsLeft) & 0xFF;
                $result .= chr($byte);
            }
        }

        return $result;
    }
}
