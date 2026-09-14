<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

function lidbQ(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }
function lidbRun(array $env): array {
    $pipes=[];$p=proc_open([PHP_BINARY,'bin/yii','legacy-import/run','--interactive=0'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,dirname(__DIR__,2),$env);
    if(!is_resource($p))throw new RuntimeException('SETUP_FAILURE');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($p),$out,$err];
}

$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$adminUser=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$adminPassword=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$token=bin2hex(random_bytes(5));$sourceName='t_lis_'.$token;$targetName='t_lit_'.$token;$reader='r_'.$token;$readerPassword='SOURCE_SECRET_CANARY_'.$token;$config=tempnam(sys_get_temp_dir(),'fm2-legacy-source-');
$admin=new mysqli($host,$adminUser,$adminPassword,'',$port);$source=$target=null;
try{
    foreach([$sourceName,$targetName]as$name)$admin->query('CREATE DATABASE '.lidbQ($name).' CHARACTER SET utf8mb4');
    $source=new mysqli($host,$adminUser,$adminPassword,$sourceName,$port);$target=new mysqli($host,$adminUser,$adminPassword,$targetName,$port);$source->set_charset('utf8mb4');$target->set_charset('utf8mb4');
    $source->query("CREATE TABLE fm_maintable(id BIGINT PRIMARY KEY,ordadr_address VARCHAR(100),entrance VARCHAR(20),regnumber VARCHAR(40),workdatestart DATETIME NULL,workdatestartadjusted DATETIME NULL,workdateendadjusted DATETIME NULL,plan_finish_date DATETIME NULL,workdatefinish DATETIME NULL,ptoactdate DATETIME NULL,responsstroicontrol BIGINT NULL,factworkstartdate DATETIME NULL,object_status VARCHAR(40),fact_percent INT,workstarted INT,floors VARCHAR(40),weight VARCHAR(40),speed VARCHAR(40),pittype VARCHAR(40),pitmaterial VARCHAR(40),paired VARCHAR(40)) ENGINE=InnoDB");
    $source->query("CREATE TABLE fm_install_checklists_values_log(value_id BIGINT,ctime DATETIME)");$source->query("CREATE TABLE fm_install_checklists_values(id BIGINT PRIMARY KEY,value_id BIGINT)");$source->query("CREATE TABLE fm_install_checklists_values_installators_log(checklist_value_id BIGINT,ctime DATETIME)");
    $source->query("CREATE TABLE fm_install_checklist_parts(id INT PRIMARY KEY,name VARCHAR(100),rang INT)");$source->query("CREATE TABLE fm_install_checklist(id INT PRIMARY KEY,part_id INT,name VARCHAR(100),share INT,rang INT,needphoto INT)");
    for($i=1;$i<=8;$i++)$source->query("INSERT INTO fm_install_checklist_parts VALUES($i,'Part $i',$i)");
    for($i=1;$i<=42;$i++){ $share=$i===42?15:($i===1?45:1);$part=min(8,(int)ceil($i/6));$source->query("INSERT INTO fm_install_checklist VALUES($i,$part,'Item $i',$share,$i,0)"); }
    $source->query("INSERT INTO fm_maintable VALUES(501,'Address','1','REG-501','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(508,'Address 508','8','REG-508','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(502,'Opened','2','REG-502','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,'2026-09-01','',1,1,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(503,'Progress','3','REG-503','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',50,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(504,'Started flag','4','REG-504','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,1,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(505,'PTO','5','REG-505','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,'2026-09-01',77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(506,'Finished','6','REG-506','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'259',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(507,'','7','REG-507','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(509,'Too early','9','REG-509','2026-09-30',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(510,'Missing start','10','REG-510',NULL,NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $source->query("INSERT INTO fm_maintable VALUES(511,'Missing finish','11','REG-511','2026-10-01',NULL,NULL,NULL,NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0')");
    $admin->query("CREATE USER ".lidbQ($reader)."@'%' IDENTIFIED BY '".$admin->real_escape_string($readerPassword)."'");$admin->query('GRANT SELECT ON '.lidbQ($sourceName).'.* TO '.lidbQ($reader)."@'%'");
    $migration=CanonicalMigrationApplication::run($target,'fm2_',ProductionPilotMigrationCatalogue::migrations());assertSameValue(0,$migration['exitCode'],'target migrations');
    file_put_contents($config,"FMONITOR_SOURCE_HOST=$host\nFMONITOR_SOURCE_PORT=$port\nFMONITOR_SOURCE_NAME=$sourceName\nFMONITOR_SOURCE_USER=$reader\nFMONITOR_SOURCE_PASSWORD='$readerPassword'\nFMONITOR_MIGRATION_CUTOFF=2026-09-14 23:59:59\n");chmod($config,0600);
    $env=array_replace(getenv(),['FMONITOR_LEGACY_SOURCE_CONFIG'=>$config,'FMONITOR_DB_HOST'=>$host,'FMONITOR_DB_PORT'=>(string)$port,'FMONITOR_DB_NAME'=>$targetName,'FMONITOR_DB_USER'=>$adminUser,'FMONITOR_DB_PASSWORD'=>$adminPassword,'FMONITOR_PROCESS_TABLE_PREFIX'=>'fm2_','FMONITOR_LEGACY_TABLE_PREFIX'=>'fm2_']);
    $beforeSource=$source->query("SELECT TABLE_NAME,TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$source->real_escape_string($sourceName)."' ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
    $target->query("CREATE TRIGGER lidb_fault BEFORE INSERT ON fm2_fm2_pilot_object_details FOR EACH ROW BEGIN IF NEW.object_id=508 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'; END IF; END");
    $failed=lidbRun($env);assertSameValue(true,$failed[0]!==0,'mid-import fault rejected');foreach(['fm2_fm2_installation_cases','fm2_fm2_pilot_object_details','fm2_fm2_checklist_template_snapshots','fm2_fm2_checklist_template_associations','fm2_fm_maintable']as$table)assertSameValue(0,(int)$target->query("SELECT COUNT(*) FROM `$table`")->fetch_column(),'fault publishes no partial '.$table);$target->query('DROP TRIGGER lidb_fault');
    $one=lidbRun($env);assertSameValue([0,''],[$one[0],$one[2]],'native import succeeds');$result=json_decode(trim($one[1]),true,512,JSON_THROW_ON_ERROR);assertSameValue(['LEGACY_IMPORT_COMPLETED','2026-09-14 23:59:59',2,2,0,2,2],[$result['result']??null,$result['cutoff']??null,$result['eligible']??null,$result['imported']??null,$result['alreadyPresent']??null,$result['details']??null,$result['templateAssociations']??null],'stable exact result and counts');
    assertSameValue([501,508],array_map('intval',array_column($target->query('SELECT legacy_installation_object_id FROM fm2_fm2_installation_cases ORDER BY legacy_installation_object_id')->fetch_all(MYSQLI_ASSOC),'legacy_installation_object_id')),'only native candidates imported');
    assertSameValue([2,1,2],[(int)$target->query('SELECT COUNT(*) FROM fm2_fm2_pilot_object_details')->fetch_column(),(int)$target->query('SELECT COUNT(*) FROM fm2_fm2_checklist_template_snapshots')->fetch_column(),(int)$target->query('SELECT COUNT(*) FROM fm2_fm2_checklist_template_associations')->fetch_column()],'details template association');
    assertSameValue(['2026-09-14 23:59:59'],array_column($target->query("SELECT DISTINCT source_cutoff_at FROM fm2_fm2_migration_classification_provenance")->fetch_all(MYSQLI_ASSOC),'source_cutoff_at'),'one cutoff across imported provenance');
    $snapshot=$target->query("SELECT legacy_installation_object_id,process_state FROM fm2_fm2_installation_cases ORDER BY id")->fetch_all(MYSQLI_ASSOC);$two=lidbRun($env);assertSameValue(0,$two[0],'repeat succeeds');$repeat=json_decode(trim($two[1]),true,512,JSON_THROW_ON_ERROR);assertSameValue([0,2,0,0],[$repeat['imported']??null,$repeat['alreadyPresent']??null,$repeat['details']??null,$repeat['templateAssociations']??null],'repeat exact counts');assertSameValue($snapshot,$target->query("SELECT legacy_installation_object_id,process_state FROM fm2_fm2_installation_cases ORDER BY id")->fetch_all(MYSQLI_ASSOC),'repeat preserves facts');
    $target->query("UPDATE fm2_fm_maintable SET ordadr_address='CONFLICT' WHERE id=501");$conflictBefore=$target->query('SELECT * FROM fm2_fm2_installation_cases ORDER BY id')->fetch_all(MYSQLI_ASSOC);$conflict=lidbRun($env);assertSameValue(true,$conflict[0]!==0,'conflicting immutable mirror rejected');assertSameValue($conflictBefore,$target->query('SELECT * FROM fm2_fm2_installation_cases ORDER BY id')->fetch_all(MYSQLI_ASSOC),'conflict adds no case facts');
    assertSameValue($beforeSource,$source->query("SELECT TABLE_NAME,TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$source->real_escape_string($sourceName)."' ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC),'source unchanged');
    assertSameValue(false,str_contains($one[1].$one[2],$readerPassword),'source secret redacted');
    echo "PASS: YII2-LOCAL-DATA-BOOTSTRAP-001 native legacy import\n";
}finally{
    if($source instanceof mysqli)$source->close();if($target instanceof mysqli)$target->close();@unlink($config);$admin->query('DROP DATABASE IF EXISTS '.lidbQ($sourceName));$admin->query('DROP DATABASE IF EXISTS '.lidbQ($targetName));$admin->query('DROP USER IF EXISTS '.lidbQ($reader)."@'%'");$admin->close();
}
