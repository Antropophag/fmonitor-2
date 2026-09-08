<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionInstant
{ public function __construct(public string $utcRfc3339Seconds) {} }
