<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

interface AssignmentOrderOriginalAccessQuery
{
    /** @return array{status:string,canRead:bool,canUpload:bool,canCorrect:bool} */
    public function readAccess(int $actorId, int $objectId): array;
    public function readSubmissionForm(int $actorId, int $objectId, int $orderId): array;
}
