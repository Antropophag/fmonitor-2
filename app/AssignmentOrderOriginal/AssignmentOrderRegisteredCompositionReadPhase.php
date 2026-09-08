<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

enum AssignmentOrderRegisteredCompositionReadPhase: string
{
    case REGISTRY_READ='registry_read';
    case SOURCE_READ='source_read';
    case MEMBERS_READ='members_read';
    case BEFORE_RELEASE='before_release';
}
