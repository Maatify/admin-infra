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

final class TotpSecretGenerator
{
    private const DEFAULT_SECRET_BYTES = 20;
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generate(int $numBytes = self::DEFAULT_SECRET_BYTES): string
    {
        $numBytes = $numBytes > 0 ? $numBytes : self::DEFAULT_SECRET_BYTES;

        $random = random_bytes($numBytes);

        return $this->base32Encode($random);
    }

    private function base32Encode(string $data): string
    {
        $encoded = '';
        $padding = '';
        $dataSize = strlen($data);
        $position = 0;

        while ($position < $dataSize) {
            $block = substr($data, $position, 5);
            $blockSize = strlen($block);
            $buffer = 0;
            for ($i = 0; $i < $blockSize; $i++) {
                $buffer = ($buffer << 8) | ord($block[$i]);
            }

            $bitCount = $blockSize * 8;
            $buffer <<= (5 - $blockSize) * 8;
            $outputCount = intdiv($bitCount, 5) + ($bitCount % 5 === 0 ? 0 : 1);

            for ($i = 0; $i < $outputCount; $i++) {
                $shift = 35 - $i * 5;
                $index = ($buffer >> ($shift < 0 ? 0 : $shift)) & 0x1F;
                $encoded .= self::BASE32_ALPHABET[$index];
            }

            if ($blockSize < 5) {
                $paddingLength = 8 - $outputCount;
                $padding .= str_repeat('=', $paddingLength);
            }

            $position += 5;
        }

        return $encoded . $padding;
    }
}
