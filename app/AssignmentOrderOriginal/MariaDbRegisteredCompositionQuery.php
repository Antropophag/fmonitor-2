<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Shared source proof inside the caller-owned snapshot or write transaction. */
final class MariaDbRegisteredCompositionQuery
{
    public static function read(AssignmentOrderOriginalSql $sql, int $case, int $order, ?AssignmentOrderRegisteredCompositionReadObserver $observer = null, bool $lock = false): AssignmentOrderCompositionSnapshot
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        $rows = $sql->rows('SELECT * FROM ' . $sql->table('fm2_assignment_order_identities') . ' WHERE assignment_order_id=' . $order . $suffix);
        $observer?->observe(AssignmentOrderRegisteredCompositionReadPhase::REGISTRY_READ);
        AssignmentOrderRegisteredCompositionValues::require(count($rows) <= 1);
        if ($rows !== []) {
            $registry = $rows[0];
            AssignmentOrderRegisteredCompositionValues::require(AssignmentOrderRegisteredCompositionValues::integer($registry['assignment_order_id']) === $order);
            if (AssignmentOrderRegisteredCompositionValues::integer($registry['installation_case_id']) !== $case) {
                return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::NOT_FOUND, $case, $order);
            }
            $version = AssignmentOrderRegisteredCompositionValues::integer($registry['order_version'], 65535);
            AssignmentOrderRegisteredCompositionValues::require(in_array($registry['source_kind'], ['legacy_order','selection'], true));
            AssignmentOrderRegisteredCompositionValues::instant($registry['allocated_at_utc']);
        }
        $legacy = $rows !== [] && $registry['source_kind'] === 'legacy_order';
        $physical = $sql->rows('SELECT ' . ($legacy ? '*' : 'id') . ' FROM ' . $sql->table('fm2_assignment_orders') . ' WHERE id=' . $order . $suffix);
        $selection = $sql->rows('SELECT ' . ($rows !== [] && !$legacy ? '*' : 'assignment_order_id') . ' FROM ' . $sql->table('fm2_assignment_order_selections') . ' WHERE assignment_order_id=' . $order . $suffix);
        $observer?->observe(AssignmentOrderRegisteredCompositionReadPhase::SOURCE_READ);
        if ($rows === []) {
            AssignmentOrderRegisteredCompositionValues::require($physical === [] && $selection === []);
            return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::NOT_FOUND, $case, $order);
        }
        $legacy = $registry['source_kind'] === 'legacy_order'; $source = $legacy ? $physical : $selection; $opposite = $legacy ? $selection : $physical;
        AssignmentOrderRegisteredCompositionValues::require(count($source) === 1 && $opposite === []); $header = $source[0];
        AssignmentOrderRegisteredCompositionValues::require(AssignmentOrderRegisteredCompositionValues::integer($header[$legacy ? 'id' : 'assignment_order_id']) === $order
            && AssignmentOrderRegisteredCompositionValues::integer($header['installation_case_id']) === $case
            && AssignmentOrderRegisteredCompositionValues::integer($header[$legacy ? 'version_no' : 'order_version'], 65535) === $version);
        $members = $sql->rows('SELECT * FROM ' . $sql->table($legacy ? 'fm2_order_installers' : 'fm2_assignment_order_selection_members') . ' WHERE assignment_order_id=' . $order . ' ORDER BY installer_tab_id' . $suffix);
        $observer?->observe(AssignmentOrderRegisteredCompositionReadPhase::MEMBERS_READ);
        return $legacy ? AssignmentOrderRegisteredLegacyComposition::read($header, $members, $case, $order, $version)
            : AssignmentOrderRegisteredSelectionComposition::read($registry, $header, $members, $case, $order, $version);
    }
}
