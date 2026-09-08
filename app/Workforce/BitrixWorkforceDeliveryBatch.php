<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

final readonly class BitrixWorkforceDeliveryBatch
{
    public function __construct(public int $total, private array $selectedRecords) {}

    public function records(): array
    {
        return $this->selectedRecords;
    }
}
