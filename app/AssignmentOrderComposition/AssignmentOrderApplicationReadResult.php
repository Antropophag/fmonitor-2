<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class AssignmentOrderApplicationReadResult
{
    public function __construct(public string $status, public ?array $value = null) {}
}
