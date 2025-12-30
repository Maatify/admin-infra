<?php

declare(strict_types=1);

namespace Maatify\AdminInfra\Tests\Support;

use DateTimeImmutable;
use Maatify\AdminInfra\Core\Totp\TimeProviderInterface;

final class FakeTimeProvider implements TimeProviderInterface
{
    private DateTimeImmutable $now;

    public function __construct(?DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new DateTimeImmutable();
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function setNow(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function advanceSeconds(int $seconds): void
    {
        $this->now = $this->now->modify("+{$seconds} seconds");
    }
}
