<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public int $selectionRevision,public string $compositionIdentity,
  public string $compositionSha256,public bool $hasAcceptedOriginal) {} }
