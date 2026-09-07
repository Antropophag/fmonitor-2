<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal One monotonic budget covering attempts, backoff and validation. */
final class DeliveryDeadline
{
    private readonly int $end;

    public function __construct(int $seconds)
    {
        $this->end = hrtime(true) + $seconds * 1_000_000_000;
    }

    public function check(): void
    {
        if (hrtime(true) >= $this->end) $this->fail();
    }

    public function milliseconds(): int
    {
        $remaining = intdiv(max(0, $this->end - hrtime(true)), 1_000_000);
        if ($remaining < 1) $this->fail();
        return $remaining;
    }

    public function backoff(int $retry): void
    {
        $delay = $retry * 1000 + random_int(0, 250);
        if ($delay >= $this->milliseconds()) $this->fail();
        usleep($delay * 1000);
        $this->check();
    }

    private function fail(): never
    {
        throw new DeliveryFailure(BitrixWorkforceDeliveryReason::DeadlineExceeded);
    }
}
