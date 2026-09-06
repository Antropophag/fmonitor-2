<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaComposition
{
    public static function prove(array $headers, array $members, array $registry): array
    {
        $identities = []; $versions = []; $selections = [];
        foreach ($registry as $row) {
            $identities[$row['assignment_order_id']] = $row;
            $versions[$row['installation_case_id'] . ':' . $row['order_version']] = $row;
            if ($row['source_kind'] === 'selection') { $selections[$row['assignment_order_id']] = true; }
        }
        $grouped = [];
        foreach ($members as $member) { $grouped[$member['assignment_order_id']][] = $member; }
        $byId = []; $cases = [];
        foreach ($headers as $header) {
            $id = $header['assignment_order_id']; $identity = $identities[$id] ?? null;
            AssignmentOrderSelectionSchemaValues::require($identity !== null && $identity['source_kind'] === 'selection');
            foreach (['installation_case_id','order_version'] as $key) { AssignmentOrderSelectionSchemaValues::require($header[$key] === (string)$identity[$key]); }
            AssignmentOrderSelectionSchemaValues::require($header['selected_at_utc'] === $identity['allocated_at_utc']);
            $instant = AssignmentOrderSelectionSchemaValues::instant($header['selected_at_utc'], true);
            AssignmentOrderSelectionSchemaValues::require($header['selection_date'] === $instant->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d'));
            foreach (['control_engineer_fio_snapshot','control_engineer_position_snapshot'] as $key) { AssignmentOrderSelectionSchemaValues::text($header[$key], 300); }
            AssignmentOrderSelectionSchemaValues::require($header['composition_identity'] === 'composition-' . $id . '-v' . $header['order_version']);
            $crew = $grouped[$id] ?? []; AssignmentOrderSelectionSchemaValues::require(count($crew) >= 1 && count($crew) <= 500);
            $ids = [];
            foreach ($crew as $member) {
                foreach (['fio_snapshot','position_snapshot'] as $key) { AssignmentOrderSelectionSchemaValues::text($member[$key], 300); }
                AssignmentOrderSelectionSchemaValues::text($member['workforce_source_snapshot'], 80);
                AssignmentOrderSelectionSchemaValues::sourceInstant($member['workforce_source_updated_at_snapshot']);
                AssignmentOrderSelectionSchemaValues::require($member['employment_status_snapshot'] === 'employed' && $member['employed_from_snapshot'] <= $header['selection_date']
                    && ($member['employed_to_snapshot'] === null || $member['employed_to_snapshot'] >= $header['selection_date']));
                $ids[] = (int)$member['installer_tab_id'];
            }
            $sorted = array_values(array_unique($ids)); sort($sorted, SORT_NUMERIC);
            AssignmentOrderSelectionSchemaValues::require($ids === $sorted);
            $composition = ['caseId' => (int)$header['installation_case_id'],'compositionIdentity' => $header['composition_identity'],
                'engineerUserId' => (int)$header['control_engineer_user_id'],'installers' => $ids,'orderId' => (int)$id];
            AssignmentOrderSelectionSchemaValues::require($header['composition_sha256'] === hash('sha256', AssignmentOrderSelectionSchemaValues::json($composition)));
            $header['installerIds'] = $ids; $byId[$id] = $header; unset($grouped[$id], $selections[$id]);
            $cases[$header['installation_case_id']][] = $header;
        }
        AssignmentOrderSelectionSchemaValues::require($grouped === [] && $selections === []);
        foreach ($cases as $case => $history) {
            usort($history, static fn ($a, $b) => (int)$a['selection_revision'] <=> (int)$b['selection_revision']); $previous = null;
            foreach ($history as $offset => $header) {
                AssignmentOrderSelectionSchemaValues::require($header['selection_revision'] === (string)($offset + 1));
                AssignmentOrderSelectionSchemaValues::require($header['previous_selection_order_id'] === ($previous['assignment_order_id'] ?? null));
                if ($previous !== null) { AssignmentOrderSelectionSchemaValues::require((int)$header['order_version'] === (int)$previous['order_version'] + 1); }
                elseif ((int)$header['order_version'] > 1) { AssignmentOrderSelectionSchemaValues::require(($versions[$case . ':' . ((int)$header['order_version'] - 1)]['source_kind'] ?? null) === 'legacy_order'); }
                if ($header['mode'] === 'new_order') { AssignmentOrderSelectionSchemaValues::require($header['replaces_selection_order_id'] === null); }
                else { AssignmentOrderSelectionSchemaValues::require($header['mode'] === 'replace_pending' && $previous !== null && $header['replaces_selection_order_id'] === $previous['assignment_order_id']); }
                $previous = $header;
            }
        }
        return $byId;
    }
}
