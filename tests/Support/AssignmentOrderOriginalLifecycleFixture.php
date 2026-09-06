<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalLifecycleTrace
{
    public array $calls=[];public array $counts=[];
    public function __construct(public array $faults=[]){}
    public function call(string $name):void{$this->calls[]=$name;$this->counts[$name]=($this->counts[$name]??0)+1;}
    public function fails(string $name):bool{return in_array($name,$this->faults,true);}
    public function throwing(string $name):void{if($this->fails($name))throw new \RuntimeException('synthetic '.$name);}
}
final class OriginalLifecycleStream implements O\AssignmentOrderOriginalByteStream
{
    private int $offset=0;public int $readCalls=0;public int $closeCalls=0;
    public function __construct(private string $bytes,private OriginalLifecycleTrace $trace){}
    public function read(int $maximumBytes):O\AssignmentOrderOriginalStreamRead
    {
        ++$this->readCalls;$this->trace->call('stream.read:'.$maximumBytes);$this->trace->throwing('read_throw');
        if($this->trace->fails('read_empty')){if($this->readCalls>1)throw new \RuntimeException('no-progress sentinel');return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::BYTES,'');}
        if($this->trace->fails('read_eof_payload'))return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::EOF,'x');
        if($this->trace->fails('read_failed_payload'))return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::FAILED,'x');
        if($this->trace->fails('read_oversized')){if($this->readCalls>1)return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::EOF,'');return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::BYTES,str_repeat('A',65537));}
        if($this->offset===strlen($this->bytes))return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::EOF,'');
        $value=substr($this->bytes,$this->offset,$maximumBytes);$this->offset+=strlen($value);return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::BYTES,$value);
    }
    public function close():void{++$this->closeCalls;$this->trace->call('stream.close');$this->trace->throwing('stream_close_throw');}
}
final class OriginalLifecycleContent implements O\AssignmentOrderOriginalPrivateContent
{
    public array $gets=[];
    public function __construct(private OriginalLifecycleTrace $trace,private string $digest,private int $size){}
    public function opaqueIdentity():string{$this->gets[]='id';$this->trace->throwing('content_id_throw');return $this->trace->fails('content_bad_id')?'bad/id':'private-content-0001';}
    public function sha256():string{$this->gets[]='sha';$this->trace->throwing('content_sha_throw');return $this->trace->fails('content_bad_sha')?str_repeat('0',64):$this->digest;}
    public function byteSize():int{$this->gets[]='size';$this->trace->throwing('content_size_throw');return $this->trace->fails('content_bad_size')?$this->size+1:$this->size;}
}
final class OriginalLifecycleLease implements O\AssignmentOrderOriginalPrivateContentLease
{
    public int $releaseCalls=0;public int $statusCalls=0;public int $contentCalls=0;
    public OriginalLifecycleContent $value;
    public function __construct(private OriginalLifecycleTrace $trace,string $sha,int $size){$this->value=new OriginalLifecycleContent($trace,$sha,$size);}
    public function status():O\AssignmentOrderOriginalStorageStatus{++$this->statusCalls;$this->trace->throwing('lease_status_throw');return $this->trace->fails('lease_bad_status')?O\AssignmentOrderOriginalStorageStatus::FAILED:O\AssignmentOrderOriginalStorageStatus::OK;}
    public function content():?O\AssignmentOrderOriginalPrivateContent{++$this->contentCalls;$this->trace->throwing('lease_content_throw');if($this->contentCalls>1&&$this->trace->fails('content_second_call_trap'))throw new \RuntimeException('content getter repeated');return $this->trace->fails('lease_null_content')?null:$this->value;}
    public function release():O\AssignmentOrderOriginalStorageStatus{++$this->releaseCalls;$this->trace->call('lease.release');$this->trace->throwing('release_throw');return $this->trace->fails('release_failed')?O\AssignmentOrderOriginalStorageStatus::FAILED:O\AssignmentOrderOriginalStorageStatus::OK;}
}
final class OriginalLifecycleOutcome implements O\AssignmentOrderOriginalStorageOutcome
{
    public int $statusCalls=0;public int $leaseCalls=0;
    public function __construct(private OriginalLifecycleTrace $trace,public OriginalLifecycleLease $value){}
    public function status():O\AssignmentOrderOriginalStorageStatus{++$this->statusCalls;return $this->trace->fails('outcome_failed_with_lease')?O\AssignmentOrderOriginalStorageStatus::FAILED:($this->trace->fails('outcome_already')?O\AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED:O\AssignmentOrderOriginalStorageStatus::OK);}
    public function lease():?O\AssignmentOrderOriginalPrivateContentLease{++$this->leaseCalls;return $this->trace->fails('outcome_null_lease')?null:$this->value;}
}
final class OriginalLifecycleStage implements O\AssignmentOrderOriginalPrivateStage
{
    private string $bytes='';public int $abortCalls=0;public int $closeCalls=0;public int $finalizeCalls=0;public ?OriginalLifecycleOutcome $outcome=null;
    public function __construct(private OriginalLifecycleTrace $trace){}
    public function write(string $chunk):O\AssignmentOrderOriginalStorageStatus{$this->trace->call('stage.write:'.strlen($chunk));$this->trace->throwing('write_throw');if($this->trace->fails('write_failed'))return O\AssignmentOrderOriginalStorageStatus::FAILED;$this->bytes.=$chunk;return O\AssignmentOrderOriginalStorageStatus::OK;}
    public function completedBytesForInspection():string{$this->trace->call('stage.completed');$this->trace->throwing('completed_throw');return $this->bytes;}
    public function finalize(string $sha256,int $byteSize):O\AssignmentOrderOriginalStorageOutcome{++$this->finalizeCalls;$this->trace->call('stage.finalize');$this->trace->throwing('finalize_throw');return $this->outcome=new OriginalLifecycleOutcome($this->trace,new OriginalLifecycleLease($this->trace,$sha256,$byteSize));}
    public function abort():O\AssignmentOrderOriginalStorageStatus{++$this->abortCalls;$this->trace->call('stage.abort');$this->trace->throwing('abort_throw');return $this->trace->fails('abort_failed')?O\AssignmentOrderOriginalStorageStatus::FAILED:O\AssignmentOrderOriginalStorageStatus::OK;}
    public function close():void{++$this->closeCalls;$this->trace->call('stage.close');$this->trace->throwing('stage_close_throw');}
}
final class OriginalLifecycleStorage implements O\AssignmentOrderOriginalPrivateStorage
{
    public ?OriginalLifecycleStage $stage=null;public int $beginCalls=0;
    public function __construct(private OriginalLifecycleTrace $trace){}
    public function beginStage():O\AssignmentOrderOriginalPrivateStage{++$this->beginCalls;$this->trace->call('stage.begin');$this->trace->throwing('begin_throw');return $this->stage=new OriginalLifecycleStage($this->trace);}
    public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):O\AssignmentOrderOriginalOrphanPage{throw new \LogicException('unexpected maintenance');}
    public function acquireDigestLock(string $opaqueIdentity):O\AssignmentOrderOriginalDigestLock{throw new \LogicException('unexpected maintenance');}
    public function deleteLocked(O\AssignmentOrderOriginalDigestLock $lock):O\AssignmentOrderOriginalStorageStatus{throw new \LogicException('unexpected maintenance');}
    public function inventoryCanonicalJson():string{return '{"stages":[],"finalized":[]}';}
}
final class OriginalLifecycleObservers implements O\AssignmentOrderOriginalLifecycleObserver,O\AssignmentOrderOriginalStorageObserver,O\AssignmentOrderOriginalSafeLogObserver,O\AssignmentOrderOriginalFaultInjector,O\AssignmentOrderOriginalResultDeliveryObserver
{
    public array $logs=[];public int $deliveryCalls=0;
    public function __construct(private OriginalLifecycleTrace $trace){}
    public function observe(O\AssignmentOrderOriginalLifecycleEvent|O\AssignmentOrderOriginalStorageEvent $event,?string $opaqueIdentity=null):void
    {$this->trace->call(($event instanceof O\AssignmentOrderOriginalLifecycleEvent?'lifecycle:':'storage:').$event->value.($event instanceof O\AssignmentOrderOriginalStorageEvent?':'.($opaqueIdentity??'null'):''));$this->trace->throwing('observer:'.$event->value);}
    public function before(O\AssignmentOrderOriginalFaultPoint $point):void{}
    public function record(string $event,array $safeFields):void{$this->trace->call('log:'.$safeFields['phase']);$this->logs[]=[$event,$safeFields];$this->trace->throwing('log_throw');}
    public function afterCommitBeforeReturn(O\AssignmentOrderOriginalResult $result):void{++$this->deliveryCalls;$this->trace->call('delivery');$this->trace->throwing('delivery_throw');}
}
final class OriginalLifecycleRepository implements O\AssignmentOrderOriginalRepository,O\AssignmentOrderOriginalAssignmentLineageRepository
{
    public int $commitCalls=0;public int $terminalCalls=0;public int $fingerprintCalls=0;public array $accepted=[];public array $attempts=[];public array $winnerFacts=[];
    private array $terminal=[];
    public function __construct(private OriginalLifecycleTrace $trace,private string $mode='normal')
    {
        if(in_array($mode,['terminal_replay','fingerprint_replay'],true)){
            $prior=self::seed($mode==='terminal_replay'?'00000000-0000-4000-8000-000000000001':'00000000-0000-4000-8000-000000000999');
            $this->winnerFacts[]=$prior;$this->terminal[$prior->requestId]=self::result($prior);
        }elseif(str_starts_with($mode,'conflict_')&&$mode!=='conflict_replay')$this->winnerFacts[]=self::seed('00000000-0000-4000-8000-000000000999','original-0099','revision-0099','2026-08-31');
    }
    private static function seed(string $request,string $root='original-0001',string $revision='revision-0001',string $date='2026-09-01'):O\AssignmentOrderOriginalAcceptedCommit
    {
        $members=['initial','4512','81','','','',$date,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784'];$bytes='';foreach($members as $member)$bytes.=pack('N',strlen($member)).$member;
        return new O\AssignmentOrderOriginalAcceptedCommit($request,hash('sha256',$bytes),O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,$root,$revision,1,null,null,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',$date,'2026-09-02T09:15:30Z','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'private-content-0001',null,'assignment_order_original_accepted');
    }
    private static function result(O\AssignmentOrderOriginalAcceptedCommit $c):O\AssignmentOrderOriginalResult
    {return new O\AssignmentOrderOriginalResultValue(O\AssignmentOrderOriginalStatus::ACCEPTED,null,false,$c->requestId,$c->rootOriginalId,$c->newRevisionId,$c->newRevisionNumber,$c->documentDate,$c->pdfSha256,$c->byteSize,$c->uploadedAt);}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup
    {
        ++$this->terminalCalls;$this->trace->call('request');
        if($this->commitCalls>0){
            if($this->mode==='unknown_recovery_throw')throw new \RuntimeException('fresh recovery unavailable');
            if($this->mode==='unknown_result_getter_throw')return new class implements O\AssignmentOrderOriginalResultLookup{public function status():O\AssignmentOrderOriginalLookupStatus{return O\AssignmentOrderOriginalLookupStatus::FOUND;}public function result():?O\AssignmentOrderOriginalResult{throw new \RuntimeException('fresh result unavailable');}};
            if(in_array($this->mode,['unknown_unavailable','commit_throw_unavailable'],true))return new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE);
        }
        return isset($this->terminal[$requestId])?new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::FOUND,$this->terminal[$requestId]):new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND);
    }
    public function findAcceptedFingerprint(string $fingerprint):O\AssignmentOrderOriginalResultLookup
    {
        ++$this->fingerprintCalls;$this->trace->call('fingerprint:'.$fingerprint);
        if($this->commitCalls>0&&$this->mode==='conflict_fingerprint_throw')throw new \RuntimeException('fingerprint reread');
        if($this->commitCalls>0&&$this->mode==='conflict_result_getter_throw')return new class implements O\AssignmentOrderOriginalResultLookup{public function status():O\AssignmentOrderOriginalLookupStatus{return O\AssignmentOrderOriginalLookupStatus::FOUND;}public function result():?O\AssignmentOrderOriginalResult{throw new \RuntimeException('winner result unavailable');}};
        foreach($this->winnerFacts as $c)if($c->fingerprint===$fingerprint)return new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::FOUND,self::result($c));
        return new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND);
    }
    public function findLineage(string $rootOriginalId):O\AssignmentOrderOriginalLineageLookup{throw new \LogicException('initial must read exact assignment lineage');}
    public function findLineageForAssignmentOrder(int $installationCaseId,int $assignmentOrderId):O\AssignmentOrderOriginalLineageLookup
    {
        $this->trace->call('lineage');if($this->mode==='conflict_lineage_throw')throw new \RuntimeException('lineage reread');
        if($installationCaseId!==4512||$assignmentOrderId!==81||!$this->winnerFacts)throw new \LogicException('unseeded lineage');$c=$this->winnerFacts[0];
        return new O\AssignmentOrderOriginalMariaDbLineage(O\AssignmentOrderOriginalLookupStatus::FOUND,['root_original_id'=>$c->rootOriginalId,'current_revision_id'=>$c->newRevisionId,'current_revision_number'=>1,'composition_identity'=>$c->compositionIdentity,'composition_sha256'=>$c->compositionSha256,'current_document_date'=>$c->documentDate,'current_pdf_sha256'=>$c->pdfSha256],[$c->newRevisionId]);
    }
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit):O\AssignmentOrderOriginalCommitStatus
    {
        ++$this->commitCalls;$this->trace->call('commit');
        if(str_starts_with($this->mode,'conflict_')){
            if($this->mode==='conflict_replay')$this->winnerFacts[]=self::seed('00000000-0000-4000-8000-000000000999','original-0099','revision-0099');
            return O\AssignmentOrderOriginalCommitStatus::CONFLICT;
        }
        if(!in_array($this->mode,['rolled_back','unknown_not_found','commit_throw_not_found'],true)){$this->accepted[]=$commit;$this->terminal[$commit->requestId]=self::result($commit);}
        if(str_starts_with($this->mode,'commit_throw_'))throw new \RuntimeException('ambiguous commit');
        if(str_starts_with($this->mode,'unknown_'))return O\AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
        return $this->mode==='rolled_back'?O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK:O\AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit):O\AssignmentOrderOriginalCommitStatus
    {$this->trace->call('audit');$this->attempts[]=$commit;return O\AssignmentOrderOriginalCommitStatus::COMMITTED;}
    public function hasCommittedContent(string $opaqueIdentity):O\AssignmentOrderOriginalReferenceLookup{throw new \LogicException('unexpected maintenance');}
    public function evidenceCanonicalJson(int $caseId,int $orderId):string{return json_encode(['accepted'=>$this->accepted,'winner'=>$this->winnerFacts,'attempts'=>$this->attempts],JSON_THROW_ON_ERROR);}
}
final class OriginalLifecycleFixture
{
    public OriginalLifecycleTrace $trace;public OriginalLifecycleStorage $storage;public OriginalLifecycleObservers $observers;public OriginalLifecycleRepository $repository;public OriginalLifecycleStream $stream;public O\AssignmentOrderOriginalApplication $application;
    public function __construct(private string $pdf,array $faults=[],string $mode='normal')
    {
        $this->trace=$t=new OriginalLifecycleTrace($faults);$this->storage=new OriginalLifecycleStorage($t);$this->observers=new OriginalLifecycleObservers($t);$this->repository=new OriginalLifecycleRepository($t,$mode);
        $auth=new class($t) implements O\AssignmentOrderOriginalAuthorizer{public function __construct(private OriginalLifecycleTrace $t){}public function authorize(int $actorUserId,string $exactCapability):O\AssignmentOrderOriginalAuthorizationStatus{$this->t->call('authorize');return $actorUserId===18&&$exactCapability==='assignment_order.original.upload'?O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED:O\AssignmentOrderOriginalAuthorizationStatus::DENIED;}};
        $composition=new class($t) implements O\AssignmentOrderCompositionReader{public function __construct(private OriginalLifecycleTrace $t){}public function find(int $caseId,int $orderId):O\AssignmentOrderCompositionSnapshot{$this->t->call('composition');return new O\AssignmentOrderCompositionSnapshot(O\AssignmentOrderCompositionLookupStatus::FOUND,$caseId,$orderId,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',[7001,7002],31);}};
        $clock=new class($t) implements O\AssignmentOrderOriginalClock{public function __construct(private OriginalLifecycleTrace $t){}public function nowUtc():string{$this->t->call('clock');return '2026-09-02T09:15:30Z';}};
        $ids=new class($t) implements O\AssignmentOrderOriginalIdSource{public function __construct(private OriginalLifecycleTrace $t){}public function nextRootId():O\AssignmentOrderOriginalIdResult{$this->t->call('id.root');return new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,'original-0001');}public function nextRevisionId():O\AssignmentOrderOriginalIdResult{$this->t->call('id.revision');return new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,'revision-0001');}};
        $inspector=new class($t) implements O\AssignmentOrderOriginalPdfInspector{public function __construct(private OriginalLifecycleTrace $t){}public function inspect(string $completedBytes):O\AssignmentOrderOriginalPdfInspection{$this->t->call('inspect');$this->t->throwing('inspector_throw');if($this->t->fails('inspector_failed'))return O\AssignmentOrderOriginalPdfInspection::failed();return $this->t->fails('invalid_pdf')?O\AssignmentOrderOriginalPdfInspection::invalid():O\AssignmentOrderOriginalPdfInspection::passive();}public function algorithmId():string{return 'fmonitor-passive-pdf-v1';}};
        $this->application=O\AssignmentOrderOriginalVerificationFactory::create(new O\AssignmentOrderOriginalDependencies($auth,$composition,$clock,$ids,$inspector,$this->storage,$this->repository,$this->observers,$this->observers,$this->observers,$this->observers,$this->observers));
    }
    public function command():O\SubmitAssignmentOrderOriginalCommand
    {$this->stream=new OriginalLifecycleStream($this->pdf,$this->trace);return new O\SubmitAssignmentOrderOriginalCommand('00000000-0000-4000-8000-000000000001',O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,'2026-09-01',true,null,null,null,null,new O\AssignmentOrderOriginalUpload($this->stream,'original.pdf','application/pdf'));}
}
