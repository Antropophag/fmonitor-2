<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require_once dirname(__DIR__,2).'/rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php';
require_once dirname(__DIR__,2).'/rapid-pilot/legacy-migration/MigrationQuarantineDecisionLedger.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\MariaDbSchemaInspector;
use FMonitor2\InstallationProcess\OtizEvidenceSchemaMigration;
use FMonitor2\InstallationProcess\OtizPublicationSchemaMigration;
use FMonitor2\InstallationProcess\PilotOtizSchemaMigration;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

function orsDb(?string $database=null):mysqli
{
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$database??'',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $db->set_charset('utf8mb4');
    return $db;
}

/** @return list<array<string,mixed>> */
function orsRows(mysqli $db,string $table,string $order):array
{
    return $db->query("SELECT * FROM `{$table}` ORDER BY {$order}")->fetch_all(MYSQLI_ASSOC);
}

/** @return list<array<string,mixed>> */
function orsColumns(mysqli $db,string $table):array
{
    $s=$db->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
    $s->bind_param('s',$table);$s->execute();return$s->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** @return array<string,list<string>> */
function orsIndexes(mysqli $db,string $table):array
{
    $s=$db->prepare('SELECT INDEX_NAME,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX');
    $s->bind_param('s',$table);$s->execute();$out=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$row)$out[(string)$row['INDEX_NAME']][]=(string)$row['COLUMN_NAME'];return$out;
}

function orsRuntimeSource(string $relative):string
{
    $source=file_get_contents(dirname(__DIR__,2).'/'.$relative);
    if(!is_string($source))throw new TestFailure('SETUP_FAILURE: cannot read '.$relative);
    return$source;
}

if(!class_exists(OtizPublicationSchemaMigration::class))throw new TestFailure('INTENDED_RED: canonical OTIZ publication schema migration v20 is absent');
if(!class_exists(OtizEvidenceSchemaMigration::class))throw new TestFailure('INTENDED_RED: canonical OTIZ evidence schema migration v21 is absent');

$database='t_ors_'.bin2hex(random_bytes(5));$admin=orsDb();$db=null;$failure=null;
try{
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$db=orsDb($database);
    $prefix='ors_';$decoy='ord_';$bad='orb_';$engine='ore_';$evidenceDrift='orf_';$clean='orc_';

    assertSameValue(true,PilotOtizSchemaMigration::apply($db,$prefix)['applied'],'historical seven-table OTIZ schema fixture');
    assertSameValue(true,PilotOtizSchemaMigration::apply($db,$decoy)['applied'],'opposite-prefix fixture');
    $db->query("INSERT INTO `{$prefix}fm2_pilot_otiz_snapshots`(id,report_date,status,previous_snapshot_id,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash)VALUES(41,'2026-08-31','accepted',NULL,'pilot-v1','2026-09-01T10:00:00+03:00',901,'2026-09-01T11:00:00+03:00',902,120000,20000,100000,'".str_repeat('a',64)."')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_otiz_events`(id,snapshot_id,object_id,event_type,payload_json,actor_user_id,occurred_at)VALUES(71,41,NULL,'snapshot_accepted','{}',902,'2026-09-01T11:00:00+03:00')");
    $before=[orsRows($db,$prefix.'fm2_pilot_otiz_snapshots','id'),orsRows($db,$prefix.'fm2_pilot_otiz_events','id')];
    assertSameValue(['applied'=>true,'schemaVersion'=>20,'tablesCreated'=>[$prefix.'fm2_otiz_publications']],OtizPublicationSchemaMigration::apply($db,$prefix),'v20 adds the publication receipt to the complete historical OTIZ family');
    assertSameValue($before,[orsRows($db,$prefix.'fm2_pilot_otiz_snapshots','id'),orsRows($db,$prefix.'fm2_pilot_otiz_events','id')],'v20 preserves accepted snapshots and append-only events exactly');
    $receipt=$prefix.'fm2_otiz_publications';
    assertSameValue([
        ['COLUMN_NAME'=>'snapshot_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'PRI','EXTRA'=>''],
        ['COLUMN_NAME'=>'actor_user_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'MUL','EXTRA'=>''],
        ['COLUMN_NAME'=>'operation_id','COLUMN_TYPE'=>'char(36)','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'request_sha256','COLUMN_TYPE'=>'char(64)','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'manifest_version','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'manifest_sha256','COLUMN_TYPE'=>'char(64)','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'object_count','COLUMN_TYPE'=>'int(10) unsigned','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'allocation_count','COLUMN_TYPE'=>'int(10) unsigned','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'issue_count','COLUMN_TYPE'=>'int(10) unsigned','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
        ['COLUMN_NAME'=>'published_at','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','COLUMN_KEY'=>'','EXTRA'=>''],
    ],orsColumns($db,$receipt),'receipt stores durable replay identity and independently checkable publication counts/digest');
    assertSameValue(['PRIMARY'=>['snapshot_id'],'uq_actor_operation'=>['actor_user_id','operation_id']],orsIndexes($db,$receipt),'snapshot and actor-scoped operation identities are exact');
    $db->query("INSERT INTO `{$receipt}` VALUES(41,901,'11111111-1111-4111-8111-111111111111','".str_repeat('b',64)."','otiz-publication-v1','".str_repeat('c',64)."',2,3,1,'2026-09-01T10:00:01+03:00')");
    $receiptBefore=orsRows($db,$receipt,'snapshot_id');
    assertSameValue(['applied'=>false,'schemaVersion'=>20,'tablesCreated'=>[]],OtizPublicationSchemaMigration::apply($db,$prefix),'v20 exact repeat is a no-op');
    assertSameValue($receiptBefore,orsRows($db,$receipt,'snapshot_id'),'v20 repeat preserves receipt exactly');
    assertSameValue([],array_values(array_filter(array_keys(orsIndexes($db,$decoy.'fm2_pilot_otiz_snapshots')),fn(string$n):bool=>$n==='uq_actor_operation')),'configured prefix does not alter the decoy family');

    $badTable=$bad.'fm2_otiz_publications';$db->query("CREATE TABLE `{$badTable}`(snapshot_id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB");$badBefore=orsColumns($db,$badTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>20,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$badTable]],OtizPublicationSchemaMigration::apply($db,$bad),'partial/wrong receipt refuses migration');
    assertSameValue($badBefore,orsColumns($db,$badTable),'v20 conflict performs no repair DDL');
    assertSameValue(true,PilotOtizSchemaMigration::apply($db,$engine)['applied'],'transaction-engine drift predecessor fixture');$engineTable=$engine.'fm2_pilot_otiz_snapshot_objects';$db->query("ALTER TABLE `{$engineTable}` ENGINE=MyISAM");
    assertSameValue(false,OtizPublicationSchemaMigration::isCompleteCompatible($db,$engine),'publication readiness rejects a non-transactional member of the write family');$engineBefore=orsColumns($db,$engineTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>20,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$engineTable]],OtizPublicationSchemaMigration::apply($db,$engine),'v20 refuses non-InnoDB publication family before creating a receipt');assertSameValue(false,MariaDbSchemaInspector::tableExists($db,$engine.'fm2_otiz_publications'),'engine conflict creates no misleading publication receipt');assertSameValue($engineBefore,orsColumns($db,$engineTable),'engine conflict performs no repair DDL');

    $legacyEvidenceDdl=[
        "CREATE TABLE `{$prefix}fm2_migrated_evidence_decisions`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,snapshot_id BIGINT UNSIGNED NOT NULL,snapshot_sha256 CHAR(64) NOT NULL,projection_sha256 CHAR(64) NOT NULL,source_locator VARCHAR(500) NOT NULL,issue_code VARCHAR(80) NOT NULL,outcome VARCHAR(40) NOT NULL,target_locator VARCHAR(500) NULL,reason VARCHAR(1000) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_operation(operation_id),KEY ix_snapshot_issue(snapshot_id,issue_code,id),KEY ix_actor(actor_user_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}fm2_migrated_evidence_projection`(snapshot_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,projection_version VARCHAR(80) NOT NULL,input_sha256 CHAR(64) NOT NULL,projection_sha256 CHAR(64) NOT NULL,classification VARCHAR(40) NOT NULL,evidence_grade CHAR(1) NOT NULL,confidence VARCHAR(20) NOT NULL,quarantine_count INT UNSIGNED NOT NULL,conflict_codes_json JSON NOT NULL,conflict_search VARCHAR(2000) NOT NULL,payload_json LONGTEXT NOT NULL,projected_at DATETIME NOT NULL,PRIMARY KEY(snapshot_id,legacy_object_id),KEY ix_filter(classification,evidence_grade,quarantine_count,legacy_object_id),KEY ix_object(legacy_object_id,snapshot_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE `{$prefix}fm2_migrated_evidence_conflicts`(snapshot_id BIGINT UNSIGNED NOT NULL,issue_code VARCHAR(80) NOT NULL,PRIMARY KEY(snapshot_id,issue_code),KEY ix_code(issue_code,snapshot_id)) ENGINE=InnoDB",
        "CREATE TABLE `{$prefix}fm2_migrated_evidence_decision_state`(snapshot_id BIGINT UNSIGNED NOT NULL,issue_code VARCHAR(80) NOT NULL,decision_id BIGINT UNSIGNED NOT NULL,outcome VARCHAR(40) NOT NULL,PRIMARY KEY(snapshot_id,issue_code),KEY ix_outcome(outcome,snapshot_id)) ENGINE=InnoDB",
        "CREATE TABLE `{$prefix}fm2_migration_quarantine_decisions`(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,source_locator VARCHAR(80) NOT NULL,source_cutoff_at DATETIME NOT NULL,source_digest CHAR(64) NOT NULL,classification_version VARCHAR(80) NOT NULL,quarantine_code VARCHAR(100) NOT NULL,outcome VARCHAR(40) NOT NULL,reason VARCHAR(1000) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_operation(operation_id),KEY ix_reference(source_locator,source_cutoff_at,source_digest,classification_version,quarantine_code,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach($legacyEvidenceDdl as$sql)$db->query($sql);
    $db->query("INSERT INTO `{$prefix}fm2_migrated_evidence_projection` VALUES(8,1450,'migrated-evidence-projection-v1','".str_repeat('1',64)."','".str_repeat('2',64)."','legacy_historical','B','medium',1,'[\"ORPHAN\"]','|ORPHAN|','{}','2026-09-01 08:00:00')");
    $db->query("INSERT INTO `{$prefix}fm2_migrated_evidence_conflicts` VALUES(8,'ORPHAN')");
    $db->query("INSERT INTO `{$prefix}fm2_migrated_evidence_decisions`(id,operation_id,request_sha256,snapshot_id,snapshot_sha256,projection_sha256,source_locator,issue_code,outcome,target_locator,reason,actor_user_id,occurred_at)VALUES(11,'22222222-2222-4222-8222-222222222222','".str_repeat('3',64)."',8,'".str_repeat('1',64)."','".str_repeat('2',64)."','fm_maintable/1450','ORPHAN','acknowledge',NULL,'reviewed',901,'2026-09-01T12:00:00+03:00')");
    $db->query("INSERT INTO `{$prefix}fm2_migrated_evidence_decision_state` VALUES(8,'ORPHAN',11,'acknowledge')");
    $db->query("INSERT INTO `{$prefix}fm2_migration_quarantine_decisions`(id,operation_id,request_sha256,source_locator,source_cutoff_at,source_digest,classification_version,quarantine_code,outcome,reason,actor_user_id,occurred_at)VALUES(12,'33333333-3333-4333-8333-333333333333','".str_repeat('4',64)."','hmac-sha256:".str_repeat('5',64)."','2026-09-01 07:00:00','".str_repeat('6',64)."','classifier-v1','SOURCE_GAP','acknowledge','reviewed',901,'2026-09-01T12:01:00+03:00')");
    $evidenceTables=['fm2_migrated_evidence_decisions'=>'id','fm2_migrated_evidence_projection'=>'snapshot_id,legacy_object_id','fm2_migrated_evidence_conflicts'=>'snapshot_id,issue_code','fm2_migrated_evidence_decision_state'=>'snapshot_id,issue_code','fm2_migration_quarantine_decisions'=>'id'];$evidenceBefore=[];foreach($evidenceTables as$table=>$order)$evidenceBefore[$table]=orsRows($db,$prefix.$table,$order);
    assertSameValue(['applied'=>false,'schemaVersion'=>21,'tablesCreated'=>[]],OtizEvidenceSchemaMigration::apply($db,$prefix),'v21 adopts the exact populated runtime-created evidence family');
    foreach($evidenceTables as$table=>$order)assertSameValue($evidenceBefore[$table],orsRows($db,$prefix.$table,$order),'v21 preserves populated '.$table);
    assertSameValue(['applied'=>false,'schemaVersion'=>21,'tablesCreated'=>[]],OtizEvidenceSchemaMigration::apply($db,$prefix),'v21 repeat is a no-op');
    assertSameValue(true,OtizEvidenceSchemaMigration::apply($db,$evidenceDrift)['applied'],'clean evidence drift fixture');$driftTable=$evidenceDrift.'fm2_migrated_evidence_decisions';$db->query("ALTER TABLE `{$driftTable}` DROP INDEX uq_operation");
    assertSameValue(false,OtizEvidenceSchemaMigration::isCompleteCompatible($db,$evidenceDrift),'evidence readiness rejects missing durable operation uniqueness');$driftBefore=orsColumns($db,$driftTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>21,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$driftTable]],OtizEvidenceSchemaMigration::apply($db,$evidenceDrift),'v21 refuses index drift without repair');assertSameValue($driftBefore,orsColumns($db,$driftTable),'v21 index conflict preserves existing rows and columns');

    $catalogue=ProductionPilotMigrationCatalogue::migrations();assertSameValue(OtizPublicationSchemaMigration::class,$catalogue[20]??null,'canonical catalogue owns publication schema at v20');assertSameValue(OtizEvidenceSchemaMigration::class,$catalogue[21]??null,'canonical catalogue owns evidence schema at v21');
    $canonical=CanonicalMigrationApplication::run($db,$clean,$catalogue);assertSameValue([0,true,24,range(1,24)],[$canonical['exitCode'],$canonical['result']['ok']??null,$canonical['result']['schemaVersion']??null,$canonical['result']['appliedVersions']??null],'clean canonical migration reaches the complete v24 frontier');
    $repeat=CanonicalMigrationApplication::run($db,$clean,$catalogue);assertSameValue([0,true,24,[]],[$repeat['exitCode'],$repeat['result']['ok']??null,$repeat['result']['schemaVersion']??null,$repeat['result']['appliedVersions']??null],'complete canonical v24 repeat is a no-op');
    assertSameValue(true,OtizPublicationSchemaMigration::isCompleteCompatible($db,$clean),'publication readiness recognizes canonical schema');assertSameValue(true,OtizEvidenceSchemaMigration::isCompleteCompatible($db,$clean),'evidence readiness recognizes canonical schema');

    foreach(['rapid-pilot/Otiz.php','rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php','rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php','rapid-pilot/legacy-migration/MigrationQuarantineDecisionLedger.php']as$file){$source=orsRuntimeSource($file);assertSameValue(0,preg_match('/\b(?:CREATE|ALTER|DROP|TRUNCATE)\s+(?:TABLE|INDEX)\b/i',$source),'retained OTIZ GET/POST reachable runtime has no DDL: '.$file);}
    foreach(['app/Otiz/MariaDbMigratedEvidenceDecisionLedger.php','app/Otiz/MariaDbMigratedEvidenceProjectionStore.php','app/Otiz/MariaDbMigrationQuarantineDecisionLedger.php'] as $file){$source=orsRuntimeSource($file);assertSameValue(0,preg_match('/\b(?:CREATE|ALTER|DROP|TRUNCATE)\s+(?:TABLE|INDEX)\b/i',$source),'Yii2 reachable OTIZ owner has no DDL: '.$file);}
    $store=orsRuntimeSource('app/Otiz/MariaDbMigratedEvidenceProjectionStore.php');assertSameValue(true,str_contains($store,'rebuildDecisionState'), 'rebuildDecisionState remains covered by runtime DDL inventory');assertSameValue(true,str_contains($store,'backfill'), 'projection backfill remains covered by runtime DDL inventory');
    (new MigratedEvidenceDecisionLedger($db,$clean))->ensureSchema();(new MigrationQuarantineDecisionLedger($db,$clean))->ensureSchema();
    echo"PASS canonical OTIZ v20/v21 schema preserves history and every retained runtime ensureSchema is readiness-only\n";
}catch(Throwable$error){$failure=$error;}finally{if($db instanceof mysqli)$db->close();$admin->query("DROP DATABASE IF EXISTS `{$database}`");$admin->close();}if($failure)throw$failure;
