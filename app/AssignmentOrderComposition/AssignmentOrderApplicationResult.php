<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class AssignmentOrderApplicationResult
{
    public function __construct(public string $status, public ?string $reason, public ?array $application) {}
}
