<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Preserve the approved physical reader's date/action semantics without altering its outcomes. */
final class AssignmentOrderRegisteredLegacyComposition
{
    public static function read(array $header, array $members, int $case, int $order, int $version): AssignmentOrderCompositionSnapshot
    {
        $engineer = AssignmentOrderRegisteredCompositionValues::integer($header['control_engineer_user_id']); $date = $header['order_date'];
        AssignmentOrderRegisteredCompositionValues::require(AssignmentOrderOriginalDataScalar::date($date)); $seen = []; $ids = [];
        foreach ($members as $member) {
            AssignmentOrderRegisteredCompositionValues::require(AssignmentOrderRegisteredCompositionValues::integer($member['assignment_order_id']) === $order);
            $id = AssignmentOrderRegisteredCompositionValues::integer($member['installer_tab_id']);
            $from = $member['valid_from']; $to = $member['valid_to']; $action = $member['change_action'];
            AssignmentOrderRegisteredCompositionValues::require(!isset($seen[$id]) && in_array($action, ['assign','retain','release'], true)
                && AssignmentOrderOriginalDataScalar::date($from) && $from <= $date
                && ($to === null || (AssignmentOrderOriginalDataScalar::date($to) && $to >= $from))
                && ($action !== 'release' || ($to !== null && $to <= $date)));
            $seen[$id] = true;
            if (in_array($action, ['assign','retain'], true) && ($to === null || $to >= $date)) { $ids[] = $id; }
        }
        sort($ids, SORT_NUMERIC);
        return AssignmentOrderRegisteredCompositionValues::result($case, $order, $version, $engineer, $ids);
    }
}
