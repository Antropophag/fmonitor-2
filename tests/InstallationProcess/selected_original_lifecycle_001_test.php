<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require dirname(__DIR__).'/Support/SelectionSchemaWorkerControl.php';
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalInput as Input;
use FMonitor2\Tests\Support\SelectionNativeFixture as S;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001: real target/correction/serialization.
function selectedOriginalCommand(Input $input,int $request=1,int $order=81,?O\AssignmentOrderOriginalResult $root=null):O\SubmitAssignmentOrderOriginalCommand {
    return new O\SubmitAssignmentOrderOriginalCommand(sprintf('22222222-2222-4222-8222-%012d',$request),$root===null?O\AssignmentOrderOriginalMode::INITIAL:O\AssignmentOrderOriginalMode::CORRECTION,4512,$order,18,$root===null?'2026-09-04':'2026-09-03',true,$root?->rootOriginalId(),$root?->currentRevisionId(),$root?->currentRevisionId(),$root===null?null:'Исправление ошибочно указанной даты оригинала',new O\AssignmentOrderOriginalUpload($input,'signed.pdf','application/pdf'));
}
function selectedOriginalOtherSelector(F $f,mysqli $db):C\AssignmentOrderCompositionApplication {
    $p=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($db,fn()=>$f->selection->schema->source->connect($f->selection->schema->source->name));
    $clock=new class implements C\SelectionClock {public function now():C\SelectionInstantLookup{return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T09:00:00Z'));}};
    return C\AssignmentOrderCompositionFactory::create(new C\SelectionDependencies($p->authorizer,$p->facts,$clock,$p->requests,$p->transactions,$p->freshReaders,$p->audits,$p->terminalAttempts));
}
function selectedOriginalObserver(Closure $callback):O\AssignmentOrderOriginalPersistenceObserver {return new class($callback) implements O\AssignmentOrderOriginalPersistenceObserver {public function __construct(private Closure $callback){}public function observe(O\AssignmentOrderOriginalPersistenceEvent $event):void{($this->callback)($event);}};}
function selectedOriginalConflict(O\AssignmentOrderOriginalResult $r):void {assertSameValue(['conflict','target_not_current',false,null],[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->rootOriginalId()],'exact stale-selection outcome');}
$tests=[
    'replaced preflight rejects old input and accepts current82'=>static function(F $f):void {
        assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition(S::command(2,1,7002))->status()->value,'native replacement setup');$app=$f->app();$before=$f->selection->rows();$files=$f->privateFiles();$stream=new Input('must not be read');
        $r=$app->submitAssignmentOrderOriginal(selectedOriginalCommand($stream));selectedOriginalConflict($r);assertSameValue([0,1],[$stream->reads,$stream->closes],'stale preflight before PDF read');assertSameValue($files,$f->privateFiles(),'stale preflight no storage');
        $after=$f->selection->rows();foreach($before as $table=>$rows)if(!in_array($table,['fm2_assignment_order_original_requests','fm2_assignment_order_original_audits'],true))assertSameValue($rows,$after[$table],'stale request mutates terminal/audit only');
        assertSameValue([1,1],[count($after['fm2_assignment_order_original_requests']),count($after['fm2_assignment_order_original_audits'])],'stale terminal and audit');
        $retry=new Input('also unread');selectedOriginalConflict($app->submitAssignmentOrderOriginal(selectedOriginalCommand($retry)));assertSameValue([0,1],[$retry->reads,$retry->closes],'cached conflict replay');assertSameValue($after,$f->selection->rows(),'cached conflict silent');
        $accepted=$app->submitAssignmentOrderOriginal(selectedOriginalCommand(new Input(F::pdf()),2,82));assertSameValue(['accepted',1],[$accepted->status()->value,$accepted->revisionNumber()],'original for current replacement');
        $root=$f->selection->rows()['fm2_assignment_order_original_roots'][0];assertSameValue(['82','composition-82-v2','1e6e0030b9f3cca92c36a741f87331f652490391ae773386673dc32f838d8bda'],[$root['assignment_order_id'],$root['composition_identity'],$root['composition_sha256']],'current replacement exact composition');
    },
    'accepted81 correction after new pending82 retains both histories'=>static function(F $f):void {
        $app=$f->app();$first=$app->submitAssignmentOrderOriginal(selectedOriginalCommand(new Input(F::pdf())));assertSameValue('accepted',$first->status()->value,'accepted original setup');
        $c=S::command(2,1,7002);$new=new C\SelectAssignmentOrderCompositionCommand($c->requestId,C\AssignmentOrderCompositionMode::NEW_ORDER,$c->installationObjectId,$c->actorUserId,$c->installerTabIds,$c->controlEngineerUserId,$c->expectedSelectionRevision);assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition($new)->status()->value,'new prospective selection');
        $before=$f->selection->rows();$corrected=$app->submitAssignmentOrderOriginal(selectedOriginalCommand(new Input(F::pdf()),2,81,$first));assertSameValue(['accepted',$first->rootOriginalId(),2,'2026-09-03'],[$corrected->status()->value,$corrected->rootOriginalId(),$corrected->revisionNumber(),$corrected->documentDate()],'historical accepted original correction');
        $after=$f->selection->rows();foreach($before as $table=>$rows)if(!str_starts_with($table,'fm2_assignment_order_original_'))assertSameValue($rows,$after[$table],'correction leaves both selection histories/applicability untouched');
        foreach(['fm2_assignment_order_original_revisions','fm2_assignment_order_original_requests','fm2_assignment_order_original_events','fm2_assignment_order_original_audits'] as $table){assertSameValue(2,count($after[$table]),'append-only original history');assertSameValue(true,in_array($before[$table][0],$after[$table],true),'prior original fact preserved');}
        $oldRoot=$before['fm2_assignment_order_original_roots'][0];$oldRoot['current_revision_id']=$corrected->currentRevisionId();assertSameValue($oldRoot,$after['fm2_assignment_order_original_roots'][0],'only current leaf pointer changes');
        $replay=$app->submitAssignmentOrderOriginal(selectedOriginalCommand(new Input('unread')));assertSameValue(['replayed',$first->currentRevisionId()],[$replay->status()->value,$replay->currentRevisionId()],'old accepted request receipt stable');assertSameValue($after,$f->selection->rows(),'historical replay silent');
    },
    'replacement between preflight and lock prevents original commit'=>static function(F $f):void {
        $other=$f->selection->schema->source->connect($f->selection->schema->source->name);$replacement=null;$done=false;
        try {
            $selector=selectedOriginalOtherSelector($f,$other);$observer=selectedOriginalObserver(function($event)use($selector,&$done,&$replacement){if(!$done&&$event===O\AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_BEGIN){$done=true;$replacement=$selector->selectAssignmentOrderComposition(S::command(2,1,7002));}});
            $stream=new Input(F::pdf());$r=$f->app(false,$observer)->submitAssignmentOrderOriginal(selectedOriginalCommand($stream));assertSameValue('selected',$replacement?->status()->value,'other connection replacement wins');selectedOriginalConflict($r);assertSameValue(true,$stream->reads>0,'preflight and PDF preceded replacement');assertSameValue(1,$stream->closes,'input closed after conflict');
            $rows=$f->selection->rows();assertSameValue([2,0,0,1,1],[count($rows['fm2_assignment_order_selections']),count($rows['fm2_assignment_order_original_roots']),count($rows['fm2_assignment_order_original_revisions']),count($rows['fm2_assignment_order_original_requests']),count($rows['fm2_assignment_order_original_audits'])],'no crossed accepted history, conflict audited');
        }finally{$other->close();}
    },
    'original case lock makes replacement wait then reject'=>static function(F $f):void {
        $worker=null;$blocked=false;$control=$f->selection->schema->source->connect($f->selection->schema->source->name);
        try {
            $observer=selectedOriginalObserver(function($event)use($f,$control,&$worker,&$blocked){
                if($event!==O\AssignmentOrderOriginalPersistenceEvent::AFTER_COMPOSITION_ORDER_READ||$worker!==null)return;
                $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/selection_native_command_worker.php',$f->selection->schema->source->name,'2','4512','7002','','1'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new TestFailure('worker start');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$worker=['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
                aossWaitPhase($worker,'ready');if(!preg_match('/THREAD ([0-9]+)/',$worker['stdout'],$m))throw new TestFailure('worker thread');$thread=(int)$m[1];$deadline=hrtime(true)+8_000_000_000;
                do{$row=$control->query('SELECT DB,TIME,INFO FROM information_schema.PROCESSLIST WHERE ID='.$thread)->fetch_assoc();if($row!==null&&$row['DB']===$f->selection->schema->source->name&&(int)$row['TIME']>=1&&str_starts_with((string)$row['INFO'],'SELECT legacy_installation_object_id FROM `fm2_installation_cases`')&&str_ends_with($row['INFO'],'FOR UPDATE')){$blocked=true;echo "NATIVE_REPLACEMENT_BLOCKED_BY_ORIGINAL\n";return;}aossDrainWorker($worker,30000);if(!$worker['status']['running'])throw new TestFailure('replacement escaped original case lock');}while(hrtime(true)<$deadline);throw new TestFailure('replacement lock not observed');
            });
            $r=$f->app(false,$observer)->submitAssignmentOrderOriginal(selectedOriginalCommand(new Input(F::pdf())));assertSameValue([true,'accepted'],[$blocked,$r->status()->value],'original commits with replacement waiting');$result=aossReapWorker($worker,15);assertSameValue([0,''],[$result['exit'],$result['stderr']],'replacement worker exits clean');if(!preg_match('/^RESULT (.+)$/m',$result['stdout'],$m))throw new TestFailure('worker result');$loser=json_decode($m[1],true,512,JSON_THROW_ON_ERROR);assertSameValue(['conflict','original_already_accepted'],[$loser['status'],$loser['reasonCode']],'replacement observes committed original');
            $rows=$f->selection->rows();assertSameValue([1,1],[count($rows['fm2_assignment_order_selections']),count($rows['fm2_assignment_order_original_roots'])],'one composition with accepted original');
        }finally{if($worker!==null)aossCleanupWorker($worker);$control->close();}
    },
    'other-case nondisclosure and malformed same-case source'=>static function(F $f):void {
        $db=$f->selection->db;
        $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4513,4513,'needs_assignment_order','2026-08-20T09:00:00Z','2026-08-20T09:00:00Z',1)");
        $db->query('RENAME TABLE fm2_assignment_order_selection_members TO fixture_missing_members');$app=$f->app();$foreign=new Input('never read');$c=selectedOriginalCommand($foreign);
        $c=new O\SubmitAssignmentOrderOriginalCommand($c->requestId,$c->mode,4513,$c->assignmentOrderId,$c->actorUserId,$c->documentDate,$c->compositionConfirmed,null,null,null,null,$c->upload);
        $r=$app->submitAssignmentOrderOriginal($c);assertSameValue(['rejected','order_not_found',0,1],[$r->status()->value,$r->reasonCode()?->value,$foreign->reads,$foreign->closes],'other-case identity hides inaccessible source');
        $same=new Input('never read either');$r=$app->submitAssignmentOrderOriginal(selectedOriginalCommand($same,2));assertSameValue(['failed','persistence_failure',0,1],[$r->status()->value,$r->reasonCode()?->value,$same->reads,$same->closes],'same-case missing members unavailable, never fallback');
        $rows=$f->selection->rows();assertSameValue([0,0,0],[count($rows['fm2_assignment_order_original_roots']),count($rows['fm2_assignment_order_original_revisions']),count($rows['fm2_assignment_order_original_events'])],'no accepted original under unavailable source');
    },
    'selection grant never grants original upload'=>static function(F $f):void {
        $f->selection->db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id=18 AND capability='assignment_order.original.upload'");
        $f->selection->db->query('RENAME TABLE fm2_assignment_order_selections TO fixture_missing_selection');$input=new Input('never read');$r=$f->app()->submitAssignmentOrderOriginal(selectedOriginalCommand($input));
        assertSameValue(['rejected','authorization_denied',0,1],[$r->status()->value,$r->reasonCode()?->value,$input->reads,$input->closes],'original authority precedes confidential selected lookup');
        $rows=$f->selection->rows();assertSameValue([1,1],[count($rows['fm2_assignment_order_original_requests']),count($rows['fm2_assignment_order_original_audits'])],'inherited original denial contract: first terminal and audit');
        $terminal=$rows['fm2_assignment_order_original_requests'];$again=new Input('unread denial');$r=$f->app()->submitAssignmentOrderOriginal(selectedOriginalCommand($again));
        assertSameValue(['rejected','authorization_denied',0,1],[$r->status()->value,$r->reasonCode()?->value,$again->reads,$again->closes],'every denied invocation audited without confidential lookup');
        $rows=$f->selection->rows();assertSameValue($terminal,$rows['fm2_assignment_order_original_requests'],'denial terminal not overwritten');assertSameValue(2,count($rows['fm2_assignment_order_original_audits']),'second invocation adds independent audit');
    },
];
$failed=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition(S::command())->status()->value,'native initial selection');echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
