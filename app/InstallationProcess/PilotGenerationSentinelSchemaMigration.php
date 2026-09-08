<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class PilotGenerationSentinelSchemaMigration
{
    public static function apply(\mysqli$db,string$prefix=''):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$table=$prefix.'fm2_pilot_generation_sentinel';
        if(MariaDbSchemaInspector::tableExists($db,$table)){try{MariaDbPilotLegacyObjectSchemaReadiness::assertGenerationSentinelReady($db,$prefix);return['applied'=>false];}catch(\Throwable){return['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];}}
        $db->query("CREATE TABLE `{$table}` (singleton_id TINYINT UNSIGNED NOT NULL,generation INT UNSIGNED NOT NULL,fingerprint CHAR(8) NOT NULL,manifest_nonce CHAR(64) NOT NULL,PRIMARY KEY(singleton_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        MariaDbPilotLegacyObjectSchemaReadiness::assertGenerationSentinelReady($db,$prefix);return['applied'=>true];
    }
}
