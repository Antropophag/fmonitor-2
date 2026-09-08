<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Immutable historical-subset validation; not live writer/source readiness. */
final class MariaDbAssignmentOrderIdentityRegistryHistory
{
    public static function snapshot(\mysqli $db,string $prefix): AssignmentOrderIdentityRegistrySnapshot
    {
        $collation=MariaDbAssignmentOrderIdentityRegistryCatalog::collation($db);
        if ($collation===null) { throw new \DomainException('Invalid family defaults.'); }
        foreach ([AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY,AssignmentOrderIdentityRegistryDefinitionSchemaMigration::RECEIPTS] as $base) {
            if (!MariaDbAssignmentOrderIdentityRegistryCatalog::compatible($db,$prefix,$base,$collation)) { throw new \DomainException('Invalid family shape.'); }
        }
        $identities=[];
        foreach (MariaDbAssignmentOrderIdentityRegistrySql::rows($db,"SELECT CAST(assignment_order_id AS CHAR) id,CAST(installation_case_id AS CHAR) case_id,CAST(order_version AS CHAR) version,source_kind,allocated_at_utc FROM `{$prefix}fm2_assignment_order_identities` ORDER BY assignment_order_id") as $row) {
            $identities[]=new AssignmentOrderIdentityRegistryRow(
                AssignmentOrderIdentityRegistryValues::decimal($row['id'],true,'18446744073709551615'),
                AssignmentOrderIdentityRegistryValues::decimal($row['case_id'],true,'18446744073709551615'),
                (int)AssignmentOrderIdentityRegistryValues::decimal($row['version'],true,'65535'),
                $row['source_kind'],AssignmentOrderIdentityRegistryValues::instant(str_replace(' ','T',$row['allocated_at_utc']).'Z'),
            );
        }
        $rows=MariaDbAssignmentOrderIdentityRegistrySql::rows($db,"SELECT * FROM `{$prefix}fm2_assignment_order_id_receipts`");
        if (count($rows)>1 || ($rows!==[] && (string)$rows[0]['singleton_id']!=='1')) { throw new \DomainException('Invalid receipt identity.'); }
        $receipt=null;
        if ($rows!==[]) {
            $r=$rows[0];
            $decimal=static fn($v)=>AssignmentOrderIdentityRegistryValues::decimal($v,true,'18446744073709551615');
            $receipt=new AssignmentOrderIdentityRegistryReceipt((int)$decimal($r['format_version']),
                $decimal($r['legacy_max_id']),$decimal($r['legacy_next_id']),$decimal($r['preserved_next_id']),
                $decimal($r['legacy_row_count']),$r['legacy_tuple_sha256'],$r['legacy_prepared_sha256']);
        }
        return new AssignmentOrderIdentityRegistrySnapshot($identities,$receipt,
            MariaDbAssignmentOrderIdentityRegistryCatalog::nextId($db,$prefix.'fm2_assignment_order_identities','18446744073709551615'));
    }

    public static function complete(\mysqli $db,string $prefix): bool
    {
        try { return self::matches($db,$prefix); }
        catch (\DomainException) { return false; }
    }

    private static function matches(\mysqli $db,string $prefix): bool
    {
        if (!MariaDbAssignmentOrderIdentityRegistrySourceShape::compatible($db,$prefix)) { return false; }
        $snapshot=self::snapshot($db,$prefix);$receipt=$snapshot->receipt;
        if ($receipt===null || $receipt->formatVersion!==1) { return false; }
        AssignmentOrderIdentityRegistryValues::decimal($receipt->legacyMaxId,true);
        AssignmentOrderIdentityRegistryValues::decimal($receipt->legacyNextId);
        AssignmentOrderIdentityRegistryValues::decimal($receipt->preservedNextId);
        AssignmentOrderIdentityRegistryValues::decimal($receipt->legacyRowCount,true);
        if (AssignmentOrderIdentityRegistryValues::compare($receipt->preservedNextId,$receipt->legacyNextId)<0
            || AssignmentOrderIdentityRegistryValues::compare($receipt->preservedNextId,$receipt->legacyMaxId)<=0) { return false; }
        $source=MariaDbAssignmentOrderIdentityRegistrySource::capture($db,$prefix);
        if (AssignmentOrderIdentityRegistryValues::compare($source['next'],$receipt->legacyNextId)<0) { return false; }
        $historical=array_values(array_filter($source['rows'],static fn($row)=>AssignmentOrderIdentityRegistryValues::compare($row['id'],$receipt->legacyMaxId)<=0));
        $summary=MariaDbAssignmentOrderIdentityRegistrySource::summary($historical);
        if ($summary!==['max'=>$receipt->legacyMaxId,'count'=>$receipt->legacyRowCount,'tuple'=>$receipt->tupleSha256,'prepared'=>$receipt->preparedSha256]) { return false; }
        $registry=[];$previous='0';
        foreach ($snapshot->identities as $row) {
            AssignmentOrderIdentityRegistryValues::decimal($row->id);
            AssignmentOrderIdentityRegistryValues::decimal($row->caseId);
            AssignmentOrderIdentityRegistryValues::decimal($row->version,false,'65535');
            if (AssignmentOrderIdentityRegistryValues::compare($row->id,$previous)<=0 || !in_array($row->sourceKind,['legacy_order','selection'],true)) { return false; }
            $previous=$row->id;
            if (AssignmentOrderIdentityRegistryValues::compare($row->id,$receipt->legacyMaxId)<=0) {
                if ($row->sourceKind!=='legacy_order') { return false; }
                $registry[]=[$row->id,$row->caseId,$row->version,$row->allocatedAtUtc];
            }
        }
        $expected=array_map(static fn($row)=>[$row['id'],$row['case'],$row['version'],$row['utc']],$historical);
        AssignmentOrderIdentityRegistryValues::decimal($snapshot->nextId,false,AssignmentOrderIdentityRegistryValues::EXHAUSTED);
        return $registry===$expected
            && AssignmentOrderIdentityRegistryValues::compare($snapshot->nextId,$receipt->preservedNextId)>=0
            && AssignmentOrderIdentityRegistryValues::compare($snapshot->nextId,$previous)>0;
    }
}
