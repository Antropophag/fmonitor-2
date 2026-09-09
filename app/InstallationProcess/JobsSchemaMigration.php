<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Explicit canonical deployment owner; ordinary Jobs operations never call it. */
final class JobsSchemaMigration
{
    public static function apply(\mysqli $db,string $prefix): array
    {
        self::prefix($prefix);
        if((int)$db->query('SELECT @@in_transaction')->fetch_column()!==0)throw new \RuntimeException('JOBS_SCHEMA_TRANSACTION_ACTIVE');
        $definitions=JobsDefinitionSchemaMigration::tables();$missing=[];
        foreach($definitions as $name=>$definition){
            if(!MariaDbJobsSchemaFingerprint::exists($db,$prefix.$name))$missing[]=$name;
            elseif(!MariaDbJobsSchemaFingerprint::compatible($db,$prefix,$name,$definition))throw new \RuntimeException('JOBS_SCHEMA_CONFLICT');
        }
        foreach($missing as $name)$db->query(JobsDefinitionSchemaMigration::ddl($prefix,$name,$definitions[$name]));
        return ['applied'=>$missing!==[],'schemaVersion'=>23,'tablesCreated'=>array_map(static fn(string $name): string=>$prefix.$name,$missing)];
    }
    public static function isReady(\mysqli $db,string $prefix): bool
    {
        try{
            self::prefix($prefix);
            foreach(JobsDefinitionSchemaMigration::tables() as $name=>$definition)
                if(!MariaDbJobsSchemaFingerprint::compatible($db,$prefix,$name,$definition))return false;
            return true;
        }catch(\Throwable){return false;}
    }
    private static function prefix(string $prefix): void
    {
        if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException('Invalid Jobs prefix.');
    }
}
