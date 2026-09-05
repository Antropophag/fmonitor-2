<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialProcessState.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialFixture.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalDynamicPortsFixture.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
// Active UPLOAD-001 sections ports/total boundary; command-v3 findings 01–03.
$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
assertSameValue(327,strlen($pdf),'literal PDF bytes');
assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',$pdf),'literal PDF hash');
function dynamicTuple(O\AssignmentOrderOriginalResult $r):array{return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];}
function dynamicFailure(string $request):array{return ['failed','persistence_failure',true,$request,null,null,null,null,null,null,null];}
function dynamicId(string $status,?string $id):O\AssignmentOrderOriginalIdResult
{
    $case=O\AssignmentOrderOriginalIdStatus::tryFrom($status);
    if($case===null)throw new TestFailure('INTENDED_RED: approved public ID status absent: '.$status);
    return new O\AssignmentOrderOriginalIdResult($case,$id);
}
function dynamicGraph(array $roots,array $revisions,bool $correction=false,string|Throwable $time='2026-09-02T09:15:30Z',bool $unavailable=false,string $date='2026-09-01'):array
{
    global $pdf;
    $request='00000000-0000-4000-8000-000000000001';
    $state=new S\AssignmentOrderOriginalInitialProcessState();$repo=new S\OriginalDynamicRepository($unavailable,$correction);
    $ids=new S\OriginalDynamicIds($roots,$revisions);$clock=new S\OriginalDynamicClock($time);$storage=new S\OriginalDynamicStorage();$observers=new S\AssignmentOrderOriginalInitialObservers();$stream=new S\OriginalDynamicStream($pdf);
    $authorizer=new class implements O\AssignmentOrderOriginalAuthorizer {
        public function authorize(int $actorUserId,string $exactCapability):O\AssignmentOrderOriginalAuthorizationStatus { return $actorUserId===18&&in_array($exactCapability,['assignment_order.original.upload','assignment_order.original.correct'],true)?O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED:O\AssignmentOrderOriginalAuthorizationStatus::DENIED; }
    };
    $app=O\AssignmentOrderOriginalVerificationFactory::create(new O\AssignmentOrderOriginalDependencies($authorizer,new S\AssignmentOrderOriginalInitialCompositionReader($state),$clock,$ids,new S\AssignmentOrderOriginalInitialInspector(),$storage,$repo,$observers,$observers,$observers,$observers,$observers));
    $command=new O\SubmitAssignmentOrderOriginalCommand($request,$correction?O\AssignmentOrderOriginalMode::CORRECTION:O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,$correction?'2026-09-02':$date,true,$correction?'original-0001':null,$correction?'revision-0001':null,$correction?'revision-0001':null,$correction?'Исправлена дата документа':null,new O\AssignmentOrderOriginalUpload($stream,'original.pdf','application/pdf'));
    return compact('request','app','command','repo','ids','clock','storage','observers','stream');
}
function dynamicNoFacts(array $g,string $before):void
{
    assertSameValue($before,$g['repo']->evidenceCanonicalJson(4512,81),'no accepted/attempt facts or existing evidence changes');
    assertSameValue(0,$g['observers']->deliveryCalls,'no delivery');
    assertSameValue(1,$g['stream']->closeCalls,'one stream close');
}
$failed=[];$passed=0;
function dynamicCase(string $name,callable $case):void{global $failed,$passed;try{$case();++$passed;echo "PASS $name\n";}catch(Throwable $e){$failed[]=$name;echo "FAIL $name: ".$e->getMessage()."\n";}}
dynamicCase('exact-public-ID-contract',function(){
    assertSameValue(['generated','collision','exhausted','unavailable'],array_map(fn($x)=>$x->value,O\AssignmentOrderOriginalIdStatus::cases()),'exact four statuses, no alternate FAILED');
    $value=new O\AssignmentOrderOriginalIdResult(status:O\AssignmentOrderOriginalIdStatus::GENERATED,id:'original-0001');
    assertSameValue('original-0001',$value->id,'exact named constructor and public ID property');
});
dynamicCase('post-stream-fingerprint-unavailable',function(){
    $g=dynamicGraph([dynamicId('generated','original-0001')],[dynamicId('generated','revision-0001')],unavailable:true);extract($g);$before=$repo->evidenceCanonicalJson(4512,81);
    $result=$app->submitAssignmentOrderOriginal($command);
    assertSameValue(dynamicFailure($request),dynamicTuple($result),'real fingerprint unavailable is persistence failure');
    assertSameValue(['','dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d'],$repo->fingerprints,'empty availability control followed by exact real fingerprint');
    assertSameValue([0,0],[$ids->rootCalls,$ids->revisionCalls],'no ID allocation');assertSameValue(2,$stream->readCalls,'actual post-stream fingerprint boundary reached');
    assertSameValue([1,0,1,1,null],[$storage->beginCalls,$storage->stage->finalizeCalls,$storage->stage->abortCalls,$storage->stage->closeCalls,$storage->stage->lease],'staged bytes cleaned without finalization');
    assertSameValue([],array_values(array_filter($observers->lifecycle,fn($event)=>in_array($event,[O\AssignmentOrderOriginalLifecycleEvent::AFTER_FINGERPRINT_MISS_BEFORE_CAS,O\AssignmentOrderOriginalLifecycleEvent::AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT,O\AssignmentOrderOriginalLifecycleEvent::AFTER_COMMIT_BEFORE_RETURN],true))),'no fingerprint-miss/finalize/committed lifecycle after unavailable');dynamicNoFacts($g,$before);
});
foreach(['initial-root','initial-revision','correction-revision'] as $axis){
    foreach(['generated','unavailable','exhausted','generated-null','throw','collision-then-generated','eight-collisions'] as $fault){
        dynamicCase($axis.'-'.$fault,function()use($axis,$fault){
            $correction=$axis==='correction-revision';$root=dynamicId('generated','original-0001');$revision=dynamicId('generated',$correction?'revision-0002':'revision-0001');
            $script=match($fault){
                'generated'=>[$axis==='initial-root'?$root:$revision],'unavailable','exhausted'=>[dynamicId($fault,null)],'generated-null'=>[dynamicId('generated',null)],'throw'=>[new RuntimeException('ID unavailable')],
                'collision-then-generated'=>[...array_fill(0,7,dynamicId('collision',null)),$axis==='initial-root'?$root:$revision],
                'eight-collisions'=>[...array_fill(0,8,dynamicId('collision',null)),$axis==='initial-root'?$root:$revision],
            };
            $g=dynamicGraph($axis==='initial-root'?$script:[$root],$axis==='initial-root'?[$revision]:$script,$correction);extract($g);$before=$repo->evidenceCanonicalJson(4512,81);
            $result=$app->submitAssignmentOrderOriginal($command);$success=in_array($fault,['generated','collision-then-generated'],true);$calls=in_array($fault,['collision-then-generated','eight-collisions'],true)?8:1;
            $expectedCalls=$axis==='initial-root'?[$calls,$success?1:0]:[$correction?0:1,$calls];
            assertSameValue($expectedCalls,[$ids->rootCalls,$ids->revisionCalls],'per-source bounded attempts; no next source after failure');
            if(!$success){
                assertSameValue(dynamicFailure($request),dynamicTuple($result),'ID failure exact result');
                assertSameValue([0,1,1,null],[$storage->stage->finalizeCalls,$storage->stage->abortCalls,$storage->stage->closeCalls,$storage->stage->lease],'no finalize on ID failure and exact cleanup');
                dynamicNoFacts($g,$before);
            }else{
                $expected=['accepted',null,false,$request,'original-0001',$correction?'revision-0002':'revision-0001',$correction?2:1,$correction?'2026-09-02':'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'];
                assertSameValue($expected,dynamicTuple($result),'exact generated candidate and full accepted evidence');
                assertSameValue([1,0,1,1,1],[$storage->stage->finalizeCalls,$storage->stage->abortCalls,$storage->stage->closeCalls,$stream->closeCalls,$storage->stage->lease->releaseCalls],'one finalize/close/release without abort');
                assertSameValue([1,0,1],[count($repo->accepted),count($repo->attempts),$observers->deliveryCalls],'one accepted commit/delivery');
                assertSameValue(['original-0001',$correction?'revision-0002':'revision-0001'],[$repo->accepted[0]->rootOriginalId,$repo->accepted[0]->newRevisionId],'persisted generated IDs');
            }
        });
    }
}
foreach(['missing-Z'=>'2026-09-02T09:15:30','space'=>'2026-09-02 09:15:30','fraction'=>'2026-09-02T09:15:30.000Z','offset'=>'2026-09-02T09:15:30+00:00','calendar'=>'2026-02-31T09:15:30Z','hour'=>'2026-09-02T24:00:00Z','leap-second'=>'2026-09-02T09:15:60Z','throw'=>new RuntimeException('clock unavailable')] as $label=>$time){
    dynamicCase('clock-'.$label,function()use($time){
        $g=dynamicGraph([dynamicId('generated','original-0001')],[dynamicId('generated','revision-0001')],time:$time);extract($g);$before=$repo->evidenceCanonicalJson(4512,81);
        $result=$app->submitAssignmentOrderOriginal($command);
        assertSameValue(dynamicFailure($request),dynamicTuple($result),'invalid/unavailable clock fails before stage');
        assertSameValue([1,0,0,0],[$clock->calls,$storage->beginCalls,$ids->rootCalls,$ids->revisionCalls],'one clock, no stage or IDs');
        assertSameValue([''],$repo->fingerprints,'no real fingerprint lookup after invalid clock');assertSameValue(0,$stream->readCalls,'clock rejection before any stream read');
        assertSameValue([],$observers->storage,'no storage events');assertSameValue([],$observers->lifecycle,'no lifecycle events');dynamicNoFacts($g,$before);
    });
}
foreach(['2026-09-02'=>true,'2026-09-03'=>false] as $date=>$accepted){
    dynamicCase('canonical-clock-Moscow-'.$date,function()use($date,$accepted){
        $g=dynamicGraph([dynamicId('generated','original-0001')],[dynamicId('generated','revision-0001')],time:'2026-09-01T21:00:00Z',date:$date);extract($g);
        $result=$app->submitAssignmentOrderOriginal($command);
        $expected=$accepted?['accepted',null,false,$request,'original-0001','revision-0001',1,$date,'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-01T21:00:00Z']:['rejected','future_document_date',false,$request,null,null,null,null,null,null,null];
        assertSameValue($expected,dynamicTuple($result),'Moscow midnight canonical control');
        assertSameValue(1,$clock->calls,'one canonical clock');assertSameValue(1,$stream->closeCalls,'one stream close');
        assertSameValue($accepted?[1,0,1]:[0,1,0],[count($repo->accepted),count($repo->attempts),$observers->deliveryCalls],'exact accepted/audit/delivery');
        if(!$accepted){assertSameValue(0,$storage->beginCalls,'future date before stage');assertSameValue('2026-09-01T21:00:00Z',$repo->attempts[0]->attemptedAt,'canonical audit timestamp');}
    });
}
if($failed!==[])throw new TestFailure('Dynamic ports failed '.count($failed).' cases: '.implode(',',$failed));
echo "PASS ORIGINAL-DYNAMIC-PORTS ($passed cases)\n";
