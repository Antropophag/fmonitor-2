<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Own one consistent read-only transaction; never repair stored facts. */
final class MariaDbAssignmentOrderSelectionSchemaProof
{
    public static function read(\mysqli $db, string $prefix): ?array
    {
        $owned = false;
        try {
            if ($db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT) !== true) {
                throw AssignmentOrderSelectionSchemaValues::unavailable();
            }
            $owned = true;
            try { return self::capture($db, $prefix); }
            catch (\DomainException) { return null; }
        } finally {
            if ($owned && $db->rollback() !== true) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        }
    }

    private static function capture(\mysqli $db, string $prefix): ?array
    {
        $collation = MariaDbAssignmentOrderIdentityRegistryCatalog::collation($db);
        if ($collation === null || !AssignmentOrderIdentityRegistryMigration::isBackfillComplete($db, $prefix)) { return null; }
        // Completion already checked the approved exact registry/receipt shapes and immutable historical subset.
        $registry = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, "SELECT * FROM `{$prefix}fm2_assignment_order_identities` ORDER BY assignment_order_id");
        $tables = AssignmentOrderSelectionDefinitionSchemaMigration::tables($prefix);
        $shapes = []; $rows = []; $snapshots = []; $missing = false;
        foreach ($tables as $table) {
            $shape = MariaDbAssignmentOrderSelectionSchemaCatalog::shape($db, $table, $collation);
            if ($shape === null) { $missing = true; continue; }
            AssignmentOrderSelectionSchemaValues::require(!$missing);
            [$data, $snapshot] = MariaDbAssignmentOrderSelectionSchemaRows::capture($db, $shape);
            $shapes[] = $shape; $rows[] = $data; $snapshots[] = $snapshot;
        }
        if (count($shapes) < 5) {
            foreach ($rows as $data) { AssignmentOrderSelectionSchemaValues::require($data === []); }
            foreach ($registry as $identity) { AssignmentOrderSelectionSchemaValues::require($identity['source_kind'] !== 'selection'); }
            return ['count' => count($shapes), 'collation' => $collation, 'snapshot' => null];
        }
        $headers = AssignmentOrderSelectionSchemaComposition::prove($rows[0], $rows[1], $registry);
        $cases = [];
        foreach (MariaDbAssignmentOrderSelectionSchemaSql::rows($db, "SELECT id,legacy_installation_object_id FROM `{$prefix}fm2_installation_cases` ORDER BY id") as $row) {
            $cases[(string)$row['id']] = (string)$row['legacy_installation_object_id'];
        }
        $requests = AssignmentOrderSelectionSchemaRequests::prove($rows[2], $headers, $cases);
        AssignmentOrderSelectionSchemaHistory::prove($rows[3], $rows[4], $headers, $requests);
        return ['count' => 5, 'collation' => $collation, 'snapshot' => new AssignmentOrderSelectionSchemaSnapshot(hash('sha256', AssignmentOrderSelectionSchemaValues::json($shapes)), $snapshots)];
    }
}
