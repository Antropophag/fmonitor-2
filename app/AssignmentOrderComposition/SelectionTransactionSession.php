<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionTransactionSession
{
 public function lockedCase():SelectionCaseLookup;
 public function findTerminalRequest(SelectionRequestId $id):SelectionTerminalRequestLookup;
 public function selectionState():SelectionStateLookup;
 public function allocateIdentity(SelectionSourceKind $kind,SelectionInstant $at):SelectionIdentityAllocationResult;
 public function stageAccepted(SelectionAcceptedPersistence $payload):SelectionStageResult;
 public function stageTerminalAttempt(SelectionTerminalAttemptPersistence $payload):SelectionStageResult;
}
