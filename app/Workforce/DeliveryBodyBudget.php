<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Counts decoded bytes, including rejected/retried response bodies. */
final class DeliveryBodyBudget
{
    private int $received = 0;
    public bool $exceeded = false;

    public function accept(int $attemptBytes, int $chunkBytes): bool
    {
        $this->received += $chunkBytes;
        $this->exceeded = $attemptBytes + $chunkBytes > 1_048_576 || $this->received > 16_777_216;
        return !$this->exceeded;
    }
}
