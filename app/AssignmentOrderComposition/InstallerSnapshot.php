<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class InstallerSnapshot
{ public function __construct(public int $tabId,public string $fio,
  public string $position,public string $employmentStatus,
  public string $employedFrom,public ?string $employedTo,
  public string $workforceSource,public string $workforceSourceUpdatedAt) {} }
