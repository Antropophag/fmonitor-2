<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Derives the authoritative legacy composition inside an owned transaction. */
final class AssignmentOrderOriginalSqlComposition
{
    public static function read(AssignmentOrderOriginalSql $sql, int $caseId, int $orderId,
        ?AssignmentOrderOriginalPersistenceObserver $observer = null, bool $lock = false): AssignmentOrderCompositionSnapshot
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        $rows = $sql->rows('SELECT id,installation_case_id,version_no,control_engineer_user_id,order_date FROM '
            .$sql->table('fm2_assignment_orders').' WHERE id='.$orderId.$suffix);
        $observer?->observe(AssignmentOrderOriginalPersistenceEvent::AFTER_COMPOSITION_ORDER_READ);
        if (count($rows) > 1) AssignmentOrderOriginalSql::fail();
        if ($rows === []) return self::empty(AssignmentOrderCompositionLookupStatus::NOT_FOUND, $caseId, $orderId);
        $row = $rows[0];
        $rowOrder = AssignmentOrderOriginalDataScalar::integer($row['id']);
        $rowCase = AssignmentOrderOriginalDataScalar::integer($row['installation_case_id']);
        if ($rowOrder !== $orderId || $rowCase === null) AssignmentOrderOriginalSql::fail();
        if ($rowCase !== $caseId) return self::empty(AssignmentOrderCompositionLookupStatus::NOT_FOUND, $caseId, $orderId);
        $version = AssignmentOrderOriginalDataScalar::integer($row['version_no']);
        $engineer = AssignmentOrderOriginalDataScalar::integer($row['control_engineer_user_id']);
        $members = $sql->rows('SELECT assignment_order_id,installer_tab_id,change_action,valid_from,valid_to FROM '
            .$sql->table('fm2_order_installers').' WHERE assignment_order_id='.$orderId.' ORDER BY installer_tab_id'.$suffix);
        $valid = $version !== null && $engineer !== null && AssignmentOrderOriginalDataScalar::date($row['order_date']);
        $ids = []; $seen = [];
        foreach ($members as $member) {
            if (AssignmentOrderOriginalDataScalar::integer($member['assignment_order_id']) !== $orderId) AssignmentOrderOriginalSql::fail();
            $id = AssignmentOrderOriginalDataScalar::integer($member['installer_tab_id']);
            $from = $member['valid_from']; $to = $member['valid_to']; $action = $member['change_action'];
            if ($id === null || isset($seen[$id]) || !in_array($action, ['assign', 'retain', 'release'], true)
                || !AssignmentOrderOriginalDataScalar::date($from) || $from > $row['order_date']
                || ($to !== null && (!AssignmentOrderOriginalDataScalar::date($to) || $to < $from))
                || ($action === 'release' && ($to === null || $to > $row['order_date']))) $valid = false;
            if ($id !== null) $seen[$id] = true;
            if ($id !== null && in_array($action, ['assign', 'retain'], true) && ($to === null || $to >= $row['order_date'])) $ids[] = $id;
        }
        if (!$valid || $ids === []) return self::empty(AssignmentOrderCompositionLookupStatus::FOUND, $caseId, $orderId);
        sort($ids, SORT_NUMERIC);
        $identity = 'composition-'.$orderId.'-v'.$version;
        $json = json_encode(['caseId' => $caseId, 'compositionIdentity' => $identity, 'engineerUserId' => $engineer,
            'installers' => $ids, 'orderId' => $orderId], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new AssignmentOrderCompositionSnapshot(AssignmentOrderCompositionLookupStatus::FOUND,
            $caseId, $orderId, $identity, hash('sha256', $json), $ids, $engineer);
    }

    public static function empty(AssignmentOrderCompositionLookupStatus $status, int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
    { return new AssignmentOrderCompositionSnapshot($status, $caseId, $orderId, null, null, [], null); }
}
