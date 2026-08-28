<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;
use FMonitor2\InstallationProcess\MariaDbWorkforceCatalog;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: WORKFORCE-CATALOG-001 v0.1.

function workforceConnection(?string $database = null): mysqli
{
    $connection = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $connection->set_charset('utf8mb4');
    return $connection;
}

function workforceRows(mysqli $connection, string $sql): array
{
    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function workforceProcessSchema(mysqli $connection, string $prefix): array
{
    $schema=[];
    foreach (['fm2_installation_cases','fm2_assignment_orders','fm2_order_installers','fm2_order_artifacts','fm2_process_tasks','fm2_process_events'] as $table) {
        $schema[$table]=workforceRows($connection,"SHOW CREATE TABLE {$prefix}{$table}")[0]['Create Table'];
    }
    return $schema;
}

function workforceDatabaseState(mysqli $connection): array
{
    $state=[];
    foreach (workforceRows($connection,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME") as $row) {
        $table=(string)$row['TABLE_NAME'];
        $state[$table]=[
            'create'=>workforceRows($connection,"SHOW CREATE TABLE `{$table}`")[0]['Create Table'],
            'rows'=>workforceRows($connection,"SELECT * FROM `{$table}`"),
        ];
    }
    return $state;
}

function expectedWorkforceProjection(): array
{
    return [
        'installationObjectId'=>4512,'processState'=>'assignment_order_prepared',
        'assignmentOrders'=>[[
            'version'=>1,'status'=>'prepared','registrationNumber'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
            'artifacts'=>[
                ['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],
                ['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
            ],
        ]],
        'assignments'=>[['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary']],
        'openTasks'=>[],'installationOpened'=>false,'checklistAvailable'=>false,
        'events'=>[['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>[
            'assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual',
            'artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
        ]]],
    ];
}

$database='t_wc001_'.bin2hex(random_bytes(6));
$prefix='process_';
$conflictPrefix='conflict_';
$uniqueOnlyPrefix='unique_only_';
$admin=workforceConnection();
$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");
$admin->close();
$connection=workforceConnection($database);

try {
    ProductionProcessSchemaMigration::apply($connection,$prefix);
    $connection->query("INSERT INTO {$prefix}fm2_installation_cases (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $processCaseBefore=workforceRows($connection,"SELECT * FROM {$prefix}fm2_installation_cases");
    $processSchemaBefore=workforceProcessSchema($connection,$prefix);

    assertSameValue(['applied'=>true,'schemaVersion'=>2,'tablesCreated'=>[$prefix.'fm2_workforce_catalog']], WorkforceCatalogSchemaMigration::apply($connection,$prefix), 'Additive v2 migration must create only the workforce catalog.');
    assertSameValue($processCaseBefore,workforceRows($connection,"SELECT * FROM {$prefix}fm2_installation_cases"),'Additive v2 migration must preserve process facts.');
    assertSameValue($processSchemaBefore,workforceProcessSchema($connection,$prefix),'Additive v2 migration must preserve all v1 keys and auto-increment state.');
    assertSameValue([
        $prefix.'fm2_assignment_orders',$prefix.'fm2_installation_cases',$prefix.'fm2_order_artifacts',$prefix.'fm2_order_installers',$prefix.'fm2_process_events',$prefix.'fm2_process_tasks',$prefix.'fm2_workforce_catalog',
    ],array_column(workforceRows($connection,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME"),'TABLE_NAME'),'Fresh v2 must leave exactly six v1 tables plus one workforce table and no hidden extras.');
    assertSameValue([],workforceRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'Fresh workforce table must be empty.');

    $columns=workforceRows($connection,"SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_workforce_catalog' ORDER BY ORDINAL_POSITION");
    assertSameValue([
        ['COLUMN_NAME'=>'installer_tab_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
        ['COLUMN_NAME'=>'fio','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'position','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'employment_status','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'employed_from','COLUMN_TYPE'=>'date','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
        ['COLUMN_NAME'=>'employed_to','COLUMN_TYPE'=>'date','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
        ['COLUMN_NAME'=>'workforce_source','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'workforce_source_updated_at','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
    ],$columns,'Workforce catalog columns must match the approved literal contract.');
    $table=workforceRows($connection,"SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_workforce_catalog'")[0];
    assertSameValue('InnoDB',$table['ENGINE'],'Workforce catalog must use InnoDB.');
    assertSameValue(true,str_starts_with((string)$table['TABLE_COLLATION'],'utf8mb4_'),'Workforce catalog must use utf8mb4.');
    $indexes=workforceRows($connection,"SELECT NON_UNIQUE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COLLATION,':',IGNORED) ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_workforce_catalog' GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY NON_UNIQUE,COLUMNS");
    assertSameValue([
        ['NON_UNIQUE'=>'0','COLUMNS'=>'installer_tab_id:FULL:A:NO'],
        ['NON_UNIQUE'=>'1','COLUMNS'=>'employment_status:FULL:A:NO,employed_to:FULL:A:NO'],
    ],$indexes,'Workforce catalog must expose only its exact primary and status indexes.');
    $checks=workforceRows($connection,"SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_workforce_catalog'");
    $normalizedChecks=array_map(static fn(array $row):string=>str_replace(['`',' '],'',strtolower((string)$row['CHECK_CLAUSE'])),$checks);
    assertSameValue(["employment_statusin('employed','dismissed')"],$normalizedChecks,'Workforce status check must be exact.');
    assertSameValue([],workforceRows($connection,"SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_workforce_catalog' AND REFERENCED_TABLE_NAME IS NOT NULL"),'Workforce catalog must have no foreign keys.');

    $connection->query("INSERT INTO {$prefix}fm2_workforce_catalog VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $catalogBefore=workforceRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog");
    $repeatStateBefore=workforceDatabaseState($connection);
    assertSameValue(['applied'=>false,'schemaVersion'=>2,'tablesCreated'=>[]],WorkforceCatalogSchemaMigration::apply($connection,$prefix),'Compatible repeat must perform no DDL or DML.');
    assertSameValue($catalogBefore,workforceRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'Compatible repeat must preserve catalog data.');
    assertSameValue($repeatStateBefore,workforceDatabaseState($connection),'Compatible repeat must preserve complete v1/v2 schema, data, keys and auto-increment state.');

    $connection->query("CREATE TABLE {$conflictPrefix}fm2_workforce_catalog (installer_tab_id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$conflictPrefix}fm2_workforce_catalog VALUES (777)");
    $conflictBefore=workforceRows($connection,"SELECT * FROM {$conflictPrefix}fm2_workforce_catalog");
    $conflictStateBefore=workforceDatabaseState($connection);
    assertSameValue(['applied'=>false,'schemaVersion'=>2,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$conflictPrefix.'fm2_workforce_catalog']],WorkforceCatalogSchemaMigration::apply($connection,$conflictPrefix),'Incompatible workforce table must be rejected before DDL/DML.');
    assertSameValue($conflictBefore,workforceRows($connection,"SELECT * FROM {$conflictPrefix}fm2_workforce_catalog"),'Conflict preflight must preserve incompatible data.');
    assertSameValue($conflictStateBefore,workforceDatabaseState($connection),'Conflict preflight must preserve complete incompatible and v1/v2 schema, data and auto-increment state.');

    $connection->query("CREATE TABLE {$uniqueOnlyPrefix}fm2_workforce_catalog (
        installer_tab_id BIGINT UNSIGNED NOT NULL,
        fio VARCHAR(300) NOT NULL,
        position VARCHAR(300) NOT NULL,
        employment_status VARCHAR(40) NOT NULL,
        employed_from DATE NULL,
        employed_to DATE NULL,
        workforce_source VARCHAR(80) NOT NULL,
        workforce_source_updated_at VARCHAR(40) NOT NULL,
        UNIQUE KEY (installer_tab_id),
        KEY (employment_status,employed_to),
        CHECK (employment_status IN ('employed','dismissed'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$uniqueOnlyPrefix}fm2_workforce_catalog VALUES (2042,'Сентинел Монтажник','Электромеханик','employed','2024-01-01',NULL,'one_c_zup_via_bitrix','2026-08-28T10:00:00+03:00')");
    assertSameValue([
        ['CONSTRAINT_TYPE'=>'UNIQUE','CONSTRAINT_NAME'=>'installer_tab_id'],
    ],workforceRows($connection,"SELECT CONSTRAINT_TYPE,CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$uniqueOnlyPrefix}fm2_workforce_catalog' AND CONSTRAINT_TYPE IN ('PRIMARY KEY','UNIQUE') ORDER BY CONSTRAINT_TYPE,CONSTRAINT_NAME"),'Adversarial fixture must expose UNIQUE identity and no PRIMARY KEY constraint.');
    $uniqueOnlyStateBefore=workforceDatabaseState($connection);
    $uniqueOnlyResult=WorkforceCatalogSchemaMigration::apply($connection,$uniqueOnlyPrefix);
    assertSameValue($uniqueOnlyStateBefore,workforceDatabaseState($connection),'Unique-not-primary conflict must preserve complete schema, constraints, data and auto-increment state.');
    assertSameValue(['applied'=>false,'schemaVersion'=>2,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$uniqueOnlyPrefix.'fm2_workforce_catalog']],$uniqueOnlyResult,'UNIQUE NOT NULL must not substitute for the normative PRIMARY KEY.');
    try {
        WorkforceCatalogSchemaMigration::apply($connection,'invalid-prefix;');
        throw new TestFailure('Invalid workforce prefix must be rejected.');
    } catch (InvalidArgumentException) {
    }

    $workforce=new MariaDbWorkforceCatalog($connection,$prefix);
    $facts=new class($workforce) {
        public function __construct(private readonly object $workforce) {}
        public function actorCanPrepareAssignmentOrder(int $actorId):bool{return $actorId===18;}
        public function getInstallationObjectSnapshot(int $id):array{return ['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null];}
        public function findInstallerSnapshot(int|string $id):?array{return $this->workforce->findInstallerSnapshot($id);}
        public function findEngineerSnapshot(int $id):?array{return $id===73?['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer']:null;}
        public function renderAssignmentOrder(array $input):array{return [['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix']];}
        public function now():string{return '2026-08-26T21:30:00+00:00';}
    };
    $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$facts,$prefix));
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Production workforce lookup must support the approved command.');
    assertSameValue($catalogBefore,workforceRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'Prepare command and delegate must leave current catalog unchanged.');
    $connection->query("UPDATE {$prefix}fm2_workforce_catalog SET fio='Сидоров Сидор Сидорович',position='Уволен',employment_status='dismissed',employed_to='2026-08-28',workforce_source_updated_at='2026-08-28T12:00:00+03:00' WHERE installer_tab_id=1042");
    assertSameValue([[
        'installer_tab_id'=>'1042','fio'=>'Сидоров Сидор Сидорович','position'=>'Уволен','employment_status'=>'dismissed','employed_from'=>'2024-02-01','employed_to'=>'2026-08-28','workforce_source'=>'one_c_zup_via_bitrix','workforce_source_updated_at'=>'2026-08-28T12:00:00+03:00',
    ]],workforceRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'Fixture sync mutation must visibly replace current catalog facts before reload.');
    $connection->close(); unset($process,$facts,$workforce);
    $connection=workforceConnection($database);
    $forbidden=new class{public function __call(string $name,array $arguments):never{throw new LogicException("External fact {$name} must not be read on reload.");}};
    $reloaded=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,$prefix));
    assertSameValue(expectedWorkforceProjection(),$reloaded->getInstallationObjectProcess(4512),'Complete inherited projection must retain the original workforce snapshot after catalog mutation and reconnect.');

    echo "PASS: WORKFORCE-CATALOG-001 production workforce catalog\n";
} finally {
    try{$connection->close();}catch(Throwable){}
    $cleanup=workforceConnection();
    $cleanup->query("DROP DATABASE IF EXISTS `{$database}`");
    $cleanup->close();
}
