<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
interface AssignmentOrderOriginalSubmissionQuery
{
    public function resolveSubmissionContext(int $actorId,int $objectId,int $orderId,string $mode):array;
    public function readSubmissionForm(int $actorId,int $objectId,int $orderId):array;
}
