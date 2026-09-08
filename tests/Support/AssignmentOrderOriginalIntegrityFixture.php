<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalIntegrityTrace
{
    public array $calls=[];
    public function add(string $call):void{$this->calls[]=$call;}
}
if(interface_exists(O\AssignmentOrderOriginalRevisionLineageRepository::class)){
    interface OriginalIntegrityRevisionMarker extends O\AssignmentOrderOriginalRevisionLineageRepository {}
}else{
    interface OriginalIntegrityRevisionMarker {}
}
final class OriginalIntegrityRepository implements O\AssignmentOrderOriginalRepository,O\AssignmentOrderOriginalAssignmentLineageRepository,OriginalIntegrityRevisionMarker
{
    public array $acceptedCalls=[];public array $attemptCalls=[];public array $accepted=[];public array $attempts=[];public array $terminal=[];
    public int $terminalCalls=0;public int $fingerprintCalls=0;public int $lineageCalls=0;public array $revisionQueries=[];
    public function __construct(public array $options,private OriginalIntegrityTrace $trace){}
    private function selected(mixed $value):mixed{if($value instanceof \Throwable)throw $value;return $value;}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup
    {
        ++$this->terminalCalls;$this->trace->add('request');
        if(array_key_exists('terminal',$this->options))return $this->selected($this->options['terminal']);
        return new OriginalIntegrityLookup(isset($this->terminal[$requestId])?O\AssignmentOrderOriginalLookupStatus::FOUND:O\AssignmentOrderOriginalLookupStatus::NOT_FOUND,$this->terminal[$requestId]??null);
    }
    public function findAcceptedFingerprint(string $fingerprint):O\AssignmentOrderOriginalResultLookup
    {
        ++$this->fingerprintCalls;$this->trace->add('fingerprint');
        return $this->selected($this->options['fingerprints'][$this->fingerprintCalls-1]??$this->options['fingerprint']??new OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND));
    }
    public function findLineage(string $rootOriginalId):O\AssignmentOrderOriginalLineageLookup
    {++$this->lineageCalls;$this->trace->add('lineage');return $this->selected($this->options['lineages'][$this->lineageCalls-1]??$this->options['lineage']??new OriginalIntegrityLineage());}
    public function findLineageForAssignmentOrder(int $installationCaseId,int $assignmentOrderId):O\AssignmentOrderOriginalLineageLookup
    {++$this->lineageCalls;$this->trace->add('assignment-lineage');return $this->selected($this->options['assignmentLineages'][$this->lineageCalls-1]??$this->options['assignmentLineage']??OriginalIntegrityLineage::absent());}
    public function findLineageForRevision(string $revisionId):O\AssignmentOrderOriginalLineageLookup
    {$this->revisionQueries[]=$revisionId;$this->trace->add('revision-owner');return $this->selected($this->options['revisionLineage']??OriginalIntegrityLineage::absent());}
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit):O\AssignmentOrderOriginalCommitStatus
    {
        $this->trace->add('accepted');$this->acceptedCalls[]=$commit;
        $outcome=$this->options['acceptedOutcome']??O\AssignmentOrderOriginalCommitStatus::COMMITTED;
        if($outcome===O\AssignmentOrderOriginalCommitStatus::COMMITTED||($this->options['durableAccepted']??false)){$this->accepted[]=$commit;$this->terminal[$commit->requestId]=new OriginalIntegrityResult([
            'request'=>$commit->requestId,'root'=>$commit->rootOriginalId,'revision'=>$commit->newRevisionId,'number'=>$commit->newRevisionNumber,
            'date'=>$commit->documentDate,'sha'=>$commit->pdfSha256,'size'=>$commit->byteSize,'at'=>$commit->uploadedAt]);}
        return $this->selected($outcome);
    }
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit):O\AssignmentOrderOriginalCommitStatus
    {
        $this->trace->add('attempt');$this->attemptCalls[]=$commit;
        $outcome=$this->options['attemptOutcome']??O\AssignmentOrderOriginalCommitStatus::COMMITTED;
        if($outcome===O\AssignmentOrderOriginalCommitStatus::COMMITTED||($this->options['durableAttempt']??false)){
            $this->attempts[]=$commit;$r=OriginalIntegrityResult::rejected($commit->reason);$r->values['request']=$commit->requestId;$r->values['status']=$commit->status;$this->terminal[$commit->requestId]=$r;
        }
        return $this->selected($outcome);
    }
    public function hasCommittedContent(string $opaqueIdentity):O\AssignmentOrderOriginalReferenceLookup{throw new \LogicException('unexpected maintenance');}
    public function evidenceCanonicalJson(int $caseId,int $orderId):string{return json_encode(['accepted'=>$this->accepted,'attempts'=>$this->attempts],JSON_THROW_ON_ERROR);}
}
class OriginalIntegrityWithoutAssignment implements O\AssignmentOrderOriginalRepository
{
    public function __construct(protected OriginalIntegrityRepository $inner){}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup{return $this->inner->findTerminalRequest($requestId);}
    public function findAcceptedFingerprint(string $fingerprint):O\AssignmentOrderOriginalResultLookup{return $this->inner->findAcceptedFingerprint($fingerprint);}
    public function findLineage(string $rootOriginalId):O\AssignmentOrderOriginalLineageLookup{return $this->inner->findLineage($rootOriginalId);}
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit):O\AssignmentOrderOriginalCommitStatus{return $this->inner->commitAccepted($commit);}
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit):O\AssignmentOrderOriginalCommitStatus{return $this->inner->commitAttempt($commit);}
    public function hasCommittedContent(string $opaqueIdentity):O\AssignmentOrderOriginalReferenceLookup{return $this->inner->hasCommittedContent($opaqueIdentity);}
    public function evidenceCanonicalJson(int $caseId,int $orderId):string{return $this->inner->evidenceCanonicalJson($caseId,$orderId);}
}
final class OriginalIntegrityWithoutRevision extends OriginalIntegrityWithoutAssignment implements O\AssignmentOrderOriginalAssignmentLineageRepository
{
    public function findLineageForAssignmentOrder(int $installationCaseId,int $assignmentOrderId):O\AssignmentOrderOriginalLineageLookup{return $this->inner->findLineageForAssignmentOrder($installationCaseId,$assignmentOrderId);}
}
if(interface_exists(O\AssignmentOrderOriginalFreshTerminalReaderFactory::class)){
    interface OriginalIntegrityFreshFactoryMarker extends O\AssignmentOrderOriginalFreshTerminalReaderFactory {}
    interface OriginalIntegrityFreshReaderMarker extends O\AssignmentOrderOriginalFreshTerminalReader {}
}else{
    interface OriginalIntegrityFreshFactoryMarker {}
    interface OriginalIntegrityFreshReaderMarker {}
}
final class OriginalIntegrityFreshFactory implements OriginalIntegrityFreshFactoryMarker
{
    public int $opens=0;public OriginalIntegrityFreshReader $reader;
    public function __construct(private array $options,OriginalIntegrityRepository $repository,private OriginalIntegrityTrace $trace)
    {$this->reader=new OriginalIntegrityFreshReader($options,$repository,$trace);}
    public function open():O\AssignmentOrderOriginalFreshTerminalReaderOpenResult
    {
        ++$this->opens;$this->trace->add('fresh.open');
        if(($this->options['freshOpen']??'')==='throw')throw new \RuntimeException('fresh open');
        return ($this->options['freshOpen']??'')==='unavailable'?O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::unavailable():O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::opened($this->reader);
    }
}
final class OriginalIntegrityFreshReader implements OriginalIntegrityFreshReaderMarker
{
    public int $reads=0;public int $closes=0;
    public function __construct(private array $options,private OriginalIntegrityRepository $repository,private OriginalIntegrityTrace $trace){}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup
    {
        ++$this->reads;$this->trace->add('fresh.read');
        if(array_key_exists('freshLookup',$this->options)){$value=$this->options['freshLookup'];if($value instanceof \Throwable)throw $value;return $value;}
        return new OriginalIntegrityLookup(isset($this->repository->terminal[$requestId])?O\AssignmentOrderOriginalLookupStatus::FOUND:O\AssignmentOrderOriginalLookupStatus::NOT_FOUND,$this->repository->terminal[$requestId]??null);
    }
    public function close():O\AssignmentOrderOriginalFreshReaderCloseStatus
    {
        ++$this->closes;$this->trace->add('fresh.close');
        if(($this->options['mutateOnClose']??null) instanceof OriginalIntegrityResult)$this->options['mutateOnClose']->values['at']='mutated-after-reader-close';
        if(($this->options['freshClose']??'')==='throw')throw new \RuntimeException('fresh close');
        return ($this->options['freshClose']??'')==='failed'?O\AssignmentOrderOriginalFreshReaderCloseStatus::FAILED:O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED;
    }
}
final class OriginalIntegrityFixture
{
    public static function pdf():string{return (string)base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);}
    public OriginalIntegrityTrace $trace;public OriginalIntegrityRepository $repository;public OriginalIntegrityFreshFactory $fresh;
    public OriginalIntegrityStorage $storage;public OriginalIntegrityClock $clock;public OriginalIntegrityIds $ids;
    public OriginalIntegrityObservers $observers;public O\AssignmentOrderOriginalApplication $application;
    public O\SubmitAssignmentOrderOriginalCommand $command;public OriginalIntegrityStream $stream;
    public int $compositionCalls=0;public int $authorizationCalls=0;
    public function __construct(public array $options=[])
    {
        $this->trace=new OriginalIntegrityTrace();$this->repository=new OriginalIntegrityRepository($options,$this->trace);
        $this->fresh=new OriginalIntegrityFreshFactory($options,$this->repository,$this->trace);$this->storage=new OriginalIntegrityStorage($this->trace);
        $this->clock=new OriginalIntegrityClock($options['clock']??'2026-09-02T09:15:30Z',$this->trace);
        $this->ids=new OriginalIntegrityIds($options['correction']??false);
        $this->observers=new OriginalIntegrityObservers($this->trace,$options);
        $authorizer=new class($this) implements O\AssignmentOrderOriginalAuthorizer{
            public function __construct(private OriginalIntegrityFixture $f){}
            public function authorize(int $actorUserId,string $exactCapability):O\AssignmentOrderOriginalAuthorizationStatus{
                ++$this->f->authorizationCalls;$this->f->trace->add('authorize');$value=$this->f->options['authorization']??O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED;
                if($value instanceof \Throwable)throw $value;return $value;
            }
        };
        $reader=new class($this) implements O\AssignmentOrderCompositionReader{
            public function __construct(private OriginalIntegrityFixture $f){}
            public function find(int $caseId,int $orderId):O\AssignmentOrderCompositionSnapshot{
                ++$this->f->compositionCalls;$this->f->trace->add('composition');
                $value=$this->f->options['composition']??new O\AssignmentOrderCompositionSnapshot(O\AssignmentOrderCompositionLookupStatus::FOUND,4512,81,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',[7001,7002],31);
                if($value instanceof \Throwable)throw $value;return $value;
            }
        };
        // Extra positional arguments are ignored by the old userland constructor. The separate
        // API test requires the named trailing dependency; after GREEN this is its real factory.
        $repositoryPort=($options['withoutAssignment']??false)?new OriginalIntegrityWithoutAssignment($this->repository):(($options['withoutRevision']??false)?new OriginalIntegrityWithoutRevision($this->repository):$this->repository);
        $d=new O\AssignmentOrderOriginalDependencies($authorizer,$reader,$this->clock,$this->ids,($options['inspector']??new OriginalIntegrityInspector()),$this->storage,$repositoryPort,
            $this->observers,$this->observers,$this->observers,$this->observers,$this->observers,($options['withoutFresh']??false)?null:$this->fresh);
        $this->application=O\AssignmentOrderOriginalVerificationFactory::create($d);
        $pdf=self::pdf();
        $this->stream=new OriginalIntegrityStream($options['bytes']??$pdf,$this->trace,$options['closeThrows']??false);$correction=$options['correction']??false;
        $this->command=new O\SubmitAssignmentOrderOriginalCommand($options['request']??'00000000-0000-4000-8000-000000000001',
            $correction?O\AssignmentOrderOriginalMode::CORRECTION:O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,$options['date']??($correction?'2026-09-02':'2026-09-01'),$options['confirmed']??true,
            $correction?($options['root']??'original-0001'):null,$correction?($options['target']??'revision-0001'):null,$correction?($options['expected']??'revision-0001'):null,$correction?'Исправлена дата':null,
            new O\AssignmentOrderOriginalUpload($this->stream,'original.pdf','application/pdf'));
    }
    public function run():O\AssignmentOrderOriginalResult{return $this->application->submitAssignmentOrderOriginal($this->command);}
}
