<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionInstallerBatchLookup
{
    private function __construct(public SelectionLookupStatus $status, public ?InstallerBatchPayload $payload) {}
    public static function found(InstallerBatchPayload $payload): self { return new self(SelectionLookupStatus::FOUND, $payload); }
    public static function notFound(): self { return new self(SelectionLookupStatus::NOT_FOUND, null); }
    public static function unavailable(): self { return new self(SelectionLookupStatus::UNAVAILABLE, null); }
}
