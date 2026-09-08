<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
enum AssignmentOrderOriginalApplicationGuardStatus: string
{
    case MATCHED = 'matched';
    case CHANGED = 'changed';
    case UNAVAILABLE = 'unavailable';
}
