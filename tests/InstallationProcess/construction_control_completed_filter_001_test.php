<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
require dirname(__DIR__,2).'/app/PilotHttp/ConstructionControlView.php';

use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\MariaDbConstructionControlQueue;
use FMonitor2\PilotHttp\ProductionConstructionControlRenderer;

function cccfDb(?string $database=null):mysqli{$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$database??'',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');return$db;}
function cccfQ(string $name):string{return'`'.str_replace('`','``',$name).'`';}
$token=substr(hash('sha256',__FILE__.getmypid().hrtime(true)),0,10);$database='t_cccf_'.$token;$prefix='cc'.substr($token,0,6).'_';$admin=cccfDb();$db=null;$htmlFile=dirname(__DIR__,2).'/.test-artifacts/cccf-'.$token.'.html';$failure=null;
try{
    $admin->query('CREATE DATABASE '.cccfQ($database).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');$db=cccfDb($database);$q=fn(string$name):string=>cccfQ($prefix.$name);
    $db->query('CREATE TABLE '.$q('fm2_installation_cases').'(id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT NOT NULL,process_state VARCHAR(80) NOT NULL) ENGINE=InnoDB');
    $db->query('CREATE TABLE '.$q('fm_maintable').'(id BIGINT PRIMARY KEY,ordadr_address VARCHAR(255),entrance VARCHAR(40),regnumber VARCHAR(80),ptoactdate DATE NULL,responsstroicontrol BIGINT NULL) ENGINE=InnoDB');
    $db->query('CREATE TABLE '.$q('fm2_process_events').'(id BIGINT PRIMARY KEY,installation_case_id BIGINT,event_type VARCHAR(80),payload_json JSON) ENGINE=InnoDB');
    $db->query('CREATE TABLE '.$q('fm2_pilot_users').'(user_id BIGINT PRIMARY KEY,full_name VARCHAR(255)) ENGINE=InnoDB');
    $db->query('CREATE TABLE '.$q('fm2_pilot_completion_facts').'(id BIGINT PRIMARY KEY,installation_case_id BIGINT NOT NULL,fact_type VARCHAR(40) NOT NULL,fact_date DATE NOT NULL,details VARCHAR(500) NOT NULL) ENGINE=InnoDB');
    $db->query('INSERT INTO '.$q('fm2_installation_cases')." VALUES(71,966,'working'),(72,967,'working')");
    $db->query('INSERT INTO '.$q('fm_maintable')." VALUES(966,'Завершённый объект','1','77-966',NULL,73),(967,'Монтаж выполнен на 85%','2','77-967',NULL,73)");
    $db->query('INSERT INTO '.$q('fm2_pilot_users')." VALUES(73,'Инженер стройконтроля')");
    $db->query('INSERT INTO '.$q('fm2_pilot_completion_facts')." VALUES(1,71,'pto_act','2026-09-07',''),(2,71,'declaration','2026-09-07','ЕАЭС fixture'),(3,72,'pto_act','2026-09-07','')");
    $before=$db->query('SELECT * FROM '.$q('fm2_pilot_completion_facts').' ORDER BY id')->fetch_all(MYSQLI_ASSOC);$objects=(new MariaDbConstructionControlQueue($db,$prefix,$prefix))->read(1);assertSameValue(2,count($objects),'public queue returns both working-state records for client toggle');$byId=[];foreach($objects as$object)$byId[$object['id']]=$object;assertSameValue(true,$byId[966]['completed']??null,'INTENTIONAL_RED: native PTO plus declaration marks completed despite stale working state and empty legacy PTO');assertSameValue(false,$byId[967]['completed']??null,'85-percent unfinished object remains active');
    $html=(new ProductionConstructionControlRenderer())->render(new HttpUser(73,'Инженер','engineer@example.test'),$objects);if(!is_dir(dirname($htmlFile))&&!mkdir(dirname($htmlFile),0700,true))throw new RuntimeException('artifact directory');file_put_contents($htmlFile,$html,LOCK_EX);$pipes=[];$process=proc_open(['node',dirname(__DIR__).'/Support/construction_control_completed_filter_browser.cjs',$htmlFile,dirname(__DIR__,2).'/app/PilotHttp/control-queue.js'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new RuntimeException('browser start');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue([0,''],[proc_close($process),$err],'headless filter execution');$state=json_decode($out,true,16,JSON_THROW_ON_ERROR);assertSameValue(['completed'=>'true','hidden'=>true],$state['before']['966']??null,'completed row hidden by default');assertSameValue(['completed'=>'false','hidden'=>false],$state['before']['967']??null,'unfinished 85-percent row visible by default');assertSameValue(false,$state['after']['966']['hidden']??true,'completed row shown after enabling toggle');assertSameValue($before,$db->query('SELECT * FROM '.$q('fm2_pilot_completion_facts').' ORDER BY id')->fetch_all(MYSQLI_ASSOC),'queue and browser filter are read-only over completion facts');
    echo"PASS: construction-control native completion filtering\n";
}catch(Throwable$error){$failure=$error;}finally{if($db instanceof mysqli)$db->close();$admin->query('DROP DATABASE IF EXISTS '.cccfQ($database));$admin->close();if(is_file($htmlFile))unlink($htmlFile);$dir=dirname($htmlFile);if(is_dir($dir)&&count(scandir($dir)?:[])===2)rmdir($dir);}if($failure)throw$failure;
