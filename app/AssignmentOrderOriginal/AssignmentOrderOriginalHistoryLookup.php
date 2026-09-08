<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final readonly class AssignmentOrderOriginalHistoryLookup
{
    public function __construct(public AssignmentOrderOriginalHistoryStatus $status, public ?AssignmentOrderOriginalHistoryPage $page = null)
    {
        if (($status === AssignmentOrderOriginalHistoryStatus::FOUND) !== ($page !== null))
            throw new \InvalidArgumentException('Invalid original history lookup.');
    }
}
