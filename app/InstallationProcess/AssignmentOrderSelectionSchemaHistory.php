<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaHistory
{
    public static function prove(array $events, array $audits, array $headers, array $requests): void
    {
        $seen = [];
        foreach ($events as $event) {
            $request = $requests[$event['request_id']] ?? null; $header = $headers[$event['assignment_order_id']] ?? null;
            AssignmentOrderSelectionSchemaValues::require($request !== null && $request['status'] === 'selected' && $header !== null
                && $request['assignment_order_id'] === $event['assignment_order_id'] && !isset($seen[$event['assignment_order_id']])
                && $event['event_type'] === 'assignment_order_composition_selected');
            foreach (['installation_case_id'=>'installation_case_id','assignment_order_version'=>'order_version','selection_revision'=>'selection_revision',
                'previous_selection_order_id'=>'previous_selection_order_id','replaces_selection_order_id'=>'replaces_selection_order_id',
                'composition_sha256'=>'composition_sha256','occurred_at_utc'=>'selected_at_utc','actor_user_id'=>'selected_by_user_id'] as $key => $field) {
                AssignmentOrderSelectionSchemaValues::require($event[$key] === $header[$field]);
            }
            $seen[$event['assignment_order_id']] = true;
        }
        AssignmentOrderSelectionSchemaValues::require(count($seen) === count($headers)); $backing = [];
        foreach ($audits as $audit) {
            AssignmentOrderSelectionSchemaRequests::reason($audit, true); $request = $requests[$audit['request_id']] ?? null;
            if ($audit['reason_code'] === 'authorization_denied') { continue; }
            AssignmentOrderSelectionSchemaValues::require($request !== null);
            if ($audit['reason_code'] === 'request_id_conflict') { continue; }
            foreach (['actor_user_id','installation_object_id','mode','status','reason_code'] as $key) { AssignmentOrderSelectionSchemaValues::require($audit[$key] === $request[$key]); }
            AssignmentOrderSelectionSchemaValues::require($audit['attempted_at_utc'] === $request['terminal_at_utc'] && !isset($backing[$audit['request_id']]));
            $backing[$audit['request_id']] = true;
        }
        AssignmentOrderSelectionSchemaValues::require(count($backing) === count($requests));
    }
}
