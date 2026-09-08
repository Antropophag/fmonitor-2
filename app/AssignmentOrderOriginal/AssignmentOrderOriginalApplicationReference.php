<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Immutable metadata only. Issuance and seals remain private to the owning reader. */
final readonly class AssignmentOrderOriginalApplicationReference
{
    /** @internal A constructed copy is not an issued reference and cannot confirm. */
    public function __construct(private array $metadata) {}
    public function metadata(): array { return $this->metadata; }
}
