<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: OPEN-INSTALLATION-001 v0.2, registered composition integrity rejection.

$environment=new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18); $environment->allowRegistrationConfirmationBy(18); $environment->allowOpeningBy(18); $environment->allowSecurityAuditReadBy(99);
$environment->setNow('2026-08-26T21:30:00+00:00');
$environment->seedInstallationObjectProcess(4512,['installationObjectId'=>4512,'processState'=>'needs_assignment_order','actualStartDate'=>null,'openedAt'=>null,'openedByUserId'=>null,'assignmentOrders'=>[],'assignments'=>[],'openTasks'=>[['type'=>'prepare_assignment_order','assigneeRole'=>'fkr']],'installationOpened'=>false,'checklistAvailable'=>false,'events'=>[]]);
$environment->seedInstallationObjectSnapshot(4512,['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null]);
$environment->seedInstallerSnapshot(1042,['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']);
$environment->seedEngineerSnapshot(73,['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer']);
$environment->setRenderedArtifacts([['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix']]);
$process=new InstallationProcess($environment);
assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Integrity fixture must prepare publicly.');
$environment->setNow('2026-08-28T12:15:30+03:00');
assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'processState'=>'assignment_order_prepared'],$process->confirmOrderRegistration(4512,1,' 12-Р ','manual',18),'Integrity fixture must register publicly.');

$environment->corruptCurrentAssignmentOrderByClearingInstallers(4512);
$environment->setNow('2026-08-28T12:45:00+03:00');
$environment->forbidCurrentInstallerSnapshotReads(); $environment->forbidRendering(); $environment->forbidInstallationObjectSnapshotReads(); $environment->forbidInstallerSnapshotReads(); $environment->forbidEngineerSnapshotReads();

assertSameValue(['accepted'=>false,'violations'=>[['code'=>'REGISTERED_ORDER_COMPOSITION_INVALID','message'=>'Зарегистрированное распоряжение не содержит ни одного монтажника. Открытие работ невозможно.','field'=>null]]],$process->openInstallation(4512,'2026-08-28',18),'Registered order without installers must fail closed before date and Workforce checks.');
assertSameValue([],$environment->getCurrentInstallerSnapshotReads(),'Composition rejection must not query current Workforce.');

assertSameValue([
    'installationObjectId'=>4512,'processState'=>'assignment_order_prepared','actualStartDate'=>null,'openedAt'=>null,'openedByUserId'=>null,
    'assignmentOrders'=>[[
        'version'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
        'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],
        'installers'=>[],
        'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
        'artifacts'=>[['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']],
    ]],
    'assignments'=>[['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary']],
    'openTasks'=>[],'installationOpened'=>false,'checklistAvailable'=>false,
    'events'=>[
        ['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual','artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7']]],
        ['type'=>'assignment_order_registered','occurredAt'=>'2026-08-28T12:15:30+03:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'registrationNumber'=>'12-Р','registrationSource'=>'manual','registrationActorType'=>'user']],
        ['type'=>'installation_open_rejected','occurredAt'=>'2026-08-28T12:45:00+03:00','actorId'=>18,'payload'=>['reasonCodes'=>['REGISTERED_ORDER_COMPOSITION_INVALID'],'assignmentOrderVersion'=>1,'installerCount'=>0]],
    ],
],$process->getInstallationObjectProcess(4512),'Integrity rejection must append only exact process audit and leave registered/opening facts and gates unchanged.');
assertSameValue([],$process->getSecurityAudit(4512,99),'Authorized integrity rejection must not create a security event.');

echo "PASS: OPEN-INSTALLATION-001 registered composition integrity rejection\n";
