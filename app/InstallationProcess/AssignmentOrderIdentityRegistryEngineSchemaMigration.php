<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Sole family lifecycle owner; never wired into application runtime or runner yet. */
final class AssignmentOrderIdentityRegistryEngineSchemaMigration
{
    public static function apply(\mysqli $connection,string $prefix,AssignmentOrderIdentityRegistryObserver $observer): array
    {
        AssignmentOrderIdentityRegistryValues::prefix($prefix);
        try {
            $state=MariaDbAssignmentOrderIdentityRegistrySql::one($connection,'SELECT @@in_transaction active,DATABASE() db,@@character_set_connection charset');
        } catch (\Throwable) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        if ((string)$state['active']!=='0' || $state['charset']!=='utf8mb4' || !is_string($state['db']) || $state['db']==='') {
            throw new \InvalidArgumentException('Invalid registry migration configuration.');
        }
        $name='fm2_aoir_'.substr(hash('sha256',$state['db']."\0".$prefix),0,48);
        $acquired=false;$failed=false;$result=null;
        try {
            $lock=MariaDbAssignmentOrderIdentityRegistrySql::one($connection,'SELECT GET_LOCK(?,5) n',[$name]);
            if ((string)$lock['n']!=='1') { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            $acquired=true;
            $observer->observe(AssignmentOrderIdentityRegistryPhase::LOCK_ACQUIRED);
            $plan=MariaDbAssignmentOrderIdentityRegistryPlan::build($connection,$prefix);
            if ($plan===false) { $result=['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT']; }
            elseif ($plan['repeat']) { $result=['applied'=>false]; }
            else {
                self::releaseTransaction($connection);
                foreach (AssignmentOrderIdentityRegistryDefinitionSchemaMigration::definitions($prefix,$plan['collation']) as $base=>$sql) {
                    if ($plan['exists'][$base]) { continue; }
                    if ($connection->query($sql)!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
                    $observer->observe($base===AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY
                        ? AssignmentOrderIdentityRegistryPhase::REGISTRY_CREATED : AssignmentOrderIdentityRegistryPhase::RECEIPTS_CREATED);
                }
                $current=MariaDbAssignmentOrderIdentityRegistryCatalog::nextId($connection,$prefix.'fm2_assignment_order_identities');
                if (AssignmentOrderIdentityRegistryValues::compare($current,$plan['frontier'])<0) {
                    if ($connection->query("ALTER TABLE `{$prefix}fm2_assignment_order_identities` AUTO_INCREMENT=".$plan['frontier'])!==true) {
                        throw AssignmentOrderIdentityRegistryValues::unavailable();
                    }
                }
                $observer->observe(AssignmentOrderIdentityRegistryPhase::FRONTIER_READY);
                self::releaseTransaction($connection);
                AssignmentOrderIdentityRegistryBackfillSchemaMigration::write($connection,$prefix,$plan,$observer);
                $result=['applied'=>true];
            }
        } catch (\Throwable) { $failed=true; }
        finally {
            try { self::releaseTransaction($connection); } catch (\Throwable) { $failed=true; }
            if ($acquired) {
                try {
                    $released=MariaDbAssignmentOrderIdentityRegistrySql::one($connection,'SELECT RELEASE_LOCK(?) n',[$name]);
                    if ((string)$released['n']!=='1') { $failed=true; }
                } catch (\Throwable) { $failed=true; }
            }
        }
        if ($failed || $result===null) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        return $result;
    }

    private static function releaseTransaction(\mysqli $db): void
    {
        if ((string)MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT @@in_transaction n')['n']==='1' && $db->rollback()!==true) {
            throw AssignmentOrderIdentityRegistryValues::unavailable();
        }
    }
}
