<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final readonly class AssignmentOrderOriginalHistoryPage
{
    public function __construct(private array $metadata) {}
    public function metadata(): array { return $this->metadata; }
}
