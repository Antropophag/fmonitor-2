<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require dirname(__DIR__).'/Support/SelectionSchemaWorkerControl.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\InstallationProcess as I;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001: real public command workers; native lock observation.
function commandWorker(F $f,int $request,int $object,int $installer,string $hold=''):array {
    $p=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/selection_native_command_worker.php',$f->schema->source->name,(string)$request,(string)$object,(string)$installer,$hold],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($p))throw new TestFailure('worker start failed');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
    return ['process'=>$p,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
}
function commandWaiting(F $f,array &$workers,bool $caseLock):void {
    $ids=[];foreach($workers as &$w){aossWaitPhase($w,'ready');if(!preg_match('/THREAD ([0-9]+)/',$w['stdout'],$m))throw new TestFailure('missing native thread');$ids[]=(int)$m[1];}unset($w);
    $deadline=hrtime(true)+8_000_000_000;
    do {
        $s=$f->db->prepare('SELECT ID,DB,TIME,INFO FROM information_schema.PROCESSLIST WHERE ID IN (?,?)');$s->execute($ids);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        $blocked=count($rows)===2;
        foreach($rows as $row){
            $query=(string)$row['INFO'];
            $expected=$caseLock?str_starts_with($query,'SELECT legacy_installation_object_id FROM `fm2_installation_cases`')&&str_ends_with($query,'FOR UPDATE'):str_starts_with($query,'INSERT INTO `fm2_assignment_order_selection_requests` ');
            $blocked=$blocked&&$row['DB']===$f->schema->source->name&&(int)$row['TIME']>=1&&$expected;
        }
        if($blocked){echo "NATIVE_TWO_BLOCKED_STATEMENTS\n";return;}
        foreach($workers as &$w){aossDrainWorker($w,10000);if(!$w['status']['running'])throw new TestFailure('worker escaped required lock: '.$w['stdout'].' '.$w['stderr']);}unset($w);
        usleep(30000);
    }while(hrtime(true)<$deadline);
    throw new TestFailure('two native lock waiters were not observed: '.json_encode(['threads'=>$ids,'processes'=>$f->db->query('SELECT ID,STATE,INFO FROM information_schema.PROCESSLIST WHERE ID IN ('.implode(',',$ids).')')->fetch_all(MYSQLI_ASSOC)],JSON_UNESCAPED_UNICODE));
}
function commandResult(array &$w):array {
    $r=aossReapWorker($w,15);assertSameValue([0,''],[$r['exit'],$r['stderr']],'worker clean exit');
    if(!preg_match('/^RESULT (.+)$/m',$r['stdout'],$m))throw new TestFailure('worker result absent');return json_decode($m[1],true,512,JSON_THROW_ON_ERROR);
}
$failed=0;
foreach(['same_case','same_request_different_cases','different_cases','lost_response'] as $axis){$f=null;$lock=null;$workers=[];$errors=[];
    try {
        $f=new F();
        if(in_array($axis,['same_request_different_cases','different_cases'],true)){
            $f->db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4513,4513,'needs_assignment_order','2026-08-20T09:00:00Z','2026-08-20T09:00:00Z',1)");$f->db->query("INSERT INTO fm_maintable(id,ordadr_address,regnumber) VALUES(4513,'Вымышленный объект 2','TEST-4513')");
        }
        $before=$f->rows();echo "SETUP_OK $axis\n";
        if($axis==='lost_response'){
            $workers[]=commandWorker($f,1,4512,7001,'hold');aossWaitPhase($workers[0],'committed');aossCleanupWorker($workers[0]);$committed=$f->rows();
            assertSameValue(1,count($committed['fm2_assignment_order_selections']),'committed before external response loss');$workers[]=commandWorker($f,1,4512,7001);$r=commandResult($workers[1]);
            assertSameValue(['replayed',81,1],[$r['status'],$r['assignmentOrderId'],$r['selectionRevision']],'fresh process silently recovers committed request');assertSameValue($committed,$f->rows(),'response loss retry has no mutation');
        }else{
            $lock=$f->schema->source->connect($f->schema->source->name);$lock->begin_transaction();
            if($axis==='same_case')$lock->query('SELECT id FROM fm2_installation_cases WHERE id=4512 FOR UPDATE');
            else $lock->query("SELECT request_id FROM fm2_assignment_order_selection_requests WHERE request_id='11111111-1111-4111-8111-000000000001' FOR UPDATE");
            $workers[]=commandWorker($f,1,4512,7001);$workers[]=commandWorker($f,$axis==='same_request_different_cases'?1:2,$axis==='same_case'?4512:4513,7002);
            commandWaiting($f,$workers,$axis==='same_case');$lock->rollback();$lock->close();$lock=null;
            $results=[commandResult($workers[0]),commandResult($workers[1])];$statuses=array_column($results,'status');sort($statuses);
            assertSameValue($axis==='different_cases'?['selected','selected']:['conflict','selected'],$statuses,'exact native concurrent outcomes');
            if($axis!=='different_cases'){
                $loser=array_values(array_filter($results,fn($r)=>$r['status']==='conflict'))[0];assertSameValue($axis==='same_case'?'stale_selection':'request_id_conflict',$loser['reasonCode'],'exact loser reason');assertSameValue(null,$loser['assignmentOrderId'],'loser has no success disclosure');
            }else{$ids=array_column($results,'assignmentOrderId');sort($ids);assertSameValue([81,82],$ids,'one global allocator across cases');assertSameValue([1,1],array_column($results,'assignmentOrderVersion'),'per-case version1');}
            $after=$f->rows();$accepted=$axis==='different_cases'?2:1;
            foreach(['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_events'] as $t)assertSameValue($accepted,count($after[$t]),'no ghost facts after losing writer');
            assertSameValue($axis==='same_request_different_cases'?1:2,count($after['fm2_assignment_order_selection_requests']),'request race leaves winner only');assertSameValue(2,count($after['fm2_assignment_order_selection_audits']),'one audit per terminal invocation');
            $mutable=['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_requests','fm2_assignment_order_selection_events','fm2_assignment_order_selection_audits'];
            foreach($before as $table=>$rows)if(!in_array($table,$mutable,true))assertSameValue($rows,$after[$table],"unrelated concurrent facts preserved $table");
            assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($f->db),'committed concurrent ledger coherent');
        }
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($lock!==null)try{$lock->rollback();$lock->close();}catch(Throwable $e){$errors[]='lock cleanup: '.$e->getMessage();}
    foreach($workers as &$w)try{aossCleanupWorker($w);}catch(Throwable $e){$errors[]='worker cleanup: '.$e->getMessage();}unset($w);
    if($f!==null)try{$f->close();echo "CLEANUP_OK $axis\n";}catch(Throwable $e){$errors[]='db cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $axis: ".implode(' | ',$errors)."\n";}else echo "PASS $axis\n";
}exit($failed===0?0:1);
