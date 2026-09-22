<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Narrow persistence adapter for generation identity; schema remains migration-owned. */
final class MariaDbPilotStartupState
{
    public static function sentinel(\mysqli$db,string$p):?array
    {$r=$db->query("SELECT generation,fingerprint,manifest_nonce FROM `{$p}fm2_pilot_generation_sentinel` WHERE singleton_id=1")->fetch_assoc();return is_array($r)?$r:null;}
    public static function initialize(\mysqli$db,string$p,string$fingerprint,string$nonce):void
    {$s=$db->prepare("INSERT INTO `{$p}fm2_pilot_generation_sentinel` VALUES(1,1,?,?)");$s->bind_param('ss',$fingerprint,$nonce);$s->execute();}
    public static function userCount(\mysqli$db,string$p):int
    {return(int)$db->query("SELECT COUNT(*) n FROM `{$p}fm2_pilot_users`")->fetch_assoc()['n'];}
    public static function serverIdentity(\mysqli$db):string
    {return(string)$db->query('SELECT @@hostname identity')->fetch_assoc()['identity'];}
    public static function readinessIdentity(\mysqli$db,string$p):array
    {
        MariaDbSchemaInspector::validateTablePrefix($p);$table=$db->real_escape_string($p.'fm2_installation_cases');
        $row=$db->query("SELECT @@hostname server_identity,DATABASE() database_name,'{$table}' canonical_table,TABLE_ID table_id,SPACE table_space FROM information_schema.INNODB_SYS_TABLES WHERE NAME=CONCAT(DATABASE(),'/', '{$table}')")->fetch_assoc();
        if(!is_array($row)||$row['server_identity']===''||$row['database_name']===''||$row['canonical_table']!==$p.'fm2_installation_cases'||filter_var($row['table_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false||filter_var($row['table_space'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false)throw new \RuntimeException('STARTUP_NOT_READY');
        return['serverIdentity'=>(string)$row['server_identity'],'database'=>(string)$row['database_name'],'canonicalTable'=>(string)$row['canonical_table'],'tableId'=>(int)$row['table_id'],'tableSpace'=>(int)$row['table_space']];
    }
    public static function assertAvailable(\mysqli$db):void
    {
        try{if($db->query('SELECT 1')->fetch_row()!==['1'])throw new \RuntimeException();}catch(\Throwable){throw new \RuntimeException('DATABASE_UNAVAILABLE');}
    }
    public static function readinessMarker(\mysqli$db,string$p):array
    {
        try{$identity=self::readinessIdentity($db,$p);$version=max(array_keys(ProductionPilotMigrationCatalogue::migrations()));return['databaseId'=>hash('sha256',json_encode([$identity,$p],JSON_THROW_ON_ERROR)),'schemaVersion'=>$version];}catch(\Throwable){throw new \RuntimeException('STARTUP_NOT_READY');}
    }
}
