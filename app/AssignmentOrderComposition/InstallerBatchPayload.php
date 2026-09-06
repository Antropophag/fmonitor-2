<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class InstallerBatchPayload
{ /** @param list<InstallerSnapshot> $snapshots @param list<int> $missingIds */
  public function __construct(public array $snapshots,public array $missingIds) {} }
