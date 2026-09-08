<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionAuditWriteStatus: string
{
    case COMMITTED='committed';
    case ROLLED_BACK='rolled_back';
    case OUTCOME_UNKNOWN='outcome_unknown';
    case CAPACITY_EXHAUSTED='capacity_exhausted';
}
