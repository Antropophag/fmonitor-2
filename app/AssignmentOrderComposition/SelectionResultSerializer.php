<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class SelectionResultSerializer implements AssignmentOrderCompositionResultSerializer
{
    public function __construct() {}
    public function serialize(AssignmentOrderCompositionResult $result): array
    {
        if(!$result instanceof SelectionResult)throw new \InvalidArgumentException('Invalid selection result.');$p=$result->success();
        return ['status'=>$result->status()->value,'reasonCode'=>$result->reasonCode()?->value,'retryable'=>$result->retryable(),'requestId'=>$result->requestId()->value,
            'caseId'=>$p?->caseId,'assignmentOrderId'=>$p?->assignmentOrderId,'assignmentOrderVersion'=>$p?->assignmentOrderVersion,'selectionRevision'=>$p?->selectionRevision,
            'compositionIdentity'=>$p?->compositionIdentity,'compositionSha256'=>$p?->compositionSha256,'selectionDate'=>$p?->selectionDate,'selectedAt'=>$p?->selectedAt];
    }
}
