<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;
use FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject;
use FMonitor2\InstallationProcess\MariaDbProcessUserDirectory;
use FMonitor2\InstallationProcess\MariaDbWorkforceCatalog;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: PROCESS-USER-DIRECTORY-001 v0.1.

function userDirectoryConnection(?string $database=null): mysqli
{
    $connection=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local',$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $connection->set_charset('utf8mb4');
    return $connection;
}

function userDirectoryRows(mysqli $connection,string $sql):array{return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);}

function userDirectoryDatabaseState(mysqli $connection):array
{
    $state=[];
    foreach(userDirectoryRows($connection,'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME') as $row){
        $table=(string)$row['TABLE_NAME'];
        $state[$table]=['create'=>userDirectoryRows($connection,"SHOW CREATE TABLE `{$table}`")[0]['Create Table'],'rows'=>userDirectoryRows($connection,"SELECT * FROM `{$table}`")];
    }
    return $state;
}

function expectedUserDirectoryProjection():array
{
    return [
        'installationObjectId'=>4512,'processState'=>'assignment_order_prepared',
        'assignmentOrders'=>[[
            'version'=>1,'status'=>'prepared','registrationNumber'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-18','ptoActDate'=>null],
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'  Инженер строительного контроля  ','active'=>true,'role'=>'construction_control_engineer'],
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

$database='t_pud001_'.bin2hex(random_bytes(6));
$prefix='process_'; $conflictPrefix='conflict_';
$admin=userDirectoryConnection(); $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $admin->close();
$connection=userDirectoryConnection($database);

try{
    ProductionProcessSchemaMigration::apply($connection,$prefix);
    WorkforceCatalogSchemaMigration::apply($connection,$prefix);
    $connection->query("CREATE TABLE fm_maintable (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NOT NULL,entrance VARCHAR(80) NOT NULL,regnumber VARCHAR(120) NOT NULL,workdatestart VARCHAR(40) NULL,workdateendadjusted VARCHAR(40) NULL,plan_finish_date VARCHAR(40) NULL,workdatefinish VARCHAR(40) NULL,ptoactdate VARCHAR(40) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users_roles (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO fm_maintable VALUES (4512,'  Москва, ул. Примерная, д. 10  ',' 2 ',' 77-000123 ','2026-10-05 14:30:00','2026-12-18 09:15:00','2026-12-20','2026-11-30 18:00:00','0000-00-00 00:00:00')");
    $connection->query("INSERT INTO users_roles VALUES (5,'ФКР',1),(8,'Строительный контроль',1)");
    $connection->query("INSERT INTO users VALUES (18,'Сидоров Сергей Сергеевич',5,1),(73,'Петров Пётр Петрович',8,1)");
    $connection->query("INSERT INTO {$prefix}fm2_workforce_catalog VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $connection->query("INSERT INTO {$prefix}fm2_installation_cases (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $beforeV3=userDirectoryDatabaseState($connection);

    assertSameValue(['applied'=>true,'schemaVersion'=>3,'tablesCreated'=>[$prefix.'fm2_process_user_capabilities']],ProcessUserCapabilitiesSchemaMigration::apply($connection,$prefix),'Additive v3 must create only process capabilities.');
    foreach($beforeV3 as $table=>$state){assertSameValue($state,userDirectoryDatabaseState($connection)[$table],"Additive v3 must preserve {$table}.");}
    assertSameValue(['fm_maintable','process_fm2_assignment_orders','process_fm2_installation_cases','process_fm2_order_artifacts','process_fm2_order_installers','process_fm2_process_events','process_fm2_process_tasks','process_fm2_process_user_capabilities','process_fm2_workforce_catalog','users','users_roles'],array_column(userDirectoryRows($connection,'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME'),'TABLE_NAME'),'Fresh v3 must leave exactly prior tables plus one capabilities table and no extras.');
    assertSameValue([],userDirectoryRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities"),'Fresh capability table must be empty.');
    $columns=userDirectoryRows($connection,"SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_process_user_capabilities' ORDER BY ORDINAL_POSITION");
    assertSameValue([
        ['COLUMN_NAME'=>'user_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
        ['COLUMN_NAME'=>'capability','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ['COLUMN_NAME'=>'position_snapshot','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
    ],$columns,'Capability columns must match the exact v3 contract.');
    $table=userDirectoryRows($connection,"SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_process_user_capabilities'")[0];
    assertSameValue('InnoDB',$table['ENGINE'],'Capabilities must use InnoDB.'); assertSameValue(true,str_starts_with((string)$table['TABLE_COLLATION'],'utf8mb4_'),'Capabilities must use utf8mb4.');
    $indexes=userDirectoryRows($connection,"SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COLLATION,':',IGNORED) ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_process_user_capabilities' GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY NON_UNIQUE,INDEX_NAME");
    assertSameValue([['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','COLUMNS'=>'user_id:FULL:A:NO,capability:FULL:A:NO'],['INDEX_NAME'=>'capability','NON_UNIQUE'=>'1','COLUMNS'=>'capability:FULL:A:NO,user_id:FULL:A:NO']],$indexes,'Capabilities must expose exact primary and lookup indexes.');
    $checks=array_map(static function(array $row):string{
        $normalized=str_replace(['`',' '],'',strtolower((string)$row['CHECK_CLAUSE']));
        return str_replace(
            ["or((position_snapshotisnotnullandtrim(position_snapshot)<>''))","or(position_snapshotisnotnullandtrim(position_snapshot)<>'')"],
            "orposition_snapshotisnotnullandtrim(position_snapshot)<>''",
            $normalized,
        );
    },userDirectoryRows($connection,"SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_process_user_capabilities' ORDER BY CHECK_CLAUSE"));
    sort($checks);
    assertSameValue(["capability<>'construction_control_engineer'orposition_snapshotisnotnullandtrim(position_snapshot)<>''","capabilityin('assignment_order.prepare','construction_control_engineer')"],$checks,'Capabilities must expose both exact semantic checks and no extras despite MariaDB removing redundant parentheses.');
    assertSameValue([],userDirectoryRows($connection,"SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_process_user_capabilities' AND REFERENCED_TABLE_NAME IS NOT NULL"),'Capabilities must have no legacy FK.');

    $connection->query("INSERT INTO {$prefix}fm2_process_user_capabilities VALUES (18,'assignment_order.prepare',NULL),(73,'construction_control_engineer','  Инженер строительного контроля  ')");
    $repeatBefore=userDirectoryDatabaseState($connection);
    assertSameValue(['applied'=>false,'schemaVersion'=>3,'tablesCreated'=>[]],ProcessUserCapabilitiesSchemaMigration::apply($connection,$prefix),'Compatible v3 repeat must be a no-op.');
    assertSameValue($repeatBefore,userDirectoryDatabaseState($connection),'Compatible v3 repeat must preserve all schema/data/auto-increment state.');
    $connection->query("CREATE TABLE {$conflictPrefix}fm2_process_user_capabilities (user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$conflictPrefix}fm2_process_user_capabilities VALUES (999)");
    $conflictBefore=userDirectoryDatabaseState($connection);
    $conflictResult=ProcessUserCapabilitiesSchemaMigration::apply($connection,$conflictPrefix);
    assertSameValue($conflictBefore,userDirectoryDatabaseState($connection),'V3 conflict must preserve all schema and data.');
    assertSameValue(['applied'=>false,'schemaVersion'=>3,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$conflictPrefix.'fm2_process_user_capabilities']],$conflictResult,'Incompatible capability table must conflict.');
    try{ProcessUserCapabilitiesSchemaMigration::apply($connection,'invalid-prefix;');throw new TestFailure('Invalid v3 prefix must be rejected.');}catch(InvalidArgumentException){}

    $makeRejectedProcess=static function()use($connection,$prefix):array{
        $legacy=new MariaDbLegacyInstallationObject($connection); $workforce=new MariaDbWorkforceCatalog($connection,$prefix); $directory=new MariaDbProcessUserDirectory($connection,$prefix,'');
        $facts=new class($legacy,$workforce,$directory){
            public int $rendererCalls=0;
            public function __construct(private readonly object $legacy,private readonly object $workforce,private readonly object $directory){}
            public function actorCanPrepareAssignmentOrder(int $id):bool{return $this->directory->actorCanPrepareAssignmentOrder($id);}
            public function getInstallationObjectSnapshot(int $id):array{return $this->legacy->getInstallationObjectSnapshot($id);}
            public function findInstallerSnapshot(int|string $id):?array{return $this->workforce->findInstallerSnapshot($id);}
            public function findEngineerSnapshot(int $id):?array{return $this->directory->findEngineerSnapshot($id);}
            public function renderAssignmentOrder(array $input):array{$this->rendererCalls++;return [];}
            public function now():string{return '2026-08-26T21:30:00+00:00';}
        };
        $base=new MariaDbInstallationProcessEnvironment($connection,$facts,$prefix);
        $auditEnvironment=new class($base){
            public array $events=[]; public array $securityEvents=[];
            public function __construct(private readonly object $base){}
            public function __call(string $name,array $arguments):mixed{return $this->base->{$name}(...$arguments);}
            public function appendEvent(int $id,array $event):void{$this->events[]=['installationObjectId'=>$id,'event'=>$event];}
            public function appendSecurityEvent(int $id,array $event):void{$this->securityEvents[]=['installationObjectId'=>$id,'event'=>$event];}
        };
        return [new InstallationProcess($auditEnvironment),$facts,$auditEnvironment];
    };
    $forbiddenResult=['accepted'=>false,'violations'=>[['code'=>'FORBIDDEN','message'=>'У вас нет права формировать распоряжение.','field'=>null]]];
    $forbiddenAudit=[['installationObjectId'=>4512,'event'=>['type'=>'assignment_order_prepare_rejected','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['reasonCodes'=>['FORBIDDEN'],'installerCount'=>1,'controlEngineerProvided'=>true]]]];
    $engineerResult=['accepted'=>false,'violations'=>[['code'=>'CONTROL_ENGINEER_NOT_ELIGIBLE','message'=>'Выбранный пользователь не является активным инженером строительного контроля.','field'=>'controlEngineerUserId']]];
    $engineerAudit=[['installationObjectId'=>4512,'event'=>['type'=>'assignment_order_prepare_rejected','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['reasonCodes'=>['CONTROL_ENGINEER_NOT_ELIGIBLE'],'installerCount'=>1,'controlEngineerProvided'=>true,'controlEngineerEligible'=>false]]]];

    $actorScenarios=[
        'inactive actor user'=>["UPDATE users SET status=0 WHERE id=18","UPDATE users SET status=1 WHERE id=18"],
        'inactive actor role'=>["UPDATE users_roles SET status=0 WHERE id=5","UPDATE users_roles SET status=1 WHERE id=5"],
        'missing prepare capability despite FKR role name and engineer-only capability'=>["DELETE FROM {$prefix}fm2_process_user_capabilities WHERE user_id=18;INSERT INTO {$prefix}fm2_process_user_capabilities VALUES (18,'construction_control_engineer','Инженер строительного контроля')","DELETE FROM {$prefix}fm2_process_user_capabilities WHERE user_id=18;INSERT INTO {$prefix}fm2_process_user_capabilities VALUES (18,'assignment_order.prepare',NULL)"],
    ];
    foreach($actorScenarios as $name=>[$mutate,$restore]){
        foreach(explode(';',$mutate) as $sql){$connection->query($sql);} $rejectedStateBefore=userDirectoryDatabaseState($connection); [$rejected,$rejectedFacts,$audit]=$makeRejectedProcess();
        assertSameValue($forbiddenResult,$rejected->prepareAssignmentOrder(4512,[1042],73,18),"{$name} must be FORBIDDEN.");
        assertSameValue($forbiddenAudit,$audit->securityEvents,"{$name} must retain inherited security audit."); assertSameValue([],$audit->events,"{$name} must not append process audit."); assertSameValue(0,$rejectedFacts->rendererCalls,"{$name} must not call renderer."); assertSameValue('0',userDirectoryRows($connection,"SELECT COUNT(*) AS amount FROM {$prefix}fm2_assignment_orders")[0]['amount'],"{$name} must not persist a version.");
        assertSameValue($rejectedStateBefore,userDirectoryDatabaseState($connection),"{$name} must preserve complete DB schema, data and auto-increment state.");
        foreach(explode(';',$restore) as $sql){$connection->query($sql);}
    }

    $engineerScenarios=[
        'inactive engineer user'=>["UPDATE users SET status=0 WHERE id=73","UPDATE users SET status=1 WHERE id=73"],
        'inactive engineer role'=>["UPDATE users_roles SET status=0 WHERE id=8","UPDATE users_roles SET status=1 WHERE id=8"],
        'missing engineer capability despite exact-suggesting role and prepare capability'=>["UPDATE users_roles SET name='construction_control_engineer' WHERE id=8;DELETE FROM {$prefix}fm2_process_user_capabilities WHERE user_id=73;INSERT INTO {$prefix}fm2_process_user_capabilities VALUES (73,'assignment_order.prepare',NULL)","DELETE FROM {$prefix}fm2_process_user_capabilities WHERE user_id=73;INSERT INTO {$prefix}fm2_process_user_capabilities VALUES (73,'construction_control_engineer','  Инженер строительного контроля  ');UPDATE users_roles SET name='Строительный контроль' WHERE id=8"],
        'corrupt engineer capability with NULL position despite active user and role'=>["SET SESSION check_constraint_checks=OFF;UPDATE {$prefix}fm2_process_user_capabilities SET position_snapshot=NULL WHERE user_id=73 AND capability='construction_control_engineer';SET SESSION check_constraint_checks=ON","UPDATE {$prefix}fm2_process_user_capabilities SET position_snapshot='  Инженер строительного контроля  ' WHERE user_id=73 AND capability='construction_control_engineer'"],
        'corrupt engineer capability with whitespace-only position despite active user and role'=>["SET SESSION check_constraint_checks=OFF;UPDATE {$prefix}fm2_process_user_capabilities SET position_snapshot='   ' WHERE user_id=73 AND capability='construction_control_engineer';SET SESSION check_constraint_checks=ON","UPDATE {$prefix}fm2_process_user_capabilities SET position_snapshot='  Инженер строительного контроля  ' WHERE user_id=73 AND capability='construction_control_engineer'"],
    ];
    foreach($engineerScenarios as $name=>[$mutate,$restore]){
        foreach(explode(';',$mutate) as $sql){$connection->query($sql);} $rejectedStateBefore=userDirectoryDatabaseState($connection); [$rejected,$rejectedFacts,$audit]=$makeRejectedProcess();
        assertSameValue($engineerResult,$rejected->prepareAssignmentOrder(4512,[1042],73,18),"{$name} must reject engineer eligibility.");
        assertSameValue($engineerAudit,$audit->events,"{$name} must retain inherited process audit."); assertSameValue([],$audit->securityEvents,"{$name} must not append security audit."); assertSameValue(0,$rejectedFacts->rendererCalls,"{$name} must not call renderer."); assertSameValue('0',userDirectoryRows($connection,"SELECT COUNT(*) AS amount FROM {$prefix}fm2_assignment_orders")[0]['amount'],"{$name} must not persist a version.");
        assertSameValue($rejectedStateBefore,userDirectoryDatabaseState($connection),"{$name} must preserve complete DB schema, data and auto-increment state.");
        foreach(explode(';',$restore) as $sql){$connection->query($sql);}
    }

    $legacyBefore=userDirectoryRows($connection,'SELECT * FROM fm_maintable'); $usersBefore=userDirectoryRows($connection,'SELECT * FROM users ORDER BY id'); $rolesBefore=userDirectoryRows($connection,'SELECT * FROM users_roles ORDER BY id'); $capsBefore=userDirectoryRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities ORDER BY user_id");
    $legacy=new MariaDbLegacyInstallationObject($connection); $workforce=new MariaDbWorkforceCatalog($connection,$prefix); $directory=new MariaDbProcessUserDirectory($connection,$prefix,'');
    $facts=new class($legacy,$workforce,$directory){
        public function __construct(private readonly object $legacy,private readonly object $workforce,private readonly object $directory){}
        public function actorCanPrepareAssignmentOrder(int $id):bool{return $this->directory->actorCanPrepareAssignmentOrder($id);}
        public function getInstallationObjectSnapshot(int $id):array{return $this->legacy->getInstallationObjectSnapshot($id);}
        public function findInstallerSnapshot(int|string $id):?array{return $this->workforce->findInstallerSnapshot($id);}
        public function findEngineerSnapshot(int $id):?array{return $this->directory->findEngineerSnapshot($id);}
        public function renderAssignmentOrder(array $input):array{return [['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix']];}
        public function now():string{return '2026-08-26T21:30:00+00:00';}
    };
    $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$facts,$prefix));
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Composite production directory must authorize actor and resolve engineer.');
    assertSameValue($legacyBefore,userDirectoryRows($connection,'SELECT * FROM fm_maintable'),'Command must not mutate legacy object.'); assertSameValue($usersBefore,userDirectoryRows($connection,'SELECT * FROM users ORDER BY id'),'Directory must not mutate users.'); assertSameValue($rolesBefore,userDirectoryRows($connection,'SELECT * FROM users_roles ORDER BY id'),'Directory must not mutate roles.'); assertSameValue($capsBefore,userDirectoryRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities ORDER BY user_id"),'Directory must not mutate capabilities.');
    $connection->query("UPDATE users SET name='Изменённое ФИО',status=0 WHERE id=73"); $connection->query("UPDATE users_roles SET status=0 WHERE id=8"); $connection->query("DELETE FROM {$prefix}fm2_process_user_capabilities WHERE user_id=73");
    assertSameValue([['id'=>'73','name'=>'Изменённое ФИО','role_id'=>'8','status'=>'0']],userDirectoryRows($connection,'SELECT * FROM users WHERE id=73'),'Current engineer mutation must take effect.');
    assertSameValue([['id'=>'8','name'=>'Строительный контроль','status'=>'0']],userDirectoryRows($connection,'SELECT * FROM users_roles WHERE id=8'),'Current engineer role deactivation must take effect.');
    assertSameValue([],userDirectoryRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities WHERE user_id=73"),'Current engineer capability deletion must take effect.');
    $connection->close(); unset($process,$facts,$legacy,$workforce,$directory); $connection=userDirectoryConnection($database);
    $forbidden=new class{public function __call(string $name,array $arguments):never{throw new LogicException("External {$name} must not be read on reload.");}};
    $reloaded=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,$prefix));
    assertSameValue(expectedUserDirectoryProjection(),$reloaded->getInstallationObjectProcess(4512),'Complete persisted projection must retain original engineer after current user/capability mutation.');

    echo "PASS: PROCESS-USER-DIRECTORY-001 production user directory\n";
}finally{try{$connection->close();}catch(Throwable){} $cleanup=userDirectoryConnection(); $cleanup->query("DROP DATABASE IF EXISTS `{$database}`"); $cleanup->close();}
