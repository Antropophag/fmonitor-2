<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum AssignmentOrderCompositionReason: string
{
    case INVALID_COMMAND='invalid_command';
    case AUTHORIZATION_DENIED='authorization_denied';
    case OBJECT_NOT_FOUND='object_not_found';
    case INSTALLER_REQUIRED='installer_required';
    case CONTROL_ENGINEER_REQUIRED='control_engineer_required';
    case INSTALLER_NOT_IN_CATALOG='installer_not_in_catalog';
    case INSTALLER_NOT_EMPLOYED='installer_not_employed';
    case CONTROL_ENGINEER_NOT_ELIGIBLE='control_engineer_not_eligible';
    case OBJECT_HAS_PTO_ACT='object_has_pto_act';
    case OBJECT_COMPLETED='object_completed';
    case NO_CHANGES='no_changes';
    case REQUEST_ID_CONFLICT='request_id_conflict';
    case STALE_SELECTION='stale_selection';
    case PENDING_SELECTION_EXISTS='pending_selection_exists';
    case SELECTION_NOT_FOUND='selection_not_found';
    case ORIGINAL_ALREADY_ACCEPTED='original_already_accepted';
    case DEPENDENCY_UNAVAILABLE='dependency_unavailable';
    case PERSISTENCE_FAILURE='persistence_failure';
    case PERSISTENCE_OUTCOME_UNKNOWN='persistence_outcome_unknown';
    case ALLOCATION_CAPACITY_EXHAUSTED='allocation_capacity_exhausted';
}
