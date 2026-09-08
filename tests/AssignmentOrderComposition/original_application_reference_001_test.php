<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require dirname(__DIR__).'/Support/SelectionSchemaWorkerControl.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalInput as Input;
use FMonitor2\Tests\Support\SelectionNativeFixture as S;

// ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1. Public native seam only.
function oarReader(F $f):object {
    assertSameValue(true,is_callable([O\AssignmentOrderOriginalApplicationReferenceFactory::class,'create']),'RED_ASSERTION: original application reference factory missing after healthy native setup');
    return O\AssignmentOrderOriginalApplicationReferenceFactory::create($f->selection->db,$f->prefix);
}
function oarLookup(object $reader,string $status,int $object=4512,int $order=81):?object {
    $lookup=$reader->readCurrent($object,$order);assertSameValue($status,$lookup->status->value,'exact public lookup status');
    assertSameValue($status==='found',$lookup->reference!==null,'reference iff found');return $lookup->reference;
}
function oarTx(mysqli $db,int $expected):void {assertSameValue($expected,(int)$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'caller transaction ownership');}
function oarCorrect(F $f,O\AssignmentOrderOriginalResult $first,?mysqli $other=null):O\AssignmentOrderOriginalResult {
    $app=$other===null?$f->app():O\ProductionAssignmentOrderOriginalFactory::createForSelections($other,$f->config(),$f->fresh());
    $r=$app->submitAssignmentOrderOriginal(new O\SubmitAssignmentOrderOriginalCommand('22222222-2222-4222-8222-000000000002',O\AssignmentOrderOriginalMode::CORRECTION,4512,81,18,'2026-09-03',true,$first->rootOriginalId(),$first->currentRevisionId(),$first->currentRevisionId(),'Уточнена дата',new O\AssignmentOrderOriginalUpload(new Input(F::pdf()),'signed.pdf','application/pdf')));
    assertSameValue(['accepted',2,'2026-09-03'],[$r->status()->value,$r->revisionNumber(),$r->documentDate()],'native correction setup');return $r;
}
$tests=[
'exact metadata and ownership'=>static function(F $f,$first):void {
    $reader=oarReader($f);$f->selection->db->query('CREATE TABLE fixture_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$before=$f->selection->rows();$files=$f->privateFiles();$ref=oarLookup($reader,'found');$db=$f->selection->db;oarTx($db,0);
    $expected=['objectId'=>4512,'caseId'=>4512,'orderId'=>81,'orderVersion'=>1,'rootOriginalId'=>$first->rootOriginalId(),'revisionId'=>$first->currentRevisionId(),'revisionNumber'=>1,'documentDate'=>'2026-09-04','sha256'=>'78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62','byteSize'=>327,'uploadedAt'=>'2026-09-05T09:00:00Z','compositionIdentity'=>'composition-81-v1','compositionSha256'=>'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a','composition'=>['installers'=>[['tabId'=>7001,'fullName'=>'Монтажник 7001','position'=>'Монтажник']],'engineer'=>['userId'=>73,'fullName'=>'Инженер теста','position'=>'Инженер строительного контроля']]];
    assertSameValue($expected,$ref->metadata(),'exact independently fixed fourteen fields');$copy=$ref->metadata();$copy['composition']['installers'][0]['tabId']=999;$copy['documentDate']='2000-01-01';assertSameValue($expected,$ref->metadata(),'copy cannot mutate reference');
    assertSameValue('unavailable',$reader->confirmCurrent($ref)->value,'idle guard refuses');oarTx($db,0);
    $db->begin_transaction();$db->query('INSERT INTO fixture_sentinel VALUES(1)');assertSameValue('matched',$reader->confirmCurrent($ref)->value,'same source guard');assertSameValue('matched',$reader->confirmCurrent($ref)->value,'repeat guard');oarTx($db,1);assertSameValue('1',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'matched guard preserves uncommitted caller write');$db->rollback();assertSameValue('0',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'matched guard never commits caller write');
    assertSameValue($before,$f->selection->rows(),'reader and guard write no facts');assertSameValue($files,$f->privateFiles(),'reader and guard touch no files');
    rename($f->privateRoot,$f->privateRoot.'-held');try{assertSameValue($expected,oarLookup($reader,'found')->metadata(),'metadata does not require PDF root');}finally{rename($f->privateRoot.'-held',$f->privateRoot);}assertSameValue($files,$f->privateFiles(),'exact restored files');
},
'caller transaction and foreign proof'=>static function(F $f):void {
    $r=oarReader($f);$ref=oarLookup($r,'found');$foreign=oarReader($f);$db=$f->selection->db;$db->query('CREATE TABLE fixture_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$db->begin_transaction();$db->query('INSERT INTO fixture_sentinel VALUES(1)');
    oarLookup($r,'unavailable');assertSameValue('unavailable',$foreign->confirmCurrent($ref)->value,'foreign issuing reader');oarTx($db,1);assertSameValue('1',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'uncommitted sentinel retained');$db->rollback();assertSameValue('0',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'reader did not commit sentinel');
    $db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);assertSameValue('unavailable',$r->confirmCurrent($ref)->value,'read-only caller guard');oarTx($db,1);$db->rollback();
},
'correction and stale RR current read'=>static function(F $f,$first):void {
    $reader=oarReader($f);$ref=oarLookup($reader,'found');$db=$f->selection->db;$other=$f->selection->schema->source->connect($f->selection->schema->source->name);
    try{$db->query('CREATE TABLE fixture_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$db->begin_transaction();$db->query('INSERT INTO fixture_sentinel VALUES(1)');$old=$db->query('SELECT current_revision_id FROM `'.$f->prefix.'fm2_assignment_order_original_roots`')->fetch_assoc()['current_revision_id'];assertSameValue($first->currentRevisionId(),$old,'old RR snapshot established');
        $next=oarCorrect($f,$first,$other);assertSameValue($old,$db->query('SELECT current_revision_id FROM `'.$f->prefix.'fm2_assignment_order_original_roots`')->fetch_assoc()['current_revision_id'],'RR ordinary read remains old after other commit');
        assertSameValue('changed',$reader->confirmCurrent($ref)->value,'locking guard sees committed correction beyond stale RR');oarTx($db,1);assertSameValue('1',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'changed preserves caller write');$db->rollback();assertSameValue('0',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'changed never commits caller write');
        $metadata=oarLookup($reader,'found')->metadata();assertSameValue([$next->currentRevisionId(),2,'2026-09-03'],[$metadata['revisionId'],$metadata['revisionNumber'],$metadata['documentDate']],'new reference points to corrected revision');assertSameValue('2026-09-04',$ref->metadata()['documentDate'],'old immutable reference preserved');
        $db->begin_transaction();assertSameValue('changed',$reader->confirmCurrent($ref)->value,'old ref remains changed in fresh TX');$db->rollback();
    }finally{if((int)$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'])$db->rollback();$other->close();}
},
'corrupt sealed source'=>static function(F $f):void {
    $r=oarReader($f);$ref=oarLookup($r,'found');$db=$f->selection->db;$db->query('CREATE TABLE fixture_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$db->query('UPDATE `'.$f->prefix.'fm2_assignment_order_original_revisions` SET byte_size=328');$bad=$f->selection->rows();oarLookup($r,'unavailable');oarTx($db,0);
    $db->begin_transaction();$db->query('INSERT INTO fixture_sentinel VALUES(1)');assertSameValue('unavailable',$r->confirmCurrent($ref)->value,'same revision metadata drift is not matched or changed');oarTx($db,1);assertSameValue('1',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'corrupt guard preserves caller write');$db->rollback();assertSameValue('0',$db->query('SELECT COUNT(*) n FROM fixture_sentinel')->fetch_assoc()['n'],'corrupt guard never commits caller write');assertSameValue($bad,$f->selection->rows(),'corruption never repaired by read');
},
'identity absence configuration and binding'=>static function(F $f):void {
    $r=oarReader($f);$ref=oarLookup($r,'found');foreach([[0,81],[-1,81],[4512,0],[4512,-1]] as [$o,$id])oarLookup($r,'invalid_argument',$o,$id);
    oarLookup($r,'not_found',4513,81);oarLookup($r,'not_found',4512,999);
    $wrong=O\AssignmentOrderOriginalApplicationReferenceFactory::create($f->selection->db,'wrong_');oarLookup($wrong,'unavailable');
    $closed=$f->selection->schema->source->connect($f->selection->schema->source->name);$closedReader=O\AssignmentOrderOriginalApplicationReferenceFactory::create($closed,$f->prefix);$closed->close();oarLookup($closedReader,'invalid_argument',0,81);oarLookup($closedReader,'unavailable');
    foreach([str_repeat('p',26),'bad-','Ю'] as $prefix){try{O\AssignmentOrderOriginalApplicationReferenceFactory::create($closed,$prefix);throw new TestFailure('invalid prefix accepted');}catch(O\AssignmentOrderOriginalApplicationReferenceConfigurationUnavailable $e){assertSameValue(['Original application reference configuration unavailable.',0,null],[$e->getMessage(),$e->getCode(),$e->getPrevious()],'sanitized prefix error before closed DB access');}}
    $unselected=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',null,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    try{$unselected->set_charset('utf8mb4');$unselectedReader=O\AssignmentOrderOriginalApplicationReferenceFactory::create($unselected,$f->prefix);oarLookup($unselectedReader,'unavailable');assertSameValue(null,$unselected->query('SELECT DATABASE() db')->fetch_assoc()['db'],'no database selected or synthesized');oarTx($unselected,0);}finally{$unselected->close();}
    $db=$f->selection->db;$db->set_charset('latin1');oarLookup($r,'unavailable');$db->begin_transaction();assertSameValue('unavailable',$r->confirmCurrent($ref)->value,'charset switch');oarTx($db,1);assertSameValue('latin1',$db->character_set_name(),'reader preserves charset');$db->rollback();$db->set_charset('utf8mb4');
    $other=new F($f->prefix);try{$db->select_db($other->selection->schema->source->name);$db->begin_transaction();assertSameValue('unavailable',$r->confirmCurrent($ref)->value,'database switch');oarTx($db,1);assertSameValue($other->selection->schema->source->name,$db->query('SELECT DATABASE() db')->fetch_assoc()['db'],'reader preserves selected DB');$db->rollback();}finally{$db->select_db($f->selection->schema->source->name);$other->close();}
},
'accepted original required'=>static function(F $f):void {
    $r=oarReader($f);$c=S::command(2,1,7002);$command=new FMonitor2\AssignmentOrderComposition\SelectAssignmentOrderCompositionCommand($c->requestId,FMonitor2\AssignmentOrderComposition\AssignmentOrderCompositionMode::NEW_ORDER,$c->installationObjectId,$c->actorUserId,$c->installerTabIds,$c->controlEngineerUserId,$c->expectedSelectionRevision);
    assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition($command)->status()->value,'new pending selection without original');oarLookup($r,'not_found',4512,82);$ref=oarLookup($r,'found');$f->selection->db->begin_transaction();assertSameValue('matched',$r->confirmCurrent($ref)->value,'port does not impose global latest order');$f->selection->db->rollback();
},
'guard holds real original correction'=>static function(F $f,$first):void {
    $r=oarReader($f);$ref=oarLookup($r,'found');$db=$f->selection->db;$worker=null;$control=$f->selection->schema->source->connect($f->selection->schema->source->name);
    try{$db->begin_transaction();assertSameValue('matched',$r->confirmCurrent($ref)->value,'case lock acquired');
        $path=$f->control.'/worker.json';file_put_contents($path,json_encode(['database'=>$f->selection->schema->source->name,'prefix'=>$f->prefix,'private'=>$f->privateRoot,'log'=>$f->safeLog,'password'=>$f->control.'/password','root'=>$first->rootOriginalId(),'revision'=>$first->currentRevisionId()],JSON_THROW_ON_ERROR));chmod($path,0600);
        $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/original_application_reference_worker.php',$path],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new TestFailure('worker start');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$worker=['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];aossWaitPhase($worker,'ready');
        if(!preg_match('/THREAD ([0-9]+)/',$worker['stdout'],$m))throw new TestFailure('owned thread identity');$thread=(int)$m[1];$deadline=hrtime(true)+8_000_000_000;$blocked=false;
        do{$row=$control->query('SELECT DB,TIME,INFO FROM information_schema.PROCESSLIST WHERE ID='.$thread)->fetch_assoc();if($row!==null&&$row['DB']===$f->selection->schema->source->name&&(int)$row['TIME']>=1&&(string)$row['INFO']==='SELECT id FROM `'.$f->prefix.'fm2_installation_cases` WHERE id=4512 FOR UPDATE'){$blocked=true;break;}aossDrainWorker($worker,30000);if(!$worker['status']['running'])throw new TestFailure('correction escaped guard case lock');}while(hrtime(true)<$deadline);
        assertSameValue(true,$blocked,'real original correction waits on canonical case row');echo "NATIVE_ORIGINAL_BLOCKED_BY_REFERENCE_GUARD\n";oarTx($db,1);$db->rollback();$result=aossReapWorker($worker,15);assertSameValue([0,'',false],[$result['exit'],$result['stderr'],$result['signaled']],'owned worker clean exit');if(!preg_match('/^RESULT (.+)$/m',$result['stdout'],$m))throw new TestFailure('worker result');assertSameValue(['accepted',2,'2026-09-03'],json_decode($m[1],true,512,JSON_THROW_ON_ERROR),'correction accepted only after caller release');
    }finally{if((int)$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'])$db->rollback();if($worker!==null)aossCleanupWorker($worker);$control->close();}
},
];
$failed=0;foreach(['',str_repeat('p',25)] as $prefix)foreach($tests as $name=>$test){$f=null;$errors=[];$label=$name.' prefix'.strlen($prefix);
    try{$f=new F($prefix);assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition(S::command())->status()->value,'native selection prerequisite');$first=$f->app()->submitAssignmentOrderOriginal(F::command(new Input(F::pdf())));assertSameValue(['accepted',1,327,'2026-09-05T09:00:00Z'],[$first->status()->value,$first->revisionNumber(),$first->byteSize(),$first->uploadedAt()],'healthy accepted original prerequisite');echo "SETUP_OK $label\n";$test($f,$first);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $label\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}if($errors!==[]){$failed++;echo "FAIL $label: ".implode(' | ',$errors)."\n";}else echo "PASS $label\n";
}exit($failed===0?0:1);
