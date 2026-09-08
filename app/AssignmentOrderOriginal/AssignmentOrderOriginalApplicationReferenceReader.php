<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Trusted data port; callers authorize access and own subsequent application writes. */
interface AssignmentOrderOriginalApplicationReferenceReader
{
    public function readCurrent(int $objectId, int $orderId): AssignmentOrderOriginalApplicationReferenceLookup;
    public function confirmCurrent(AssignmentOrderOriginalApplicationReference $reference): AssignmentOrderOriginalApplicationGuardStatus;
}
