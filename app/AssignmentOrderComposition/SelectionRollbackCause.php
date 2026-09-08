<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionRollbackCause: string
{
    case DEPENDENCY_UNAVAILABLE='dependency_unavailable';
    case PERSISTENCE_FAILURE='persistence_failure';
    case ALLOCATION_CAPACITY_EXHAUSTED='allocation_capacity_exhausted';
}
