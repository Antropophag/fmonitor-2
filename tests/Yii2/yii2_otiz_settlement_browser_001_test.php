<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require __DIR__.'/Yii2AuthFixture.php';

use FMonitor2\Tests\Yii2\Yii2AuthFixture;

// OTIZ-SETTLEMENT-001: real rendered forms, DML-only Yii runtime, exact independent money oracle.
$root=dirname(__DIR__,2);
$fixture=null;$server=null;$browser=null;$dmlUser=null;
$artifacts=sys_get_temp_dir().'/fmonitor-yii-otiz-browser-'.bin2hex(random_bytes(6));
mkdir($artifacts,0700);
try {
    $fixture=new Yii2AuthFixture($root);
    $fixture->setPermission('otiz.manage');
    $db=$fixture->db;$p=$fixture->prefix;$hash=str_repeat('d',64);
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshots(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES(301,'2026-09-30','accepted','premium-calculation-v1','2026-10-01T09:00:00+03:00',9101,'2026-10-01T10:00:00+03:00',9101,100000,0,100000,'{$hash}')");
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshot_objects(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json) VALUES(301,7301,'BROWSER-1','Synthetic browser object',0,10000,'2026-09-30',100000,10000,10000,100000,100000,0,100000,100000,100000,0,'ready','{}')");
    $trace=json_encode(['premiumCalculation'=>['formulaTrace'=>[['step'=>'fund','resultCents'=>100000],['step'=>'progress','resultCents'=>100000],['step'=>'pool','resultCents'=>100000]],'exclusions'=>[]]],JSON_THROW_ON_ERROR);
    $statement=$db->prepare("UPDATE {$p}fm2_pilot_otiz_snapshot_objects SET inputs_json=? WHERE snapshot_id=301 AND object_id=7301");
    $statement->bind_param('s',$trace);$statement->execute();
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshot_allocations(snapshot_id,object_id,tab_id,full_name,position_name,contribution_bp,base_ktu_bp,adjustment_ktu_bp,effective_ktu_bp,share_bp,amount_cents,employment_status,participation_basis) VALUES(301,7301,'7001','Browser Installer','Installer',10000,10000,0,10000,10000,100000,'employed','Recorded work')");
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshot_issues(snapshot_id,object_id,severity,issue_code,message,owner_role) VALUES(301,7301,'warning','BROWSER_WARNING','Synthetic warning retained','OTIZ owner')");
    $allocationsBefore=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_allocations")->fetch_all(MYSQLI_ASSOC);
    $issuesBefore=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_issues")->fetch_all(MYSQLI_ASSOC);
    $snapshotBefore=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshots")->fetch_all(MYSQLI_ASSOC);
    $objectBefore=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_objects")->fetch_all(MYSQLI_ASSOC);
    $dmlUser='yos_browser_'.bin2hex(random_bytes(5));$dmlPassword=bin2hex(random_bytes(20));
    $db->query("CREATE USER '{$dmlUser}'@'%' IDENTIFIED BY '{$dmlPassword}'");
    $db->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$fixture->database}`.* TO '{$dmlUser}'@'%'");
    $listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
    if(!is_resource($listener))throw new TestFailure('SETUP_FAILURE: port allocation');
    $address=stream_socket_get_name($listener,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($listener);
    $env=getenv();foreach(array_keys($env) as $key)if(str_starts_with($key,'FMONITOR_'))unset($env[$key]);
    $env=array_replace($env,$fixture->environment(),['FMONITOR_DB_USER'=>$dmlUser,'FMONITOR_DB_PASSWORD'=>$dmlPassword,'FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port]);
    $server=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,$root.'/public/yii.php'],[0=>['file','/dev/null','r'],1=>['file',$artifacts.'/server.log','a'],2=>['file',$artifacts.'/server.log','a']],$pipes,$root,$env);
    if(!is_resource($server))throw new TestFailure('SETUP_FAILURE: Yii server');
    $ready=false;$deadline=microtime(true)+5;
    do{$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if(is_resource($socket)){fclose($socket);$ready=true;break;}usleep(20000);}while(microtime(true)<$deadline);
    assertSameValue(true,$ready,'SETUP_FAILURE: Yii listener ready');
    $config=['origin'=>'http://127.0.0.1:'.$port,'email'=>$fixture->email,'password'=>$fixture->password,'artifacts'=>$artifacts,'result'=>$artifacts.'/result.json','playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($root).'/shlz-ui/node_modules/playwright'];
    file_put_contents($artifacts.'/config.json',json_encode($config,JSON_THROW_ON_ERROR));chmod($artifacts.'/config.json',0600);
    $browser=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/otiz_settlement_browser.mjs',$artifacts.'/config.json'],[0=>['file','/dev/null','r'],1=>['file',$artifacts.'/browser.log','a'],2=>['file',$artifacts.'/browser.log','a']],$pipes,$root);
    if(!is_resource($browser))throw new TestFailure('SETUP_FAILURE: browser process');
    $deadline=microtime(true)+60;
    $invalidObserved=false;
    do {
        $state=proc_get_status($browser);
        if (!$invalidObserved && is_file($artifacts.'/invalid-complete')) {
            $counts=[];
            foreach (['fm2_pilot_otiz_payment_closures','fm2_pilot_otiz_events','fm2_otiz_settlement_operations'] as $table) $counts[]=(int)$db->query("SELECT COUNT(*) FROM {$p}{$table}")->fetch_column();
            assertSameValue([0,0,0],$counts,'native over-budget rejection preserves money/events/receipts before valid actions');
            file_put_contents($artifacts.'/invalid-observed','ok');
            $invalidObserved=true;
        }
        if (!$state['running']) break;
        usleep(20000);
    } while(microtime(true)<$deadline);
    if($state['running']){proc_terminate($browser,9);throw new TestFailure('SETUP_FAILURE: browser deadline');}
    $exit=$state['exitcode'];proc_close($browser);$browser=null;
    assertSameValue(0,$exit,'browser exit; evidence '.$artifacts.'; '.file_get_contents($artifacts.'/browser.log'));
    assertSameValue(true,$invalidObserved,'native invalid submission observed independently before success');
    $observed=json_decode(file_get_contents($artifacts.'/result.json'),true,flags:JSON_THROW_ON_ERROR);
    $xlsx=(string)file_get_contents($artifacts.'/snapshot.xlsx');
    assertSameValue('PK',substr($xlsx,0,2),'download is an actual XLSX archive');
    $archive=new PharData($artifacts.'/snapshot.xlsx');
    $xml='';
    foreach(new RecursiveIteratorIterator($archive) as $entry)if($entry->isFile()&&str_ends_with($entry->getFilename(),'.xml'))$xml.=$entry->getContent();
    foreach (['Объекты','Работники','Метаданные','BROWSER-1','Browser Installer','2026-09-30','premium-calculation-v1'] as $text) assertSameValue(true,str_contains($xml,$text),'retained workbook contains '.$text);
    $rows=$db->query("SELECT id,paid_cents,discipline_cents,deadline_cents,basis,artifact,reverses_payment_closure_id,created_by_user_id FROM {$p}fm2_pilot_otiz_payment_closures ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(3,count($rows),'one closure per rendered command');
    assertSameValue([[0,10000,0],[90000,0,0],[0,-10000,0]],array_map(static fn(array $r):array=>[(int)$r['paid_cents'],(int)$r['discipline_cents'],(int)$r['deadline_cents']],$rows),'literal accrued100000 discipline10000 paid90000 reversal-10000 oracle');
    assertSameValue(['Browser discipline','Browser evidence',null,9101],[$rows[0]['basis'],$rows[0]['artifact'],$rows[0]['reverses_payment_closure_id'],(int)$rows[0]['created_by_user_id']],'original discipline retained');
    assertSameValue(['Browser reversal','',(int)$rows[0]['id'],9101],[$rows[2]['basis'],$rows[2]['artifact'],(int)$rows[2]['reverses_payment_closure_id'],(int)$rows[2]['created_by_user_id']],'reversal linked and attributed');
    $receipts=$db->query("SELECT operation_id,status FROM {$p}fm2_otiz_settlement_operations ORDER BY FIELD(status,'recorded','completed','reversed')")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(['recorded','completed','reversed'],array_column($receipts,'status'),'one durable receipt per successful form');
    assertSameValue($observed['operations'],array_column($receipts,'operation_id'),'persisted IDs came from the actual rendered forms');
    assertSameValue(['payment_closure_recorded','payment_completed','snapshot_payments_completed','payment_closure_reversed'],array_column($db->query("SELECT event_type FROM {$p}fm2_pilot_otiz_events ORDER BY id")->fetch_all(MYSQLI_ASSOC),'event_type'),'four canonical audit events');
    assertSameValue($snapshotBefore,$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshots")->fetch_all(MYSQLI_ASSOC),'accepted snapshot unchanged');
    assertSameValue($objectBefore,$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_objects")->fetch_all(MYSQLI_ASSOC),'accepted object calculation unchanged');
    assertSameValue($allocationsBefore,$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_allocations")->fetch_all(MYSQLI_ASSOC),'worker allocation remains unchanged');
    assertSameValue($issuesBefore,$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_issues")->fetch_all(MYSQLI_ASSOC),'existing issue remains unchanged');
    echo 'PASS: OTIZ-SETTLEMENT-001 real Yii browser forms; artifacts '.$artifacts."\n";
} finally {
    if(is_resource($browser)){proc_terminate($browser,9);proc_close($browser);}
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if($fixture instanceof Yii2AuthFixture){if($dmlUser!==null)$fixture->db->query("DROP USER IF EXISTS '{$dmlUser}'@'%'");$fixture->close();}
    if(is_file($artifacts.'/config.json'))unlink($artifacts.'/config.json');
}
