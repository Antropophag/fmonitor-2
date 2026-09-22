<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class RuntimeReadinessMarkerSchemaMigration
{
    public const VERSION=33;
    public static function apply(\mysqli $db,string $prefix):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$table=$prefix.'fm2_runtime_readiness_marker';
        if(MariaDbSchemaInspector::tableExists($db,$table))return self::isCompleteCompatible($db,$prefix)?['applied'=>false,'schemaVersion'=>self::VERSION,'tablesCreated'=>[]]:['applied'=>false,'schemaVersion'=>self::VERSION,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        $db->query("CREATE TABLE `{$table}`(singleton_id TINYINT UNSIGNED NOT NULL,database_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,schema_version SMALLINT UNSIGNED NOT NULL,PRIMARY KEY(singleton_id),UNIQUE KEY(database_id),CHECK(singleton_id=1))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
        $db->query("INSERT INTO `{$table}` VALUES(1,UUID(),".self::VERSION.')');
        return['applied'=>true,'schemaVersion'=>self::VERSION,'tablesCreated'=>[$table]];
    }
    public static function isCompleteCompatible(\mysqli $db,string $prefix):bool
    {
        $table=$prefix.'fm2_runtime_readiness_marker';if(!MariaDbSchemaInspector::tableExists($db,$table))return false;
        try{$row=$db->query("SELECT database_id,schema_version FROM `{$table}` WHERE singleton_id=1")->fetch_assoc();return$row!==null&&preg_match('/^[0-9a-f-]{36}$/Di',(string)$row['database_id'])===1&&(int)$row['schema_version']===self::VERSION&&(int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetch_column()===1;}catch(\Throwable){return false;}
    }
}
