<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class BitrixOrderDocumentDeliveryConfig
{
    public function __construct(
        public string $origin, public int $webhookUserId, public int $rootFolderId,
        public string $loginPath, public string $token, public ?string $caFile,
        public int $pageSize, public int $maxItems, public int $maxBytes, public int $timeoutSeconds,
    ) {}
}
