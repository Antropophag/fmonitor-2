<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

final readonly class BitrixWorkforceDeliveryResult
{
    public BitrixWorkforceDeliveryStatus $status;

    public function __construct(
        public ?BitrixWorkforceDeliveryReason $reason,
        public int $pages,
        public int $attempts,
        public ?BitrixWorkforceDeliveryBatch $batch,
    ) {
        if (($reason === null) !== ($batch !== null) || $pages < 0 || $attempts < $pages) {
            throw new \InvalidArgumentException('Invalid delivery outcome.');
        }
        $this->status = $reason === null ? BitrixWorkforceDeliveryStatus::Complete : BitrixWorkforceDeliveryStatus::Failed;
    }
}
