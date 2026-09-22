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
        $row=$db->query("SELECT @@hostname server_identity,DATABASE() database_name,TABLE_NAME canonical_table,ENGINE table_engine,TABLE_COLLATION table_collation FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND TABLE_TYPE='BASE TABLE'")->fetch_assoc();
        if(!is_array($row)||$row['server_identity']===''||$row['database_name']===''||$row['canonical_table']!==$p.'fm2_installation_cases'||!is_string($row['table_engine'])||$row['table_engine']===''||!is_string($row['table_collation'])||$row['table_collation']==='')throw new \RuntimeException('STARTUP_NOT_READY');
        return['serverIdentity'=>(string)$row['server_identity'],'database'=>(string)$row['database_name'],'canonicalTable'=>(string)$row['canonical_table'],'tableEngine'=>$row['table_engine'],'tableCollation'=>$row['table_collation']];
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
