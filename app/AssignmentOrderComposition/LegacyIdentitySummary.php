<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class LegacyIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public SelectionLegacyPhysicalStatus $physicalStatus,public bool $hasAcceptedOriginal) {} }
