<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum AssignmentOrderCompositionStatus: string
{
    case SELECTED='selected'; case REPLAYED='replayed';
    case REJECTED='rejected'; case CONFLICT='conflict'; case FAILED='failed';
}
