<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\Otiz\SnapshotPublication;
use FMonitor2\Otiz\MariaDbSnapshotStore;
use FMonitor2\InstallationProcess\PilotOtizSchemaMigration;
use FMonitor2\InstallationProcess\OtizPublicationSchemaMigration;

assertSameValue(true, class_exists(SnapshotPublication::class), 'OTIZ-SNAPSHOT-PUBLICATION-001: public atomic publication operation exists');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';
$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';
$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$name='otiz_publication_'.bin2hex(random_bytes(6));
$admin=new mysqli($host,$user,$password,'',$port);
$admin->query("CREATE DATABASE `$name` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$db=new mysqli($host,$user,$password,$name,$port);
$other=new mysqli($host,$user,$password,$name,$port);
$p='op_';
$clock=static fn():string=>'2026-09-08T15:00:00+03:00';
$ids=0;
$operation=static function()use(&$ids):string{return sprintf('00000000-0000-4000-8000-%012d',++$ids);};
$reject=static function(string $expected, callable $call):void{
    try{$call();}catch(DomainException $e){assertSameValue($expected,$e->getMessage(),'exact refusal');return;}
    throw new TestFailure('Expected refusal '.$expected);
};
$inputs=static function(string $date):array{
    $source=['label'=>'Approved synthetic example','locator'=>'spec/OTIZ-SNAPSHOT-PUBLICATION-001','contentSha256'=>str_repeat('a',64)];
    $fact=static fn($v):array=>['value'=>$v,'effectiveDate'=>'2026-09-01','source'=>$source];
    $rows=[];
    foreach([[101,10000],[202,20000]]as[$id,$premium]){
        $rows[]=['id'=>$id,'caseId'=>$id,'reg'=>'TEST-'.$id,'address'=>'Synthetic '.$id,
            'progress'=>8500,'deadline'=>'2026-09-30','pto'=>null,'premium'=>$premium,'shaft'=>10000,
            'issues'=>[],'team'=>[
                ['tab'=>'1','name'=>'One','position'=>'Installer','weight'=>1],
                ['tab'=>'2','name'=>'Two','position'=>'Installer','weight'=>1]],
            'operands'=>['reportDate'=>$fact($date),'premiumCents'=>$fact($premium),
                'shaftBp'=>$fact(10000),'progressBp'=>$fact(8500),
                'deadlineDate'=>$fact('2026-09-30'),'completionDate'=>$fact(null)]];
    }
    return $rows;
};
try{
    PilotOtizSchemaMigration::apply($db,$p);
    OtizPublicationSchemaMigration::apply($db,$p);
    $db->query("CREATE TABLE {$p}fm2_pilot_users(user_id BIGINT PRIMARY KEY,status INT,activation_state VARCHAR(40))");
    $db->query("CREATE TABLE {$p}fm2_pilot_user_roles(user_id BIGINT,role_id BIGINT)");
    $db->query("CREATE TABLE {$p}fm2_pilot_roles(role_id BIGINT PRIMARY KEY,status INT)");
    $db->query("CREATE TABLE {$p}fm2_pilot_role_permissions(role_id BIGINT,permission VARCHAR(80))");
    $db->query("INSERT INTO {$p}fm2_pilot_users VALUES(1,1,'active'),(2,1,'active')");
    $db->query("INSERT INTO {$p}fm2_pilot_roles VALUES(1,1)");
    $db->query("INSERT INTO {$p}fm2_pilot_user_roles VALUES(1,1)");
    $db->query("INSERT INTO {$p}fm2_pilot_role_permissions VALUES(1,'otiz.manage')");
    $service=new SnapshotPublication(new MariaDbSnapshotStore($db,$p),$inputs,$clock);
    $reader=new SnapshotPublication(new MariaDbSnapshotStore($other,$p),$inputs,$clock);
    $empty=(new SnapshotPublication(new MariaDbSnapshotStore($db,$p),static fn(string$date):array=>[],$clock))->buildAndPublish(1,'2026-09-08',$operation());
    $emptyResult=$reader->read(1,$empty);$receipt=$emptyResult['publication'];
    assertSameValue(['otiz-publication-v1',0,0,0,'afe8736fcf02174a47e94fdb7676699ef0b9af006c04484b1a7560ea57a4742a'],[$receipt['manifest_version'],(int)$receipt['object_count'],(int)$receipt['allocation_count'],(int)$receipt['issue_count'],$receipt['manifest_sha256']],'independent empty snapshot canonical golden');
    $op=$operation();$id=$service->buildAndPublish(1,'2026-09-08',$op);
    $result=$reader->read(1,$id);
    assertSameValue('draft',$result['snapshot']['status'],'published draft needs explicit acceptance');
    assertSameValue(25500,(int)$result['snapshot']['total_pool_cents'],'independent total');
    assertSameValue([8500,17000],array_map(fn($r)=>(int)$r['pool_cents'],$result['objects']),'independent object pools');
    assertSameValue([4250,4250,8500,8500],array_map(fn($r)=>(int)$r['amount_cents'],$result['allocations']),'independent allocations');
    assertSameValue([], $result['issues'],'no issues');
    assertSameValue(['draft_calculated'],array_column($result['events'],'event_type'),'one publication audit');
    assertSameValue($id,$service->buildAndPublish(1,'2026-09-08',$op),'replay returns saved result');
    $reject('OPERATION_CONFLICT',fn()=>$service->buildAndPublish(1,'2026-09-09',$op));
    $reject('FORBIDDEN',fn()=>$service->buildAndPublish(2,'2026-09-08',$operation()));
    $reject('FORBIDDEN',fn()=>$service->accept(2,$id));
    $reject('INVALID_DATE',fn()=>$service->buildAndPublish(1,'2026-02-30',$operation()));
    $reject('INVALID_OPERATION_ID',fn()=>$service->buildAndPublish(1,'2026-09-08','invalid'));
    $reject('FORBIDDEN',fn()=>$service->read(2,$id));
    $reject('FORBIDDEN',fn()=>$service->history(2));
    $persistedCounts=static function()use($db,$p):array{
        $counts=[];foreach(['snapshots','snapshot_objects','snapshot_allocations','snapshot_issues','events']as$suffix)$counts[]=(int)$db->query("SELECT COUNT(*) n FROM {$p}fm2_pilot_otiz_{$suffix}")->fetch_assoc()['n'];
        $counts[]=(int)$db->query("SELECT COUNT(*) n FROM {$p}fm2_otiz_publications")->fetch_assoc()['n'];return$counts;
    };
    $beforeCounts=$persistedCounts();
    $before=$reader->history(1);
    foreach(['inputs','second_object','publication']as$failure){
        if($failure==='second_object')$db->query("CREATE TRIGGER fail_object BEFORE INSERT ON {$p}fm2_pilot_otiz_snapshot_objects FOR EACH ROW BEGIN IF NEW.object_id=202 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='controlled second object failure'; END IF; END");
        if($failure==='publication')$db->query("CREATE TRIGGER fail_publication BEFORE INSERT ON {$p}fm2_otiz_publications FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='controlled publication failure'");
        $load=static function(string$date)use($failure,$inputs,$reader,$before):array{
            assertSameValue($before,$reader->history(1),'uncommitted header invisible on other connection');
            if($failure==='inputs')throw new RuntimeException('controlled input failure');
            return $inputs($date);
        };
        try{(new SnapshotPublication(new MariaDbSnapshotStore($db,$p),$load,$clock))->buildAndPublish(1,'2026-09-08',$operation());throw new TestFailure('Failure injection did not fire');}
        catch(RuntimeException $e){if(!str_contains($e->getMessage(),'controlled'))throw $e;}
        finally{if($failure==='second_object')$db->query('DROP TRIGGER fail_object');if($failure==='publication')$db->query('DROP TRIGGER fail_publication');}
        assertSameValue($before,$reader->history(1),'failed publication rolls back entire result');
        assertSameValue($beforeCounts,$persistedCounts(),'rollback conserves all rows, including children and audit');
    }
    $db->query("CREATE TABLE input_cut(id INT PRIMARY KEY,premium INT) ENGINE=InnoDB");
    $db->query("INSERT INTO input_cut VALUES(1,20000)");
    $consistent=static function(string$date)use($db,$other,$inputs):array{
        $db->query('SELECT premium FROM input_cut WHERE id=1')->fetch_assoc();
        $other->query('UPDATE input_cut SET premium=30000 WHERE id=1');
        $value=(int)$db->query('SELECT premium FROM input_cut WHERE id=1')->fetch_assoc()['premium'];
        $rows=$inputs($date);$rows[1]['premium']=$value;$rows[1]['operands']['premiumCents']['value']=$value;
        return $rows;
    };
    $cut=(new SnapshotPublication(new MariaDbSnapshotStore($db,$p),$consistent,$clock))->buildAndPublish(1,'2026-09-08',$operation());
    assertSameValue(17000,(int)$reader->read(1,$cut)['objects'][1]['pool_cents'],'one input cut despite concurrent source update');
    $concurrent=static function(string$action,string$arg)use($name,$inputs):array{
        $jobs=[];$environment=array_replace(getenv(),['OTIZ_TEST_DATABASE'=>$name,'OTIZ_TEST_INPUTS'=>json_encode($inputs('2026-09-08'),JSON_THROW_ON_ERROR)]);
        for($i=0;$i<2;$i++){
            $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/OtizPublicationWorker.php',$action,$arg],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$environment);
            if(!is_resource($process))throw new TestFailure('worker setup failed');
            stream_set_timeout($pipes[1],10);stream_set_timeout($pipes[2],10);$jobs[]=[$process,$pipes];
        }
        $out=[];
        try{foreach($jobs as[$process,$pipes]){$value=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);$timed=stream_get_meta_data($pipes[1])['timed_out'];fclose($pipes[1]);fclose($pipes[2]);if($timed)proc_terminate($process,9);$code=proc_close($process);assertSameValue(0,$code,'concurrent worker: '.$error);$out[]=$value;}}
        finally{foreach($jobs as[$process,$pipes])if(is_resource($process)){proc_terminate($process,9);proc_close($process);}}
        return$out;
    };
    $twice=$concurrent('build',$operation());
    assertSameValue(true,ctype_digit($twice[0]),'concurrent publication returns snapshot id');
    assertSameValue($twice[0],$twice[1],'concurrent replay produces one identity');
    $parallelId=(int)$twice[0];
    assertSameValue(['draft_calculated'],array_column($reader->read(1,$parallelId)['events'],'event_type'),'one publication after concurrent replay');
    $accepted=$concurrent('accept',(string)$parallelId);sort($accepted);
    assertSameValue(['IMMUTABLE','OK'],$accepted,'concurrent acceptance serialized');
    assertSameValue(['draft_calculated','snapshot_accepted'],array_column($reader->read(1,$parallelId)['events'],'event_type'),'one acceptance under concurrency');
    $service->accept(1,$id);$result=$reader->read(1,$id);
    assertSameValue('accepted',$result['snapshot']['status'],'explicit acceptance');
    assertSameValue(['draft_calculated','snapshot_accepted'],array_column($result['events'],'event_type'),'single acceptance audit');
    $reject('IMMUTABLE',fn()=>$service->accept(1,$id));
    $blockedInputs=static function(string$date)use($inputs):array{$rows=$inputs($date);$rows[0]['issues']=[['code'=>'TEST_BLOCKER','message'=>'Missing verified input','owner'=>'OTIZ']];return$rows;};
    $blocked=(new SnapshotPublication(new MariaDbSnapshotStore($db,$p),$blockedInputs,$clock))->buildAndPublish(1,'2026-09-08',$operation());
    $blockedBefore=$reader->read(1,$blocked);
    $reject('BLOCKERS',fn()=>$service->accept(1,$blocked));
    assertSameValue($blockedBefore,$reader->read(1,$blocked),'blocker refusal preserves entire snapshot and history');
    $corrupted=$service->buildAndPublish(1,'2026-09-08',$operation());
    $db->query("UPDATE {$p}fm2_pilot_otiz_snapshot_allocations SET amount_cents=amount_cents+1 WHERE snapshot_id=$corrupted AND object_id=101 AND tab_id='1'");
    $corruptedBefore=$reader->read(1,$corrupted);
    $reject('SNAPSHOT_INCOMPLETE',fn()=>$service->accept(1,$corrupted));
    assertSameValue($corruptedBefore,$reader->read(1,$corrupted),'same-count corruption refusal preserves history');
    $damaged=$service->buildAndPublish(1,'2026-09-08',$operation());
    $db->query("DELETE FROM {$p}fm2_pilot_otiz_snapshot_allocations WHERE snapshot_id=$damaged AND object_id=202");
    $damagedBefore=$reader->read(1,$damaged);
    $reject('SNAPSHOT_INCOMPLETE',fn()=>$service->accept(1,$damaged));
    assertSameValue($damagedBefore,$reader->read(1,$damaged),'incomplete refusal preserves history');
    assertSameValue('draft',$reader->read(1,$damaged)['snapshot']['status'],'damaged draft unchanged');
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshots(report_date,status,rules_version,calculated_at,calculated_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES('2026-09-08','draft','legacy','2026-09-08',1,0,0,0,'pending')");
    $legacy=(int)$db->insert_id;$reject('SNAPSHOT_INCOMPLETE',fn()=>$service->accept(1,$legacy));
    $reject('NOT_FOUND',fn()=>$service->accept(1,999999));
    echo "OTIZ_SNAPSHOT_PUBLICATION_OK\n";
}finally{
    $other->close();$db->close();$admin->query("DROP DATABASE `$name`");$admin->close();
}
