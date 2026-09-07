<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
enum AssignmentOrderOriginalHistoryStatus: string
{
    case FOUND = 'found';
    case NOT_FOUND = 'not_found';
    case INVALID_ARGUMENT = 'invalid_argument';
    case UNAVAILABLE = 'unavailable';
}
