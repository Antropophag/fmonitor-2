<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

enum AssignmentOrderSelectionSchemaPhase: string
{
    case LOCK_ACQUIRED='lock_acquired';
    case SELECTIONS_CREATED='selections_created';
    case MEMBERS_CREATED='members_created';
    case REQUESTS_CREATED='requests_created';
    case EVENTS_CREATED='events_created';
    case AUDITS_CREATED='audits_created';
    case FAMILY_VERIFIED='family_verified';
}
