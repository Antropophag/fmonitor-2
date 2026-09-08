<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Additive completion-correction requisites storage; NULL inherits prior effective details. */
final class InstallationCompletionDetailsSchemaMigration
{
    public static function apply(\mysqli $db,string $prefix=''):array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);$table=$prefix.InstallationCompletionDefinitionSchemaMigration::CORRECTIONS;
        if(self::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>17,'columnsAdded'=>[]];
        if(!InstallationCompletionSchemaMigration::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>17,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        $db->query("ALTER TABLE `{$table}` ADD details VARCHAR(500) NULL AFTER fact_date");
        return['applied'=>true,'schemaVersion'=>17,'columnsAdded'=>[$table.'.details']];
    }

    public static function isCompleteCompatible(\mysqli $db,string $prefix=''):bool
    {
        try{
            IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);$collation=IdentityAccessDefinitionSchemaMigration::databaseCollation($db);$definitions=InstallationCompletionDefinitionSchemaMigration::definitions($prefix,$collation);$historical=$prefix===''?$definitions:InstallationCompletionDefinitionSchemaMigration::definitions($prefix,$collation,true);$root=InstallationCompletionDefinitionSchemaMigration::ROOT;$corrections=InstallationCompletionDefinitionSchemaMigration::CORRECTIONS;$manifests=[$definitions[$corrections]['manifest'],$historical[$corrections]['manifest']];
            foreach($manifests as&$manifest)array_splice($manifest['columns'],6,0,[['name'=>'details','type'=>'varchar(500)','nullable'=>'YES','default'=>null,'extra'=>'','generated'=>'NEVER','generationExpression'=>null,'charset'=>'utf8mb4','collation'=>$collation]]);unset($manifest);
            $rootReady=MariaDbInstallationCompletionSchemaFingerprint::matches($db,$prefix.$root,$definitions[$root]['manifest'],$collation);$correctionsReady=false;foreach($manifests as$manifest)$correctionsReady=$correctionsReady||MariaDbInstallationCompletionSchemaFingerprint::matches($db,$prefix.$corrections,$manifest,$collation);
            return $rootReady&&$correctionsReady;
        }catch(\Throwable){return false;}
    }
}
