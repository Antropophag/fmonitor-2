<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionCasePayload
{ public function __construct(public int $caseId,public int $objectId,
  public bool $completed,public ?string $ptoActDate) {} }
