<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface AssignmentOrderCompositionResultSerializer
{
    /** @return array{status:string,reasonCode:?string,retryable:bool,
     * requestId:string,caseId:?int,assignmentOrderId:?int,
     * assignmentOrderVersion:?int,selectionRevision:?int,
     * compositionIdentity:?string,compositionSha256:?string,
     * selectionDate:?string,selectedAt:?string} */
    public function serialize(AssignmentOrderCompositionResult $result): array;
}
