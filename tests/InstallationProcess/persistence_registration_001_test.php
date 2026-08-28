<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: PERSISTENCE-REGISTRATION-001 v0.2.

function persistenceRegistrationConnection(?string $database=null):mysqli
{
    $connection=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local',$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $connection->set_charset('utf8mb4'); return $connection;
}
function persistenceRegistrationRows(mysqli $connection,string $sql):array{return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);}

function expectedPersistedRegistration():array
{
    return [
        'installationObjectId'=>4512,'processState'=>'assignment_order_prepared',
        'assignmentOrders'=>[[
            'version'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,
            'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
            'artifacts'=>[['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']],
        ]],
        'assignments'=>[['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary']],
        'openTasks'=>[],'installationOpened'=>false,'checklistAvailable'=>false,
        'events'=>[
            ['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual','artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']]],
            ['type'=>'assignment_order_registered','occurredAt'=>'2026-08-28T12:15:30+03:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'registrationNumber'=>'12-Р','registrationSource'=>'manual','registrationActorType'=>'user']],
        ],
    ];
}

$database='t_pr001_'.bin2hex(random_bytes(6)); $prefix='process_';
$admin=persistenceRegistrationConnection(); $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $admin->close();
$connection=persistenceRegistrationConnection($database);
try{
    ProductionProcessSchemaMigration::apply($connection,$prefix); WorkforceCatalogSchemaMigration::apply($connection,$prefix); ProcessUserCapabilitiesSchemaMigration::apply($connection,$prefix);
    $connection->query("CREATE TABLE fm_maintable (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,marker VARCHAR(80) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users_roles (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE users (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO fm_maintable VALUES (4512,'read-only legacy sentinel')"); $connection->query("INSERT INTO users_roles VALUES(5,'ФКР',1),(8,'Строительный контроль',1)"); $connection->query("INSERT INTO users VALUES(18,'Сидоров Сергей Сергеевич',5,1),(73,'Петров Пётр Петрович',8,1)");
    $connection->query("INSERT INTO {$prefix}fm2_workforce_catalog VALUES(1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $connection->query("INSERT INTO {$prefix}fm2_process_user_capabilities VALUES(18,'assignment_order.prepare',NULL),(73,'construction_control_engineer','Инженер строительного контроля')");
    $connection->query("INSERT INTO {$prefix}fm2_installation_cases(legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $externalBefore=['legacy'=>persistenceRegistrationRows($connection,'SELECT * FROM fm_maintable'),'users'=>persistenceRegistrationRows($connection,'SELECT * FROM users ORDER BY id'),'roles'=>persistenceRegistrationRows($connection,'SELECT * FROM users_roles ORDER BY id'),'workforce'=>persistenceRegistrationRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'capabilities'=>persistenceRegistrationRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities ORDER BY user_id")];

    $facts=new class{
        public string $now='2026-08-26T21:30:00+00:00';
        public function actorCanPrepareAssignmentOrder(int $id):bool{return $id===18;}
        public function actorCanConfirmOrderRegistration(int $id):bool{return $id===18;}
        public function getInstallationObjectSnapshot(int $id):array{return ['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null];}
        public function findInstallerSnapshot(int|string $id):?array{return $id===1042?['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']:null;}
        public function findEngineerSnapshot(int $id):?array{return $id===73?['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer']:null;}
        public function renderAssignmentOrder(array $input):array{return [['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix']];}
        public function now():string{return $this->now;}
    };
    $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$facts,$prefix));
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Production persistence must establish prepared version through public seam.');
    $facts->now='2026-08-28T12:15:30+03:00';
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'processState'=>'assignment_order_prepared'],$process->confirmOrderRegistration(4512,1,' 12-Р ','manual',18),'Production persistence must update exact existing version.');
    assertSameValue($externalBefore,['legacy'=>persistenceRegistrationRows($connection,'SELECT * FROM fm_maintable'),'users'=>persistenceRegistrationRows($connection,'SELECT * FROM users ORDER BY id'),'roles'=>persistenceRegistrationRows($connection,'SELECT * FROM users_roles ORDER BY id'),'workforce'=>persistenceRegistrationRows($connection,"SELECT * FROM {$prefix}fm2_workforce_catalog"),'capabilities'=>persistenceRegistrationRows($connection,"SELECT * FROM {$prefix}fm2_process_user_capabilities ORDER BY user_id")],'Prepare and registration must not mutate external source/configuration tables.');
    $connection->close(); unset($process,$facts); $connection=persistenceRegistrationConnection($database);
    $forbidden=new class{public function __call(string $name,array $arguments):never{throw new LogicException("External {$name} must not be read on reload.");}};
    $reloaded=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,$prefix));
    assertSameValue(expectedPersistedRegistration(),$reloaded->getInstallationObjectProcess(4512),'New connection must hydrate exact full registered projection without external reads.');
    echo "PASS: PERSISTENCE-REGISTRATION-001 MariaDB registration durability\n";
}finally{try{$connection->close();}catch(Throwable){} $cleanup=persistenceRegistrationConnection();$cleanup->query("DROP DATABASE IF EXISTS `{$database}`");$cleanup->close();}
