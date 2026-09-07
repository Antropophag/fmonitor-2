<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Startup-owned empty legacy object contour for the native pilot adapters. */
final class PilotLegacyObjectSchemaMigration
{
    public static function apply(\mysqli$db,string$prefix=''):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$table=$prefix.'fm_maintable';
        if(MariaDbSchemaInspector::tableExists($db,$table)){try{MariaDbPilotLegacyObjectSchemaReadiness::assertReady($db,$prefix);return['applied'=>false];}catch(\Throwable){return['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];}}
        $db->query("CREATE TABLE `{$table}` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NULL,entrance VARCHAR(80) NULL,regnumber VARCHAR(120) NULL,workdatestart VARCHAR(40) NULL,workdateendadjusted VARCHAR(40) NULL,plan_finish_date VARCHAR(40) NULL,workdatefinish VARCHAR(40) NULL,ptoactdate VARCHAR(40) NULL,responsstroicontrol VARCHAR(80) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        MariaDbPilotLegacyObjectSchemaReadiness::assertReady($db,$prefix);return['applied'=>true];
    }
}
