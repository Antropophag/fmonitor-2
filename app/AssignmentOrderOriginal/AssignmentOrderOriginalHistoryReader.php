<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
/** Trusted data port; consumers authorize actor and object scope before access. */
interface AssignmentOrderOriginalHistoryReader
{
    public function readHistory(int $objectId, int $orderId, int $afterRevisionNumber = 0, int $limit = 50): AssignmentOrderOriginalHistoryLookup;
    public function prepareDownload(int $objectId, int $orderId, string $revisionId): AssignmentOrderOriginalDownloadLookup;
}
