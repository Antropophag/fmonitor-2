<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

final readonly class BitrixWorkforceDeliveryConfig
{
    public function __construct(
        public string $origin,
        public int $webhookUserId,
        public string $tokenFile,
        public array $departmentIds,
        public int $connectTimeoutSeconds = 3,
        public int $requestTimeoutSeconds = 10,
        public int $deadlineSeconds = 120,
        public ?string $caFile = null,
    ) {}
}
