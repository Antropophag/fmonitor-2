<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalShapeAuthorizer implements O\AssignmentOrderOriginalAuthorizer
{
    public array $calls=[];
    public function __construct(private bool $denied){}
    public function authorize(int $actorUserId,string $exactCapability):O\AssignmentOrderOriginalAuthorizationStatus
    {$this->calls[]=[$actorUserId,$exactCapability];return !$this->denied&&$actorUserId===18&&in_array($exactCapability,['assignment_order.original.upload','assignment_order.original.correct'],true)?O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED:O\AssignmentOrderOriginalAuthorizationStatus::DENIED;}
}
final class OriginalShapeComposition implements O\AssignmentOrderCompositionReader
{
    public int $calls=0;
    public function find(int $caseId,int $orderId):O\AssignmentOrderCompositionSnapshot
    {++$this->calls;return new O\AssignmentOrderCompositionSnapshot(O\AssignmentOrderCompositionLookupStatus::FOUND,$caseId,$orderId,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',[7001,7002],31);}
}
final class OriginalShapeRepository implements O\AssignmentOrderOriginalRepository
{
    public array $calls=[];
    public OriginalDynamicRepository $inner;
    public function __construct(private bool $terminalHit,bool $correction)
    {
        $this->inner=new OriginalDynamicRepository(correction:$correction);
        if($terminalHit)$this->inner->accepted[]=new O\AssignmentOrderOriginalAcceptedCommit(
            '00000000-0000-4000-8000-000000000001','dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d',O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,'original-0001','revision-0001',1,null,null,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5','2026-09-01','2026-09-02T09:15:30Z','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'private-content-0001',null,'assignment_order_original_accepted',
        );
    }
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup
    {
        $this->calls[]='terminal';
        return $this->terminalHit?new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::FOUND,new O\AssignmentOrderOriginalResultValue(O\AssignmentOrderOriginalStatus::ACCEPTED,null,false,$requestId,'original-0001','revision-0001',1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z')):$this->inner->findTerminalRequest($requestId);
    }
    public function findAcceptedFingerprint(string $fingerprint):O\AssignmentOrderOriginalResultLookup{$this->calls[]='fingerprint';return $this->inner->findAcceptedFingerprint($fingerprint);}
    public function findLineage(string $rootOriginalId):O\AssignmentOrderOriginalLineageLookup{$this->calls[]='lineage';return $this->inner->findLineage($rootOriginalId);}
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit):O\AssignmentOrderOriginalCommitStatus{$this->calls[]='accepted';return $this->inner->commitAccepted($commit);}
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit):O\AssignmentOrderOriginalCommitStatus{$this->calls[]='attempt';return $this->inner->commitAttempt($commit);}
    public function hasCommittedContent(string $opaqueIdentity):O\AssignmentOrderOriginalReferenceLookup{throw new \LogicException('unexpected maintenance');}
    public function evidenceCanonicalJson(int $caseId,int $orderId):string{return $this->inner->evidenceCanonicalJson($caseId,$orderId);}
}
final class OriginalShapeFixture
{
    public OriginalShapeAuthorizer $authorizer;
    public OriginalShapeComposition $compositions;
    public OriginalShapeRepository $repository;
    public OriginalDynamicClock $clock;
    public OriginalDynamicIds $ids;
    public OriginalDynamicStorage $storage;
    public OriginalDynamicStream $stream;
    public AssignmentOrderOriginalInitialInspector $inspector;
    public AssignmentOrderOriginalInitialObservers $observers;
    public O\AssignmentOrderOriginalApplication $application;
    public function __construct(string $pdf,private bool $correction=false,bool $terminalHit=false,bool $denied=false,?string $generatedRoot='original-0001',?string $generatedRevision=null)
    {
        $this->authorizer=new OriginalShapeAuthorizer($denied);$this->compositions=new OriginalShapeComposition();
        $this->repository=new OriginalShapeRepository($terminalHit,$correction);$this->clock=new OriginalDynamicClock('2026-09-02T09:15:30Z');
        $this->ids=new OriginalDynamicIds([new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,$generatedRoot)],[new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,$generatedRevision??($correction?'revision-0002':'revision-0001'))]);
        $this->storage=new OriginalDynamicStorage();$this->stream=new OriginalDynamicStream($pdf);$this->inspector=new AssignmentOrderOriginalInitialInspector();$this->observers=new AssignmentOrderOriginalInitialObservers();
        $this->application=O\AssignmentOrderOriginalVerificationFactory::create(new O\AssignmentOrderOriginalDependencies($this->authorizer,$this->compositions,$this->clock,$this->ids,$this->inspector,$this->storage,$this->repository,$this->observers,$this->observers,$this->observers,$this->observers,$this->observers));
    }
    public function command(array $changes=[],string $filename='original.pdf'):O\SubmitAssignmentOrderOriginalCommand
    {
        $values=['requestId'=>'00000000-0000-4000-8000-000000000001','mode'=>$this->correction?O\AssignmentOrderOriginalMode::CORRECTION:O\AssignmentOrderOriginalMode::INITIAL,'installationCaseId'=>4512,'assignmentOrderId'=>81,'actorUserId'=>18,'documentDate'=>$this->correction?'2026-09-02':'2026-09-01','compositionConfirmed'=>true,'rootOriginalId'=>$this->correction?'original-0001':null,'targetRevisionId'=>$this->correction?'revision-0001':null,'expectedCurrentRevisionId'=>$this->correction?'revision-0001':null,'correctionReason'=>$this->correction?'Исправлена дата':null,'upload'=>new O\AssignmentOrderOriginalUpload($this->stream,$filename,'application/pdf')];
        return new O\SubmitAssignmentOrderOriginalCommand(...array_replace($values,$changes));
    }
    public function businessCounters():array
    {return [count($this->authorizer->calls),count($this->repository->calls),$this->compositions->calls,$this->clock->calls,$this->storage->beginCalls,$this->ids->rootCalls,$this->ids->revisionCalls,$this->inspector->inspected,count($this->observers->lifecycle),count($this->observers->storage),$this->observers->deliveryCalls];}
}
