<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionStageStatus: string
{
    case STAGED='staged';
    case REQUEST_RACE='request_race';
    case PERSISTENCE_ERROR='persistence_error';
    case CAPACITY_EXHAUSTED='capacity_exhausted';
}
