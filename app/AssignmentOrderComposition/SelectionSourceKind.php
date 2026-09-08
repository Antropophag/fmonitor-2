<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionSourceKind: string
{
    case LEGACY_ORDER='legacy_order';
    case SELECTION='selection';
}
