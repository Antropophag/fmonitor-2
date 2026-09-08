<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionTerminalRequestRecord
{ public function __construct(public SelectionRequestId $requestId,
  public SelectionNormalizedIntent $intent,
  public AssignmentOrderCompositionResult $terminalResult) {} }
