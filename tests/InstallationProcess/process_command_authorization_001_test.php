<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;
use FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject;
use FMonitor2\InstallationProcess\MariaDbProcessUserDirectory;
use FMonitor2\InstallationProcess\MariaDbWorkforceCatalog;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: PROCESS-COMMAND-AUTHORIZATION-001 v0.1.

function commandAuthConnection(?string $database = null): mysqli
{
    $connection = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $connection->set_charset('utf8mb4');
    return $connection;
}
function commandAuthRows(mysqli $connection, string $sql): array { return $connection->query($sql)->fetch_all(MYSQLI_ASSOC); }
function commandAuthTableState(mysqli $connection, string $table): array
{
    return [
        'create' => commandAuthRows($connection, "SHOW CREATE TABLE `{$table}`")[0]['Create Table'],
        'rows' => commandAuthRows($connection, "SELECT * FROM `{$table}` ORDER BY user_id,capability"),
        'autoIncrement' => commandAuthRows($connection, "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")[0]['AUTO_INCREMENT'],
    ];
}
function commandAuthChecks(mysqli $connection, string $table): array
{
    $checks = commandAuthRows($connection, "SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' AND tc.CONSTRAINT_TYPE='CHECK' ORDER BY tc.CONSTRAINT_NAME");
    return array_map(static function (array $check): array {
        $clause = strtolower(str_replace(['`',' ',"\n","\r","\t",'(',')'], '', $check['CHECK_CLAUSE']));
        return ['CONSTRAINT_NAME'=>$check['CONSTRAINT_NAME'],'SEMANTIC_CLAUSE'=>$clause];
    }, $checks);
}
function commandAuthNonCapabilityState(mysqli $connection, string $table, string $capabilityConstraintName = 'ck_fm2_process_user_capability'): array
{
    return [
        'table'=>commandAuthRows($connection,"SELECT ENGINE,TABLE_COLLATION,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'"),
        'columns'=>commandAuthRows($connection,"SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION"),
        'indexes'=>commandAuthRows($connection,"SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY INDEX_NAME='PRIMARY' DESC,INDEX_NAME,SEQ_IN_INDEX"),
        'otherChecks'=>array_values(array_filter(commandAuthChecks($connection,$table),static fn(array $check):bool=>$check['CONSTRAINT_NAME']!==$capabilityConstraintName)),
        'foreignKeys'=>commandAuthRows($connection,"SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND CONSTRAINT_TYPE='FOREIGN KEY'"),
        'rows'=>commandAuthRows($connection,"SELECT * FROM `{$table}` ORDER BY user_id,capability"),
    ];
}
function expectedCommandAuthOpenedProjection(): array
{
    return [
        'installationObjectId'=>4512,'processState'=>'working','actualStartDate'=>'2026-08-28','openedAt'=>'2026-08-28T12:45:00+03:00','openedByUserId'=>18,
        'assignmentOrders'=>[['version'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual','installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],'artifacts'=>[['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']]],
        ],
        'assignments'=>[['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary']],
        'openTasks'=>[],'installationOpened'=>true,'checklistAvailable'=>true,
        'events'=>[
            ['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual','artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']]],
            ['type'=>'assignment_order_registered','occurredAt'=>'2026-08-28T12:15:30+03:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'registrationNumber'=>'12-Р','registrationSource'=>'manual','registrationActorType'=>'user']],
            ['type'=>'installation_opened','occurredAt'=>'2026-08-28T12:45:00+03:00','actorId'=>18,'payload'=>['actualStartDate'=>'2026-08-28','assignmentOrderVersion'=>1,'installerCount'=>1]],
        ],
    ];
}

$database = 't_pca001_' . bin2hex(random_bytes(6)); $prefix = 'process_';
$admin = commandAuthConnection(); $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $admin->close();
$connection = commandAuthConnection($database);
try {
    ProductionProcessSchemaMigration::apply($connection,$prefix); WorkforceCatalogSchemaMigration::apply($connection,$prefix);
    $connection->query("CREATE TABLE fm_maintable (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NOT NULL,entrance VARCHAR(80) NOT NULL,regnumber VARCHAR(120) NOT NULL,workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users_roles (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ProcessUserCapabilitiesSchemaMigration::apply($connection,$prefix);
    $table=$prefix.'fm2_process_user_capabilities';
    $connection->query("INSERT INTO users_roles VALUES(5,'ФКР',1),(8,'Строительный контроль',1),(9,'Inactive',0)");
    $connection->query("INSERT INTO users VALUES(18,'Сидоров Сергей Сергеевич',5,1),(73,'Петров Пётр Петрович',8,1),(91,'Prepare',5,1),(92,'Confirm',5,1),(93,'Open',5,1),(94,'Engineer',8,1),(95,'Inactive role commands',9,1),(96,'No capabilities',5,1),(97,'Inactive user all capabilities',5,0)");
    $connection->query("INSERT INTO {$table} VALUES(18,'assignment_order.prepare',NULL),(73,'construction_control_engineer','Инженер строительного контроля'),(91,'assignment_order.prepare',NULL),(94,'construction_control_engineer','Инженер строительного контроля')");
    $v3Before=commandAuthTableState($connection,$table);

    assertSameValue(['applied'=>true,'schemaVersion'=>4,'constraintsChanged'=>['ck_fm2_process_user_capability']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$prefix),'V4 must forward-change only the capability CHECK.');
    assertSameValue($v3Before['rows'],commandAuthTableState($connection,$table)['rows'],'V4 must preserve all v3 rows exactly.');
    assertSameValue([
        ['CONSTRAINT_NAME'=>'ck_fm2_process_user_capability','SEMANTIC_CLAUSE'=>"capabilityin'assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer'"],
        ['CONSTRAINT_NAME'=>'ck_fm2_process_user_engineer_position','SEMANTIC_CLAUSE'=>"capability<>'construction_control_engineer'orposition_snapshotisnotnullandtrimposition_snapshot<>''"],
    ],commandAuthChecks($connection,$table),'V4 must expose exactly the two normative named semantic CHECK constraints.');
    assertSameValue([
        ['COLUMN_NAME'=>'user_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','CHARACTER_SET_NAME'=>null],
        ['COLUMN_NAME'=>'capability','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'position_snapshot','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'YES','CHARACTER_SET_NAME'=>'utf8mb4'],
    ],commandAuthRows($connection,"SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION"),'V4 must preserve exact columns, types, nullability and charset.');
    assertSameValue([['ENGINE'=>'InnoDB','TABLE_COLLATION'=>'utf8mb4_general_ci']],commandAuthRows($connection,"SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'"),'V4 must preserve the normative engine and table collation.');
    assertSameValue([
        ['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','SEQ_IN_INDEX'=>'1','COLUMN_NAME'=>'user_id'],
        ['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','SEQ_IN_INDEX'=>'2','COLUMN_NAME'=>'capability'],
        ['INDEX_NAME'=>'capability','NON_UNIQUE'=>'1','SEQ_IN_INDEX'=>'1','COLUMN_NAME'=>'capability'],
        ['INDEX_NAME'=>'capability','NON_UNIQUE'=>'1','SEQ_IN_INDEX'=>'2','COLUMN_NAME'=>'user_id'],
    ],commandAuthRows($connection,"SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY INDEX_NAME='PRIMARY' DESC,INDEX_NAME,SEQ_IN_INDEX"),'V4 must preserve exact primary and capability indexes without extras.');
    assertSameValue([],commandAuthRows($connection,"SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND CONSTRAINT_TYPE='FOREIGN KEY'"),'V4 must not introduce a cross-schema foreign key.');
    $v4State=commandAuthTableState($connection,$table);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$prefix),'Exact v4 repeat must be a no-op.');
    assertSameValue($v4State,commandAuthTableState($connection,$table),'V4 repeat must preserve complete schema, data and AUTO_INCREMENT state.');

    $quotedPrefix='quoted_'; $quotedTable=$quotedPrefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$quotedTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT quoted_capability CHECK(capability IN ('assignment_order. prepare','construction_control_engineer')),CONSTRAINT quoted_engineer CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$quotedTable}` VALUES(681,'assignment_order. prepare',NULL),(682,'construction_control_engineer','Quoted Literal Sentinel')");
    $quotedBefore=commandAuthTableState($connection,$quotedTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$quotedTable]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$quotedPrefix),'Spaces inside a quoted capability literal must remain semantic data and conflict.');
    assertSameValue($quotedBefore,commandAuthTableState($connection,$quotedTable),'Quoted-literal conflict must preserve complete catalog and sentinel data.');

    $groupedPrefix='grouped_'; $groupedTable=$groupedPrefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$groupedTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT grouped_capability CHECK(capability IN ('assignment_order.prepare','construction_control_engineer')),CONSTRAINT grouped_engineer CHECK((capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL) AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$groupedTable}` VALUES(671,'assignment_order.prepare','non-null required only by corrupt grouping'),(672,'construction_control_engineer','Grouped Sentinel Engineer')");
    $groupedBefore=commandAuthTableState($connection,$groupedTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$groupedTable]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$groupedPrefix),'Non-equivalent engineer CHECK grouping must conflict even when parenthesis-stripped text resembles the normative expression.');
    assertSameValue($groupedBefore,commandAuthTableState($connection,$groupedTable),'Non-equivalent engineer grouping conflict must preserve complete schema, checks, sentinel data, indexes, collation and AUTO_INCREMENT.');

    $dollarPrefix='dollar_'; $dollarTable=$dollarPrefix.'fm2_process_user_capabilities'; $dollarConstraint='cap$'.str_repeat('x',60);
    assertSameValue(64,strlen($dollarConstraint),'Safe generated dollar constraint fixture must exercise the MariaDB identifier length boundary.');
    $connection->query("CREATE TABLE `{$dollarTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT `{$dollarConstraint}` CHECK(capability IN ('assignment_order.prepare','construction_control_engineer')),CONSTRAINT dollar_engineer CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$dollarTable}` VALUES(691,'assignment_order.prepare',NULL),(692,'construction_control_engineer','Dollar Name Sentinel')");
    $dollarBefore=commandAuthNonCapabilityState($connection,$dollarTable,$dollarConstraint);
    assertSameValue(['applied'=>true,'schemaVersion'=>4,'constraintsChanged'=>['ck_fm2_process_user_capability']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$dollarPrefix),'A catalog-derived safe generated name containing dollar and exactly 64 bytes must upgrade.');
    assertSameValue($dollarBefore,commandAuthNonCapabilityState($connection,$dollarTable),'Dollar-name upgrade must preserve every fact except the replaced capability CHECK.');
    $dollarV4=commandAuthTableState($connection,$dollarTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$dollarPrefix),'Dollar-name upgraded v4 must repeat safely.');
    assertSameValue($dollarV4,commandAuthTableState($connection,$dollarTable),'Dollar-name v4 repeat must be a complete no-op.');

    $wrongV4Prefix='wrongv4_'; $wrongV4Table=$wrongV4Prefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$wrongV4Table}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT completed_but_wrong_name CHECK(capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')),CONSTRAINT historical_engineer CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$wrongV4Table}` VALUES(601,'assignment_order.prepare',NULL),(602,'assignment_order.confirm_registration',NULL),(603,'installation.open',NULL),(604,'construction_control_engineer','Wrong V4 Name Sentinel')");
    $wrongV4Before=commandAuthTableState($connection,$wrongV4Table);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$wrongV4Table]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$wrongV4Prefix),'Exact v4 semantics under a non-normative capability constraint name must conflict, not masquerade as completed state.');
    assertSameValue($wrongV4Before,commandAuthTableState($connection,$wrongV4Table),'Non-normative completed-v4 conflict must preserve complete state.');

    $historicalPrefix='historical_'; $historicalTable=$historicalPrefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$historicalTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT CONSTRAINT_1 CHECK(capability IN ('construction_control_engineer','assignment_order.prepare')),CONSTRAINT CONSTRAINT_2 CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$historicalTable}` VALUES(701,'assignment_order.prepare',NULL),(702,'construction_control_engineer','Historical Engineer')");
    $historicalBefore=commandAuthNonCapabilityState($connection,$historicalTable,'CONSTRAINT_1');
    assertSameValue(['applied'=>true,'schemaVersion'=>4,'constraintsChanged'=>['ck_fm2_process_user_capability']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$historicalPrefix),'Exact historical v3 semantic checks with safe generated names must migrate.');
    assertSameValue($historicalBefore,commandAuthNonCapabilityState($connection,$historicalTable),'Historical v4 must drop only the actual capability CHECK and preserve generated engineer CHECK name, schema, data, collation, indexes and AUTO_INCREMENT.');
    assertSameValue([
        ['CONSTRAINT_NAME'=>'ck_fm2_process_user_capability','SEMANTIC_CLAUSE'=>"capabilityin'assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer'"],
        ['CONSTRAINT_NAME'=>'CONSTRAINT_2','SEMANTIC_CLAUSE'=>"capability<>'construction_control_engineer'orposition_snapshotisnotnullandtrimposition_snapshot<>''"],
    ],commandAuthChecks($connection,$historicalTable),'Historical engineer CHECK name must survive beside the normative v4 capability CHECK.');
    $historicalV4=commandAuthTableState($connection,$historicalTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$historicalPrefix),'Historical-name completed v4 must repeat as a no-op.');
    assertSameValue($historicalV4,commandAuthTableState($connection,$historicalTable),'Historical-name v4 repeat must preserve complete state.');

    $ambiguousPrefix='ambiguous_'; $ambiguousTable=$ambiguousPrefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$ambiguousTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT capability_check_a CHECK(capability IN ('assignment_order.prepare','construction_control_engineer')),CONSTRAINT capability_check_b CHECK(capability IN ('construction_control_engineer','assignment_order.prepare')),CONSTRAINT engineer_position CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$ambiguousTable}` VALUES(711,'assignment_order.prepare',NULL),(712,'construction_control_engineer','Ambiguous Sentinel')");
    $ambiguousBefore=commandAuthTableState($connection,$ambiguousTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$ambiguousTable]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$ambiguousPrefix),'Two semantically identical capability CHECK candidates must fail closed as ambiguous.');
    assertSameValue($ambiguousBefore,commandAuthTableState($connection,$ambiguousTable),'Ambiguous semantic CHECK conflict must preserve all constraints, schema, data, collation, indexes and AUTO_INCREMENT.');

    $binaryPrefix='binary_'; $binaryTable=$binaryPrefix.'fm2_process_user_capabilities';
    $connection->query("CREATE TABLE `{$binaryTable}` (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300) NULL,PRIMARY KEY(user_id,capability),KEY capability(capability,user_id),CONSTRAINT ck_fm2_process_user_capability CHECK(capability IN ('assignment_order.prepare','construction_control_engineer')),CONSTRAINT ck_fm2_process_user_engineer_position CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    $connection->query("INSERT INTO `{$binaryTable}` VALUES(801,'assignment_order.prepare',NULL),(802,'construction_control_engineer','  Инженер строительного контроля  ')");
    $binaryBefore=commandAuthNonCapabilityState($connection,$binaryTable);
    assertSameValue(['applied'=>true,'schemaVersion'=>4,'constraintsChanged'=>['ck_fm2_process_user_capability']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$binaryPrefix),'Valid v3 with an alternate utf8mb4 collation must migrate successfully.');
    assertSameValue($binaryBefore,commandAuthNonCapabilityState($connection,$binaryTable),'Binary-collation v4 must change only capability CHECK, preserving table/column collations, rows, indexes, position CHECK, foreign keys and AUTO_INCREMENT exactly.');
    assertSameValue([
        ['CONSTRAINT_NAME'=>'ck_fm2_process_user_capability','SEMANTIC_CLAUSE'=>"capabilityin'assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer'"],
        ['CONSTRAINT_NAME'=>'ck_fm2_process_user_engineer_position','SEMANTIC_CLAUSE'=>"capability<>'construction_control_engineer'orposition_snapshotisnotnullandtrimposition_snapshot<>''"],
    ],commandAuthChecks($connection,$binaryTable),'Binary-collation fixture must contain exact normative v4 CHECKs.');
    $binaryV4=commandAuthTableState($connection,$binaryTable);
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$binaryPrefix),'Binary-collation v4 repeat must be a no-op.');
    assertSameValue($binaryV4,commandAuthTableState($connection,$binaryTable),'Binary-collation repeat must preserve complete schema, rows and AUTO_INCREMENT state.');

    $conflictPrefix='conflict_';
    $connection->query("CREATE TABLE {$conflictPrefix}fm2_process_user_capabilities (user_id BIGINT UNSIGNED NOT NULL,capability VARCHAR(80) NOT NULL,position_snapshot VARCHAR(300),extra_column INT,PRIMARY KEY(user_id,capability),KEY(capability,user_id),CONSTRAINT ck_fm2_process_user_capability CHECK(capability IN ('assignment_order.prepare','construction_control_engineer')),CONSTRAINT ck_fm2_process_user_engineer_position CHECK(capability<>'construction_control_engineer' OR position_snapshot IS NOT NULL AND TRIM(position_snapshot)<>'')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$conflictPrefix}fm2_process_user_capabilities VALUES(999,'assignment_order.prepare',NULL,7)");
    $conflictBefore=commandAuthTableState($connection,$conflictPrefix.'fm2_process_user_capabilities');
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$conflictPrefix.'fm2_process_user_capabilities']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,$conflictPrefix),'Non-exact source schema must conflict.');
    assertSameValue($conflictBefore,commandAuthTableState($connection,$conflictPrefix.'fm2_process_user_capabilities'),'Conflict must preserve complete schema/data/AUTO_INCREMENT state.');
    assertSameValue(['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['missing_fm2_process_user_capabilities']],ProcessCommandCapabilitiesSchemaMigration::apply($connection,'missing_'),'Missing v3 source must conflict.');
    try { ProcessCommandCapabilitiesSchemaMigration::apply($connection,'invalid-prefix;'); throw new TestFailure('Invalid v4 prefix must be rejected.'); } catch (InvalidArgumentException) {}

    $connection->query("INSERT INTO {$table} VALUES(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(92,'assignment_order.confirm_registration',NULL),(93,'installation.open',NULL),(95,'assignment_order.prepare',NULL),(95,'assignment_order.confirm_registration',NULL),(95,'installation.open',NULL),(97,'assignment_order.prepare',NULL),(97,'assignment_order.confirm_registration',NULL),(97,'installation.open',NULL),(97,'construction_control_engineer','Инженер строительного контроля')");
    $directory=new MariaDbProcessUserDirectory($connection,$prefix,'');
    $matrix=[91=>[true,false,false,null],92=>[false,true,false,null],93=>[false,false,true,null],94=>[false,false,false,['userId'=>94,'fullName'=>'Engineer','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer']],95=>[false,false,false,null],96=>[false,false,false,null],97=>[false,false,false,null]];
    foreach($matrix as $id=>$expected){assertSameValue($expected,[$directory->actorCanPrepareAssignmentOrder($id),$directory->actorCanConfirmOrderRegistration($id),$directory->actorCanOpenInstallation($id),$directory->findEngineerSnapshot($id)],"Authorization matrix must keep capability intents separate for user {$id}.");}

    $connection->query("INSERT INTO fm_maintable VALUES(4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-20','2026-12-20','2026-11-30',NULL)");
    $connection->query("INSERT INTO {$prefix}fm2_workforce_catalog VALUES(1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $connection->query("INSERT INTO {$prefix}fm2_installation_cases(legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $externalBefore=['legacy'=>commandAuthRows($connection,'SELECT * FROM fm_maintable ORDER BY id'),'workforce'=>commandAuthRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog ORDER BY installer_tab_id"),'users'=>commandAuthRows($connection,'SELECT * FROM users ORDER BY id'),'roles'=>commandAuthRows($connection,'SELECT * FROM users_roles ORDER BY id'),'capabilities'=>commandAuthRows($connection,"SELECT * FROM {$table} ORDER BY user_id,capability")];
    $legacy=new MariaDbLegacyInstallationObject($connection); $workforce=new MariaDbWorkforceCatalog($connection,$prefix);
    $facts=new class($legacy,$workforce,$directory){public string $now='2026-08-26T21:30:00+00:00';public function __construct(private object $legacy,private object $workforce,private object $directory){} public function actorCanPrepareAssignmentOrder(int $id):bool{return $this->directory->actorCanPrepareAssignmentOrder($id);} public function actorCanConfirmOrderRegistration(int $id):bool{return $this->directory->actorCanConfirmOrderRegistration($id);} public function actorCanOpenInstallation(int $id):bool{return $this->directory->actorCanOpenInstallation($id);} public function getInstallationObjectSnapshot(int $id):array{return $this->legacy->getInstallationObjectSnapshot($id);} public function findInstallerSnapshot(int|string $id):?array{return $this->workforce->findInstallerSnapshot($id);} public function findCurrentInstallerSnapshot(int|string $id):?array{return $this->workforce->findInstallerSnapshot($id);} public function findEngineerSnapshot(int $id):?array{return $this->directory->findEngineerSnapshot($id);} public function renderAssignmentOrder(array $input):array{return [['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix']];} public function now():string{return $this->now;}};
    $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$facts,$prefix));
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Prepare must use its exact production capability.');
    $facts->now='2026-08-28T12:15:30+03:00'; assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'processState'=>'assignment_order_prepared'],$process->confirmOrderRegistration(4512,1,' 12-Р ','manual',18),'Confirm must use its exact production capability.');
    $facts->now='2026-08-28T12:45:00+03:00'; assertSameValue(['accepted'=>true,'processState'=>'working','actualStartDate'=>'2026-08-28','openedAt'=>'2026-08-28T12:45:00+03:00','openedByUserId'=>18,'installationOpened'=>true,'checklistAvailable'=>true,'assignmentOrderVersion'=>1],$process->openInstallation(4512,'2026-08-28',18),'Open must use its exact production capability.');
    assertSameValue($externalBefore,['legacy'=>commandAuthRows($connection,'SELECT * FROM fm_maintable ORDER BY id'),'workforce'=>commandAuthRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog ORDER BY installer_tab_id"),'users'=>commandAuthRows($connection,'SELECT * FROM users ORDER BY id'),'roles'=>commandAuthRows($connection,'SELECT * FROM users_roles ORDER BY id'),'capabilities'=>commandAuthRows($connection,"SELECT * FROM {$table} ORDER BY user_id,capability")],'Public commands must not mutate legacy, Workforce or authorization configuration.');
    $connection->close(); unset($process,$facts,$legacy,$workforce,$directory); $connection=commandAuthConnection($database);
    $forbidden=new class{public function __call(string $name,array $args):never{throw new LogicException("External {$name} must not be read on reload.");}};
    assertSameValue(expectedCommandAuthOpenedProjection(),(new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,$prefix)))->getInstallationObjectProcess(4512),'New connection must hydrate the exact full opened projection without external reads.');
    echo "PASS: PROCESS-COMMAND-AUTHORIZATION-001\n";
} finally { try{$connection->close();}catch(Throwable){} $cleanup=commandAuthConnection();$cleanup->query("DROP DATABASE IF EXISTS `{$database}`");$cleanup->close(); }
