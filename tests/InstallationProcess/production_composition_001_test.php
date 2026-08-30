<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\Clock;
use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionInstallationProcessConfig;
use FMonitor2\InstallationProcess\ProductionInstallationProcessFactory;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\SystemClock;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: PRODUCTION-COMPOSITION-001 v0.1.

function compositionConnection(?string $database=null): mysqli
{
    $connection=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local',$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $connection->set_charset('utf8mb4'); return $connection;
}
function compositionRows(mysqli $connection,string $sql):array{return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);}
function compositionArtifactFiles(string $root):array{$files=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));foreach($it as $entry){if($entry->isFile()&&!$entry->isLink())$files[]=str_replace(DIRECTORY_SEPARATOR,'/',substr($entry->getPathname(),strlen($root)+1));}sort($files);return $files;}
function compositionRemoveArtifactRoot(string $root,string $parent):void{$realRoot=realpath($root);$realParent=realpath($parent);if($realRoot===false||$realParent===false||!str_starts_with($realRoot,$realParent.DIRECTORY_SEPARATOR))throw new LogicException('Unsafe composition artifact cleanup.');$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realRoot,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $entry){$entry->isDir()&&!$entry->isLink()?rmdir($entry->getPathname()):unlink($entry->getPathname());}rmdir($realRoot);}
function expectedCompositionProjection(array $pdfArtifact):array
{
    return [
        'installationObjectId'=>4512,'processState'=>'working','actualStartDate'=>'2026-08-28','openedAt'=>'2026-08-28T12:45:00+03:00','openedByUserId'=>18,
        'assignmentOrders'=>[ [
            'version'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
            'artifacts'=>[$pdfArtifact],
        ] ],
        'assignments'=>[['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary']],
        'openTasks'=>[],'installationOpened'=>true,'checklistAvailable'=>true,
        'events'=>[
            ['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual','artifactSha256'=>['order'=>$pdfArtifact['sha256']]]],
            ['type'=>'assignment_order_registered','occurredAt'=>'2026-08-28T12:15:30+03:00','actorId'=>18,'payload'=>['assignmentOrderVersion'=>1,'registrationNumber'=>'12-Р','registrationSource'=>'manual','registrationActorType'=>'user']],
            ['type'=>'installation_opened','occurredAt'=>'2026-08-28T12:45:00+03:00','actorId'=>18,'payload'=>['actualStartDate'=>'2026-08-28','assignmentOrderVersion'=>1,'installerCount'=>1]],
        ],
    ];
}

$artifactParent=dirname(__DIR__,2).'/.test-artifacts';$artifactParentCreated=false;if(!file_exists($artifactParent)){if(!mkdir($artifactParent,0700,true))throw new RuntimeException('Cannot create composition artifact parent.');$artifactParentCreated=true;}$artifactParentInfo=lstat($artifactParent);$compositionEffectiveUid=posix_geteuid();$compositionAccount=posix_getpwuid($compositionEffectiveUid);$compositionNamedAccount=is_array($compositionAccount)?posix_getpwnam((string)$compositionAccount['name']):false;$compositionHome=is_array($compositionAccount)?realpath((string)$compositionAccount['dir']):false;assertSameValue(true,$artifactParentInfo!==false&&($artifactParentInfo['mode']&0170000)===0040000&&!is_link($artifactParent),'Shared composition artifact parent must be a real directory.');assertSameValue($compositionEffectiveUid,$artifactParentInfo['uid'],'Shared composition artifact parent must be current-euid owned.');assertSameValue(0,$artifactParentInfo['mode']&0022,'Shared composition artifact parent must not be group/other writable.');assertSameValue($compositionEffectiveUid,is_array($compositionNamedAccount)?$compositionNamedAccount['uid']:null,'Composition effective account name must resolve to the same uid.');assertSameValue(true,is_string($compositionHome)&&str_starts_with(realpath($artifactParent),$compositionHome.DIRECTORY_SEPARATOR),'Composition artifact parent must resolve below the effective account home.');$artifactRoot=$artifactParent.'/production_composition_001_'.bin2hex(random_bytes(8));mkdir($artifactRoot,0755);chmod($artifactRoot,0755);
$compositionRootInfo=lstat($artifactRoot);assertSameValue(posix_geteuid(),$compositionRootInfo['uid'],'Composition artifact root must be current-user owned.');assertSameValue(0755,$compositionRootInfo['mode']&0777,'Configured composition root may be protected mode 0755 because it has no group/other write bits.');
$configConstructor=(new ReflectionMethod(ProductionInstallationProcessConfig::class,'__construct'))->getParameters();assertSameValue(3,count($configConstructor),'Production config constructor must have exactly three fields.');foreach(['processTablePrefix','legacyTablePrefix','artifactStorageRoot'] as $position=>$expectedName){$parameter=$configConstructor[$position]??null;$type=$parameter?->getType();assertSameValue($expectedName,$parameter?->getName(),"Production config parameter {$position} must have the exact approved name and order.");assertSameValue(true,$type instanceof ReflectionNamedType&&$type->isBuiltin(),"Production config {$expectedName} type must be builtin.");assertSameValue('string',$type instanceof ReflectionNamedType?$type->getName():null,"Production config {$expectedName} must have exact string type.");assertSameValue(false,$parameter?->allowsNull(),"Production config {$expectedName} must be non-nullable.");assertSameValue(false,$parameter?->isDefaultValueAvailable(),"Production config {$expectedName} must be required with no default.");}
$database='t_pc001_'.bin2hex(random_bytes(6)); $token=bin2hex(random_bytes(3)); $processPrefix="pc001_{$token}_"; $legacyPrefix="pc001_legacy_{$token}_";
$admin=compositionConnection();$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");$admin->close();$connection=compositionConnection($database);
try {
    ProductionProcessSchemaMigration::apply($connection,$processPrefix); WorkforceCatalogSchemaMigration::apply($connection,$processPrefix); ProcessUserCapabilitiesSchemaMigration::apply($connection,$processPrefix); ProcessCommandCapabilitiesSchemaMigration::apply($connection,$processPrefix);
    $connection->query("CREATE TABLE `{$legacyPrefix}fm_maintable` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NOT NULL,entrance VARCHAR(80) NOT NULL,regnumber VARCHAR(120) NOT NULL,workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE `{$legacyPrefix}users_roles` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("CREATE TABLE `{$legacyPrefix}users` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO `{$legacyPrefix}fm_maintable` VALUES(4512,'  Москва, ул. Примерная, д. 10  ',' 2 ',' 77-000123 ','2026-10-05 14:30:00','2026-12-20 09:15:00','2026-12-19','2026-11-30','0000-00-00 00:00:00')");
    $connection->query("INSERT INTO `{$legacyPrefix}users_roles` VALUES(5,'ФКР',1),(8,'Строительный контроль',1)");
    $connection->query("INSERT INTO `{$legacyPrefix}users` VALUES(18,'Сидоров Сергей Сергеевич',5,1),(73,'Петров Пётр Петрович',8,1)");
    $connection->query("INSERT INTO `{$processPrefix}fm2_workforce_catalog` VALUES(1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $connection->query("INSERT INTO `{$processPrefix}fm2_process_user_capabilities` VALUES(18,'assignment_order.prepare',NULL),(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(73,'construction_control_engineer','Инженер строительного контроля')");
    $connection->query("INSERT INTO `{$processPrefix}fm2_installation_cases`(legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");

    // Opposite-namespace decoys use identical IDs but facts that must never appear.
    $connection->query("CREATE TABLE `{$processPrefix}fm_maintable` LIKE `{$legacyPrefix}fm_maintable`"); $connection->query("INSERT INTO `{$processPrefix}fm_maintable` VALUES(4512,'WRONG PROCESS-NAMESPACE OBJECT','9','WRONG','2020-01-01','2020-01-02',NULL,NULL,NULL)");
    $connection->query("CREATE TABLE `{$processPrefix}users_roles` LIKE `{$legacyPrefix}users_roles`"); $connection->query("INSERT INTO `{$processPrefix}users_roles` VALUES(5,'Wrong inactive role',0),(8,'Wrong inactive engineer role',0)");
    $connection->query("CREATE TABLE `{$processPrefix}users` LIKE `{$legacyPrefix}users`"); $connection->query("INSERT INTO `{$processPrefix}users` VALUES(18,'WRONG PROCESS-NAMESPACE ACTOR',5,0),(73,'WRONG PROCESS-NAMESPACE ENGINEER',8,0)");
    $connection->query("CREATE TABLE `{$legacyPrefix}fm2_workforce_catalog` LIKE `{$processPrefix}fm2_workforce_catalog`"); $connection->query("INSERT INTO `{$legacyPrefix}fm2_workforce_catalog` VALUES(1042,'WRONG LEGACY-NAMESPACE WORKER','Wrong','dismissed','2020-01-01','2020-01-02','wrong','2020-01-01T00:00:00+03:00')");
    $connection->query("CREATE TABLE `{$legacyPrefix}fm2_process_user_capabilities` LIKE `{$processPrefix}fm2_process_user_capabilities`"); $connection->query("INSERT INTO `{$legacyPrefix}fm2_process_user_capabilities` VALUES(18,'construction_control_engineer','Wrong capability namespace')");
    $externalSql=["SELECT * FROM `{$legacyPrefix}fm_maintable` ORDER BY id","SELECT * FROM `{$legacyPrefix}users` ORDER BY id","SELECT * FROM `{$legacyPrefix}users_roles` ORDER BY id","SELECT * FROM `{$processPrefix}fm2_workforce_catalog` ORDER BY installer_tab_id","SELECT * FROM `{$processPrefix}fm2_process_user_capabilities` ORDER BY user_id,capability","SELECT * FROM `{$processPrefix}fm_maintable` ORDER BY id","SELECT * FROM `{$processPrefix}users` ORDER BY id","SELECT * FROM `{$processPrefix}users_roles` ORDER BY id","SELECT * FROM `{$legacyPrefix}fm2_workforce_catalog` ORDER BY installer_tab_id","SELECT * FROM `{$legacyPrefix}fm2_process_user_capabilities` ORDER BY user_id,capability"];
    $externalBefore=array_map(fn(string $sql):array=>compositionRows($connection,$sql),$externalSql);

    $config=new ProductionInstallationProcessConfig($processPrefix,$legacyPrefix,$artifactRoot);
    $sequenceClock=new class(['2026-08-26T21:30:00+00:00','2026-08-28T12:15:30+03:00','2026-08-28T12:45:00+03:00']) implements Clock {public function __construct(private array $instants){} public function now():string{if($this->instants===[])throw new LogicException('Unexpected clock read.');return array_shift($this->instants);}};
    $process=ProductionInstallationProcessFactory::create($connection,$config,$sequenceClock);
    assertSameValue(InstallationProcess::class,$process::class,'Factory must expose only the ready deep process module.');
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'],$process->prepareAssignmentOrder(4512,[1042],73,18),'Factory composition must prepare with production delegates.');
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'registered','registrationNumber'=>'12-Р','registeredAt'=>'2026-08-28T12:15:30+03:00','registrationActorType'=>'user','registrationActorId'=>18,'registrationSource'=>'manual','externalRegistrationId'=>null,'processState'=>'assignment_order_prepared'],$process->confirmOrderRegistration(4512,1,' 12-Р ','manual',18),'Factory composition must confirm with its distinct capability.');
    assertSameValue(['accepted'=>true,'processState'=>'working','actualStartDate'=>'2026-08-28','openedAt'=>'2026-08-28T12:45:00+03:00','openedByUserId'=>18,'installationOpened'=>true,'checklistAvailable'=>true,'assignmentOrderVersion'=>1],$process->openInstallation(4512,'2026-08-28',18),'Factory composition must open with production Workforce recheck.');
    $preparedProjection=$process->getInstallationObjectProcess(4512);$pdfArtifact=$preparedProjection['assignmentOrders'][0]['artifacts'][0]??null;assertSameValue(true,is_array($pdfArtifact),'Production composition must expose one PDF artifact.');assertSameValue('Распоряжение о закреплении монтажников.pdf',$pdfArtifact['filename'],'Production composition must use the PDF factory filename.');assertSameValue('application/pdf',$pdfArtifact['mediaType'],'Production composition must use the PDF media type.');assertSameValue(true,$pdfArtifact['size']>10000,'Production composition must store non-trivial PDF bytes.');$pdfRelativePath='sha256/'.substr($pdfArtifact['sha256'],0,2).'/'.substr($pdfArtifact['sha256'],2,2).'/'.$pdfArtifact['sha256'];assertSameValue([$pdfRelativePath],compositionArtifactFiles($artifactRoot),'Production composition must use one exact SHA-addressed PDF blob.');
    assertSameValue($externalBefore,array_map(fn(string $sql):array=>compositionRows($connection,$sql),$externalSql),'Production composition must route namespaces exactly and leave all real/decoy external tables unchanged.');

    foreach(["`{$legacyPrefix}fm_maintable`","`{$legacyPrefix}users`","`{$legacyPrefix}users_roles`","`{$processPrefix}fm2_workforce_catalog`","`{$processPrefix}fm2_process_user_capabilities`"] as $table){$connection->query("DELETE FROM {$table}");}
    $connection->close(); unset($process,$sequenceClock); $connection=compositionConnection($database);
    $forbiddenClock=new class implements Clock {public function now():string{throw new LogicException('Clock must not be read during public reload.');}};
    $reloaded=ProductionInstallationProcessFactory::create($connection,$config,$forbiddenClock);
    assertSameValue(expectedCompositionProjection($pdfArtifact),$reloaded->getInstallationObjectProcess(4512),'Fresh factory/connection must hydrate the exact full PDF opened projection after external rows are unavailable.');

    $timezoneBefore=date_default_timezone_get(); $systemNow=(new SystemClock())->now();
    assertSameValue(1,preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/D',$systemNow),'SystemClock must return seconds precision with explicit RFC3339 offset.');
    assertSameValue($systemNow,(new DateTimeImmutable($systemNow))->format('Y-m-d\TH:i:sP'),'SystemClock output must round-trip exactly as RFC3339 seconds.');
    assertSameValue($timezoneBefore,date_default_timezone_get(),'SystemClock must not change the default timezone.');

    foreach(['bad-prefix;'=>'',str_repeat('x',33)=>''] as $badProcess=>$unused){try{ProductionInstallationProcessFactory::create($connection,new ProductionInstallationProcessConfig($badProcess,$legacyPrefix,$artifactRoot),$forbiddenClock);throw new TestFailure('Invalid process prefix must fail closed.');}catch(InvalidArgumentException){}}
    try{ProductionInstallationProcessFactory::create($connection,new ProductionInstallationProcessConfig($processPrefix,'bad-prefix;',$artifactRoot),$forbiddenClock);throw new TestFailure('Invalid legacy prefix must fail closed.');}catch(InvalidArgumentException){}
    $uninitializedConfig=(new ReflectionClass(ProductionInstallationProcessConfig::class))->newInstanceWithoutConstructor();
    try{ProductionInstallationProcessFactory::create($connection,$uninitializedConfig,$forbiddenClock);throw new TestFailure('Uninitialized config fields must fail closed.');}catch(InvalidArgumentException){}
    $closed=compositionConnection($database);$closed->close();try{ProductionInstallationProcessFactory::create($closed,$config,$forbiddenClock);throw new TestFailure('Closed connection charset setup must fail closed.');}catch(RuntimeException $error){assertSameValue('Production installation process initialization failed.',$error->getMessage(),'Initialization failure must not disclose connection or SQL details.');}
    echo "PASS: PRODUCTION-COMPOSITION-001\n";
} finally {try{$connection->close();}catch(Throwable){}$cleanup=compositionConnection();$cleanup->query("DROP DATABASE IF EXISTS `{$database}`");$cleanup->close();if(is_dir($artifactRoot))compositionRemoveArtifactRoot($artifactRoot,$artifactParent);if($artifactParentCreated&&is_dir($artifactParent)&&(new FilesystemIterator($artifactParent))->valid()===false)rmdir($artifactParent);}
