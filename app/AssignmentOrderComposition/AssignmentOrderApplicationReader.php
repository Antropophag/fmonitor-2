<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface AssignmentOrderApplicationReader
{
    public function readCurrent(int $objectId): AssignmentOrderApplicationReadResult;
    public function readHistory(int $objectId, int $afterSequence = 0, int $limit = 50): AssignmentOrderApplicationReadResult;
    public function confirmCurrent(int $objectId, int $applicationId): string;
}
