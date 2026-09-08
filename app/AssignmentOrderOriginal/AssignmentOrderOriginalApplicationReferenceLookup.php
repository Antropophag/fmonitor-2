<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final readonly class AssignmentOrderOriginalApplicationReferenceLookup
{
    public function __construct(
        public AssignmentOrderOriginalApplicationReferenceStatus $status,
        public ?AssignmentOrderOriginalApplicationReference $reference = null,
    ) {
        if (($status === AssignmentOrderOriginalApplicationReferenceStatus::FOUND) !== ($reference !== null))
            throw new \InvalidArgumentException('Invalid original reference lookup.');
    }
}
