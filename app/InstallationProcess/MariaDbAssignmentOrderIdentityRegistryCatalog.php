<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbAssignmentOrderIdentityRegistryCatalog
{
    public static function collation(\mysqli $db): ?string
    {
        $row=MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT DEFAULT_CHARACTER_SET_NAME c,DEFAULT_COLLATION_NAME v FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()');
        if ($row['c']!=='utf8mb4' || preg_match('/^[A-Za-z0-9_]+$/D',(string)$row['v'])!==1) { return null; }
        return IdentityAccessDefinitionSchemaMigration::databaseCollation($db);
    }

    public static function exists(\mysqli $db,string $table): bool
    {
        return MariaDbAssignmentOrderIdentityRegistrySql::rows($db,'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])!==[];
    }

    public static function nextId(\mysqli $db,string $table,string $maximum=AssignmentOrderIdentityRegistryValues::EXHAUSTED): string
    {
        $row=MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT CAST(AUTO_INCREMENT AS CHAR) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);
        return AssignmentOrderIdentityRegistryValues::decimal($row['n'],false,$maximum);
    }

    public static function compatible(\mysqli $db,string $prefix,string $base,string $collation): bool
    {
        $table=$prefix.$base;
        $properties=MariaDbAssignmentOrderIdentityRegistrySql::rows($db,'SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);
        if ($properties!==[['ENGINE'=>'InnoDB','TABLE_COLLATION'=>$collation]]) { return false; }
        $actual=MariaDbAssignmentOrderIdentityRegistrySql::rows($db,'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',[$table]);
        $expected=[];
        foreach (AssignmentOrderIdentityRegistryDefinitionSchemaMigration::columns($base) as [$name,$type,$extra,$ascii]) {
            $expected[]=[$name,$type,'NO',$extra,$ascii?'ascii':null,$ascii?'ascii_bin':null,null];
        }
        $actual=array_map(static fn($r)=>[$r['COLUMN_NAME'],preg_replace('/^(tinyint|smallint|bigint)\([0-9]+\)/','$1',strtolower($r['COLUMN_TYPE'])),$r['IS_NULLABLE'],$r['EXTRA'],$r['CHARACTER_SET_NAME'],$r['COLLATION_NAME'],$r['COLUMN_DEFAULT']],$actual);
        if ($actual!==$expected) { return false; }
        $actual=[];
        foreach (MariaDbSchemaInspector::indexes($db,$table) as $row) { $actual[$row['INDEX_NAME']]=[(int)$row['NON_UNIQUE'],$row['COLUMNS']]; }
        $expected=[];
        foreach (AssignmentOrderIdentityRegistryDefinitionSchemaMigration::indexes($base) as $name=>[$nonUnique,$columns]) {
            $expected[$name==='PRIMARY'?$name:$prefix.$name]=[$nonUnique,implode(',',array_map(static fn($column)=>$column.':FULL:A:NO',$columns))];
        }
        ksort($actual);ksort($expected);if ($actual!==$expected) { return false; }
        $actual=[];
        foreach (MariaDbAssignmentOrderIdentityRegistrySql::rows($db,'SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]) as $row) {
            $actual[$row['CONSTRAINT_NAME']]=AssignmentOrderIdentityRegistryPredicate::key($row['CHECK_CLAUSE']);
        }
        $expected=[];
        foreach (AssignmentOrderIdentityRegistryDefinitionSchemaMigration::checks($base) as $name=>$predicate) { $expected[$prefix.$name]=AssignmentOrderIdentityRegistryPredicate::key($predicate); }
        ksort($actual);ksort($expected);if ($actual!==$expected) { return false; }
        $foreign=self::foreignKeys($db,$table);
        $database=MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT DATABASE() n')['n'];
        $expected=$base===AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY
            ? [[$prefix.'fm2_aoir_fk_case','installation_case_id',$database,$prefix.'fm2_installation_cases','id','RESTRICT','RESTRICT']] : [];
        if ($foreign!==$expected) { return false; }
        return (string)MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT COUNT(*) n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=?',[$table])['n']==='0';
    }

    public static function foreignKeys(\mysqli $db,string $table): array
    {
        $rows=MariaDbAssignmentOrderIdentityRegistrySql::rows($db,'SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION',[$table]);
        // A SELECT-only principal can see referenced columns but not referential rules.
        // Missing authority to inspect rules is not evidence that the FK is absent.
        $visible=MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT COUNT(*) n FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME IS NOT NULL',[$table]);
        if ((string)count($rows)!==(string)$visible['n']) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        return array_map('array_values',$rows);
    }
}
