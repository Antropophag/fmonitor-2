<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final readonly class AssignmentOrderOriginalDownloadLookup
{
    public function __construct(public AssignmentOrderOriginalHistoryStatus $status, public ?AssignmentOrderOriginalPreparedDownload $download = null)
    {
        if (($status === AssignmentOrderOriginalHistoryStatus::FOUND) !== ($download !== null))
            throw new \InvalidArgumentException('Invalid original download lookup.');
    }
}
