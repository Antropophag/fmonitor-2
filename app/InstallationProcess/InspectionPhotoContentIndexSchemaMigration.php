<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Canonical v19 successor allowing retained identical photo evidence after revoke. */
final class InspectionPhotoContentIndexSchemaMigration
{
    private const TABLE='fm2_checklist_photos';
    public static function currentDefinitions(string $prefix, string $collation): array
    {
        $result = [];
        foreach (InspectionEvidenceDefinitionSchemaMigration::definitions($prefix, $collation) as $name => $definition) {
            $manifest = $definition['final'];
            if ($name === self::TABLE) {
                foreach ($manifest['indexes'] as &$index) {
                    if ($index['name'] === 'installation_case_id') $index['nonUnique'] = 1;
                }
                unset($index);
            }
            $result[$name] = $manifest;
        }
        return $result;
    }
    public static function apply(\mysqli $db,string $prefix=''):array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);$table=$prefix.self::TABLE;
        if(self::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>19,'indexesChanged'=>[]];
        if(!InspectionEvidenceSchemaMigration::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>19,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        $db->query('ALTER TABLE `'.$table.'` DROP INDEX installation_case_id,ADD KEY installation_case_id(installation_case_id,section_id,sha256)');
        if(!self::isCompleteCompatible($db,$prefix))throw new \RuntimeException('Inspection photo content index migration failed.');
        return['applied'=>true,'schemaVersion'=>19,'indexesChanged'=>[$table.'.installation_case_id']];
    }
    public static function isCompleteCompatible(\mysqli $db,string $prefix=''):bool
    {
        try{
            IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);$collation=IdentityAccessDefinitionSchemaMigration::databaseCollation($db);$definitions=InspectionEvidenceDefinitionSchemaMigration::definitions($prefix,$collation);
            foreach(InspectionEvidenceDefinitionSchemaMigration::tables()as$logical){$manifest=$definitions[$logical]['final'];if($logical===self::TABLE)foreach($manifest['indexes']as&$index)if($index['name']==='installation_case_id')$index['nonUnique']=1;unset($index);if(!MariaDbExactSchemaFingerprint::matches($db,$prefix.$logical,$manifest,$collation))return false;}
            return true;
        }catch(\Throwable){return false;}
    }
}
