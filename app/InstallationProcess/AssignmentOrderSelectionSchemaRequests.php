<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaRequests
{
    public const REJECTED = ['object_not_found','installer_required','control_engineer_required','installer_not_in_catalog','installer_not_employed','control_engineer_not_eligible','object_has_pto_act','object_completed','no_changes'];
    public const CONFLICT = ['stale_selection','pending_selection_exists','selection_not_found','original_already_accepted'];
    public const SUCCESS = ['case_id'=>'installation_case_id','assignment_order_id'=>'assignment_order_id','assignment_order_version'=>'order_version',
        'selection_revision'=>'selection_revision','composition_identity'=>'composition_identity','composition_sha256'=>'composition_sha256','selection_date'=>'selection_date','selected_at_utc'=>'selected_at_utc'];

    public static function reason(array $row, bool $audit): void
    {
        AssignmentOrderSelectionSchemaValues::uuid($row['request_id']);
        AssignmentOrderSelectionSchemaValues::require(in_array($row['mode'], ['new_order','replace_pending'], true));
        $reasons = match ($row['status']) {
            'selected' => [null], 'rejected' => $audit ? [...self::REJECTED, 'authorization_denied'] : self::REJECTED,
            'conflict' => $audit ? [...self::CONFLICT, 'request_id_conflict'] : self::CONFLICT, default => [],
        };
        AssignmentOrderSelectionSchemaValues::require(in_array($row['reason_code'], $reasons, true));
    }

    public static function prove(array $rows, array $headers, array $cases): array
    {
        $requests = []; $selected = [];
        foreach ($rows as $row) {
            self::reason($row, false);
            try { $ids = json_decode($row['installer_tab_ids_json'], true, 512, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { throw new \DomainException('Invalid selection intent.'); }
            AssignmentOrderSelectionSchemaValues::require(is_array($ids) && array_is_list($ids) && count($ids) <= 500);
            foreach ($ids as $id) { AssignmentOrderSelectionSchemaValues::require(is_int($id) && $id > 0); }
            $sorted = array_values(array_unique($ids)); sort($sorted, SORT_NUMERIC);
            AssignmentOrderSelectionSchemaValues::require($ids === $sorted && $row['installer_tab_ids_json'] === AssignmentOrderSelectionSchemaValues::json($ids));
            $intent = ['actorUserId'=>(int)$row['actor_user_id'],'controlEngineerUserId'=>$row['control_engineer_user_id'] === null ? null : (int)$row['control_engineer_user_id'],
                'expectedSelectionRevision'=>(int)$row['expected_selection_revision'],'installationObjectId'=>(int)$row['installation_object_id'],'installerTabIds'=>$ids,'mode'=>$row['mode']];
            AssignmentOrderSelectionSchemaValues::require($row['operation_fingerprint'] === hash('sha256', AssignmentOrderSelectionSchemaValues::json($intent)));
            if ($row['status'] === 'selected') {
                $header = $headers[$row['assignment_order_id']] ?? null;
                AssignmentOrderSelectionSchemaValues::require($header !== null && !isset($selected[$row['assignment_order_id']]));
                foreach (self::SUCCESS as $key => $field) { AssignmentOrderSelectionSchemaValues::require($row[$key] === $header[$field]); }
                AssignmentOrderSelectionSchemaValues::require($ids === $header['installerIds'] && $row['control_engineer_user_id'] === $header['control_engineer_user_id']
                    && $row['actor_user_id'] === $header['selected_by_user_id'] && $row['mode'] === $header['mode']
                    && (int)$row['expected_selection_revision'] === (int)$header['selection_revision'] - 1
                    && $row['terminal_at_utc'] === $header['selected_at_utc'] && $row['installation_object_id'] === ($cases[$header['installation_case_id']] ?? null));
                $selected[$row['assignment_order_id']] = true;
            } else { foreach (self::SUCCESS as $key => $_) { AssignmentOrderSelectionSchemaValues::require($row[$key] === null); } }
            $requests[$row['request_id']] = $row;
        }
        AssignmentOrderSelectionSchemaValues::require(count($selected) === count($headers));
        return $requests;
    }
}
