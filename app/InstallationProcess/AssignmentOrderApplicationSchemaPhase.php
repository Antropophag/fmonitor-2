<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

enum AssignmentOrderApplicationSchemaPhase:string
{
    case LOCK_ACQUIRED='lock_acquired';case APPLICATIONS_CREATED='applications_created';
    case ATTEMPTS_CREATED='attempts_created';case FAMILY_VERIFIED='family_verified';
}
