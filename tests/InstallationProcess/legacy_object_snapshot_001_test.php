<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;
use FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;

// Specification: LEGACY-OBJECT-SNAPSHOT-001 v0.2, examples A and B.

function legacySnapshotConnection(?string $database = null): mysqli
{
    $connection = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $connection->set_charset('utf8mb4');
    return $connection;
}

function legacySnapshotRows(mysqli $connection, string $sql): array
{
    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function expectedLegacyProjection(array $snapshot): array
{
    return [
        'installationObjectId'=>4512,'processState'=>'assignment_order_prepared',
        'assignmentOrders'=>[[
            'version'=>1,'status'=>'prepared','registrationNumber'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>$snapshot,
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
            'artifacts'=>[
                ['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],
                ['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
            ],
        ]],
        'assignments'=>[
            ['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],
            ['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary'],
        ],
        'openTasks'=>[],'installationOpened'=>false,'checklistAvailable'=>false,
        'events'=>[['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>[
            'assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual',
            'artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
        ]]],
    ];
}

$examples = [
    'A'=>[
        'adjusted'=>'2026-12-18 09:15:00','plan'=>'2026-12-20','actual'=>'2026-11-30 18:00:00','pto'=>'0000-00-00 00:00:00',
        'snapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-18','ptoActDate'=>null],
    ],
    'B'=>[
        'adjusted'=>'0000-00-00 00:00:00','plan'=>'2027-01-09 23:59:59','actual'=>'2026-12-01 12:00:00','pto'=>' 000000 ',
        'snapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2027-01-09','ptoActDate'=>null],
    ],
];

foreach ($examples as $exampleName=>$example) {
    $database='t_los001_'.strtolower($exampleName).'_'.bin2hex(random_bytes(6));
    $admin=legacySnapshotConnection();
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");
    $admin->close();
    $connection=legacySnapshotConnection($database);
    try {
        ProductionProcessSchemaMigration::apply($connection,'process_');
        $connection->query("CREATE TABLE fm_maintable (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NOT NULL,entrance VARCHAR(80) NOT NULL,regnumber VARCHAR(120) NOT NULL,workdatestart VARCHAR(40) NULL,workdateendadjusted VARCHAR(40) NULL,plan_finish_date VARCHAR(40) NULL,workdatefinish VARCHAR(40) NULL,ptoactdate VARCHAR(40) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $statement=$connection->prepare('INSERT INTO fm_maintable VALUES (4512,?,?,?,?,?,?,?,?)');
        $address='  Москва, ул. Примерная, д. 10  '; $entrance=' 2 '; $registration=' 77-000123 '; $start='2026-10-05 14:30:00';
        $statement->bind_param('ssssssss',$address,$entrance,$registration,$start,$example['adjusted'],$example['plan'],$example['actual'],$example['pto']);
        $statement->execute();
        $connection->query("INSERT INTO process_fm2_installation_cases (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
        $legacyBefore=legacySnapshotRows($connection,'SELECT * FROM fm_maintable');

        $legacyObject=new MariaDbLegacyInstallationObject($connection);
        $facts=new class($legacyObject) {
            public function __construct(private readonly object $legacyObject) {}
            public function actorCanPrepareAssignmentOrder(int $actorId): bool { return $actorId===18; }
            public function getInstallationObjectSnapshot(int $id): array { return $this->legacyObject->getInstallationObjectSnapshot($id); }
            public function findInstallerSnapshot(int|string $id): ?array { return $id===1042 ? ['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00'] : null; }
            public function findEngineerSnapshot(int $id): ?array { return $id===73 ? ['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'] : null; }
            public function renderAssignmentOrder(array $input): array { return [
                ['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],
                ['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix'],
            ]; }
            public function now(): string { return '2026-08-26T21:30:00+00:00'; }
        };
        $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$facts,'process_'));
        assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),"Example {$exampleName} must prepare through the public seam.");
        assertSameValue($legacyBefore,legacySnapshotRows($connection,'SELECT * FROM fm_maintable'),"Example {$exampleName} must not mutate legacy facts.");
        $connection->query('DELETE FROM fm_maintable');
        $connection->close();
        unset($process,$facts,$legacyObject);
        $connection=legacySnapshotConnection($database);
        $forbidden=new class { public function __call(string $name,array $arguments): never { throw new LogicException("External fact {$name} must not be read during reload."); } };
        $reloaded=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,'process_'));
        assertSameValue(expectedLegacyProjection($example['snapshot']),$reloaded->getInstallationObjectProcess(4512),"Example {$exampleName} complete inherited projection must survive reload without legacy.");
    } finally {
        try { $connection->close(); } catch (Throwable) {}
        $cleanup=legacySnapshotConnection();
        $cleanup->query("DROP DATABASE IF EXISTS `{$database}`");
        $cleanup->close();
    }
}

echo "PASS: LEGACY-OBJECT-SNAPSHOT-001 production legacy snapshot\n";
