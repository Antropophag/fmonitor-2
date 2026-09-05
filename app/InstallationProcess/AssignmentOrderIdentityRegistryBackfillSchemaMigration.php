<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Transaction delegate of the single registry migration engine. */
final class AssignmentOrderIdentityRegistryBackfillSchemaMigration
{
    public static function write(\mysqli $db,string $prefix,array $plan,AssignmentOrderIdentityRegistryObserver $observer): void
    {
        if ($db->begin_transaction()!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        $committed=false;
        try {
            $source=MariaDbAssignmentOrderIdentityRegistrySource::capture($db,$prefix,true);
            if ($source!==$plan['source'] || !MariaDbAssignmentOrderIdentityRegistrySourceShape::compatible($db,$prefix)) {
                throw AssignmentOrderIdentityRegistryValues::unavailable();
            }
            $statement=$db->prepare("INSERT INTO `{$prefix}fm2_assignment_order_identities` (assignment_order_id,installation_case_id,order_version,source_kind,allocated_at_utc) VALUES (?,?,?,'legacy_order',?)");
            if ($statement===false) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            try {
                foreach ($source['rows'] as $row) {
                    $date=str_replace(['T','Z'],[' ',''],$row['utc']);
                    if ($statement->execute([$row['id'],$row['case'],(string)$row['version'],$date])!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
                }
            } finally { $statement->close(); }
            $statement=$db->prepare("INSERT INTO `{$prefix}fm2_assignment_order_id_receipts` (singleton_id,format_version,legacy_max_id,legacy_next_id,preserved_next_id,legacy_row_count,legacy_tuple_sha256,legacy_prepared_sha256) VALUES (1,1,?,?,?,?,?,?)");
            if ($statement===false) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            try {
                if ($statement->execute([$source['max'],$source['next'],$plan['frontier'],$source['count'],$source['tuple'],$source['prepared']])!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            } finally { $statement->close(); }
            $observer->observe(AssignmentOrderIdentityRegistryPhase::BEFORE_BACKFILL_COMMIT);
            if ($db->commit()!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            $committed=true;
            $observer->observe(AssignmentOrderIdentityRegistryPhase::BACKFILL_COMMITTED);
        } finally {
            if (!$committed && $db->rollback()!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        }
    }
}
