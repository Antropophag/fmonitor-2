<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionIdentityAllocationStatus: string
{
    case ALLOCATED='allocated';
    case CAPACITY_EXHAUSTED='capacity_exhausted';
    case PERSISTENCE_ERROR='persistence_error';
}
