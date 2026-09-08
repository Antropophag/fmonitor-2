<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionStateSnapshot
{ public function __construct(public ?SelectionIdentitySummary $latestSelection,
  public ?SelectionIdentitySummary $latestPendingSelection,
  public ?SelectionIdentitySummary $latestAcceptedSelection,
  public ?LegacyIdentitySummary $latestRegistryLegacyIdentity,
  public ?EffectiveOrderIdentity $effectiveOrder) {} }
