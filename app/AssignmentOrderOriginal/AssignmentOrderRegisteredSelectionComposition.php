<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Validate only the requested immutable dateless selection, never global readiness. */
final class AssignmentOrderRegisteredSelectionComposition
{
    public static function read(array $registry, array $header, array $members, int $case, int $order, int $version): AssignmentOrderCompositionSnapshot
    {
        $engineer = AssignmentOrderRegisteredCompositionValues::integer($header['control_engineer_user_id']);
        $revision = AssignmentOrderRegisteredCompositionValues::integer($header['selection_revision'], 4294967295);
        AssignmentOrderRegisteredCompositionValues::integer($header['selected_by_user_id']);
        AssignmentOrderRegisteredCompositionValues::require($header['selected_at_utc'] === $registry['allocated_at_utc'] && substr($header['selected_at_utc'], -6) === '000000');
        $at = AssignmentOrderRegisteredCompositionValues::instant($header['selected_at_utc']);
        AssignmentOrderRegisteredCompositionValues::require($header['selection_date'] === $at->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d'));
        $previous = $header['previous_selection_order_id']; $replaces = $header['replaces_selection_order_id'];
        if ($revision === 1) { AssignmentOrderRegisteredCompositionValues::require($previous === null); }
        else { AssignmentOrderRegisteredCompositionValues::integer($previous); }
        if ($header['mode'] === 'new_order') { AssignmentOrderRegisteredCompositionValues::require($replaces === null); }
        else { AssignmentOrderRegisteredCompositionValues::require($header['mode'] === 'replace_pending' && $revision > 1 && $replaces !== null && $replaces === $previous); }
        foreach (['control_engineer_fio_snapshot','control_engineer_position_snapshot'] as $key) { AssignmentOrderRegisteredCompositionValues::text($header[$key], 300); }
        AssignmentOrderRegisteredCompositionValues::require(count($members) >= 1 && count($members) <= 500); $ids = []; $last = 0;
        foreach ($members as $member) {
            AssignmentOrderRegisteredCompositionValues::require(AssignmentOrderRegisteredCompositionValues::integer($member['assignment_order_id']) === $order);
            $id = AssignmentOrderRegisteredCompositionValues::integer($member['installer_tab_id']); AssignmentOrderRegisteredCompositionValues::require($id > $last); $last = $id;
            foreach (['fio_snapshot','position_snapshot'] as $key) { AssignmentOrderRegisteredCompositionValues::text($member[$key], 300); }
            AssignmentOrderRegisteredCompositionValues::text($member['workforce_source_snapshot'], 80);
            AssignmentOrderRegisteredCompositionValues::sourceInstant($member['workforce_source_updated_at_snapshot']);
            $from = $member['employed_from_snapshot']; $to = $member['employed_to_snapshot'];
            AssignmentOrderRegisteredCompositionValues::require($member['employment_status_snapshot'] === 'employed' && AssignmentOrderOriginalDataScalar::date($from)
                && $from <= $header['selection_date'] && ($to === null || (AssignmentOrderOriginalDataScalar::date($to) && $to >= $header['selection_date'])));
            $ids[] = $id;
        }
        $result = AssignmentOrderRegisteredCompositionValues::result($case, $order, $version, $engineer, $ids);
        AssignmentOrderRegisteredCompositionValues::require($header['composition_identity'] === $result->identity && $header['composition_sha256'] === $result->sha256);
        return $result;
    }
}
