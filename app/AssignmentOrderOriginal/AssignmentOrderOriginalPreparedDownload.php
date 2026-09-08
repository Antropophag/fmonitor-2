<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
/** Fully verified immutable value; no retained descriptor, lease or transaction. */
final readonly class AssignmentOrderOriginalPreparedDownload
{
    public function __construct(private array $metadata, private string $bytes) {}
    public function metadata(): array { return $this->metadata; }
    public function bytes(): string { return $this->bytes; }
}
