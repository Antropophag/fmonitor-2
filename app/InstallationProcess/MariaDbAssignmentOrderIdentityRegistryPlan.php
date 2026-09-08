<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Whole-family, read-only preflight. False is a metadata conflict, never repair. */
final class MariaDbAssignmentOrderIdentityRegistryPlan
{
    public static function build(\mysqli $db,string $prefix): array|false
    {
        try { return self::inspect($db,$prefix); }
        catch (\DomainException) { return false; }
    }

    private static function inspect(\mysqli $db,string $prefix): array|false
    {
        $collation=MariaDbAssignmentOrderIdentityRegistryCatalog::collation($db);
        if ($collation===null || !MariaDbAssignmentOrderIdentityRegistrySourceShape::compatible($db,$prefix)) { return false; }
        $exists=[];
        foreach ([AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY,AssignmentOrderIdentityRegistryDefinitionSchemaMigration::RECEIPTS] as $base) {
            $exists[$base]=MariaDbAssignmentOrderIdentityRegistryCatalog::exists($db,$prefix.$base);
            if ($exists[$base] && !MariaDbAssignmentOrderIdentityRegistryCatalog::compatible($db,$prefix,$base,$collation)) { return false; }
        }
        if ($exists[AssignmentOrderIdentityRegistryDefinitionSchemaMigration::RECEIPTS]
            && (string)MariaDbAssignmentOrderIdentityRegistrySql::one($db,"SELECT COUNT(*) n FROM `{$prefix}fm2_assignment_order_id_receipts`")['n']!=='0') {
            return MariaDbAssignmentOrderIdentityRegistryHistory::complete($db,$prefix) ? ['repeat'=>true] : false;
        }
        $registryExists=$exists[AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY];
        if ($registryExists && (string)MariaDbAssignmentOrderIdentityRegistrySql::one($db,"SELECT COUNT(*) n FROM `{$prefix}fm2_assignment_order_identities`")['n']!=='0') { return false; }
        $source=MariaDbAssignmentOrderIdentityRegistrySource::capture($db,$prefix);
        $frontier=$source['next'];
        $candidates=[AssignmentOrderIdentityRegistryValues::increment($source['max']),
            $registryExists?MariaDbAssignmentOrderIdentityRegistryCatalog::nextId($db,$prefix.'fm2_assignment_order_identities'):'1'];
        foreach ($candidates as $candidate) {
            if (AssignmentOrderIdentityRegistryValues::compare($candidate,$frontier)>0) { $frontier=$candidate; }
        }
        AssignmentOrderIdentityRegistryValues::decimal($frontier);
        return ['repeat'=>false,'source'=>$source,'frontier'=>$frontier,'exists'=>$exists,'collation'=>$collation];
    }
}
