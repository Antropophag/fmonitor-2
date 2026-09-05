<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialProcessState.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialFixture.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalDynamicPortsFixture.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalShapeFixture.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
// COMMAND-SHAPE-001 v0.2, independently fixed Example A literal.
$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
assertSameValue(327,strlen($pdf),'literal bytes');assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',$pdf),'literal PDF hash');
function shapeTuple(O\AssignmentOrderOriginalResult $r):array{return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];}
function shapeExpected(string $status='rejected',?string $reason='invalid_command',bool $retry=false):array{return [$status,$reason,$retry,'00000000-0000-4000-8000-000000000001',null,null,null,null,null,null,null];}
function shapeAccepted(bool $correction=false,string $date='2026-09-01',string $status='accepted'):array{return [$status,null,false,'00000000-0000-4000-8000-000000000001','original-0001',$correction?'revision-0002':'revision-0001',$correction?2:1,$date,'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'];}
$failures=[];$passed=0;
function shapeCase(string $name,callable $case):void{global $failures,$passed;try{$case();++$passed;echo "PASS $name\n";}catch(Throwable $error){$failures[]=$name;echo "FAIL $name: ".$error->getMessage()."\n";}}
$invalid=[];
foreach(['2026-02-29','2026-02-31','2026-04-31','0000-00-00','0000-01-01','2026-13-01','2026-00-01','2026-01-00','2026-9-01','2026-09-01 '] as $date)$invalid['date-'.$date]=[false,['documentDate'=>$date],'original.pdf'];
$badText=['empty'=>'','ascii-trim'=>'   ','unicode-trim'=>"\u{00A0}",'nul'=>"x\0y",'leading-nul'=>"\0x",'trailing-vt'=>"x\x0B",'control'=>"x\x01y",'del'=>"x\x7Fy",'c1'=>"x\u{0085}y",'bad-utf8'=>"x\xC3\x28y"];
foreach($badText+['256-codepoints'=>str_repeat('я',256)] as $name=>$value)$invalid['filename-'.$name]=[false,[],$value];
foreach($badText+['501-codepoints'=>str_repeat('Я',501)] as $name=>$value)$invalid['reason-'.$name]=[true,['correctionReason'=>$value],'original.pdf'];
$badIds=['empty'=>'','81bytes'=>str_repeat('a',81),'space'=>'a b','tab'=>"a\tb",'nul'=>"a\0b",'del'=>"a\x7Fb",'nbsp'=>"a\u{00A0}b",'badutf8'=>"\xC3\x28",'slash'=>'root/0001','backslash'=>"revision\\0001",'single-slash'=>'/','single-backslash'=>"\\"];
foreach(['rootOriginalId','targetRevisionId','expectedCurrentRevisionId'] as $field)foreach($badIds as $name=>$value)$invalid[$field.'-'.$name]=[true,[$field=>$value],'original.pdf'];
foreach(['rootOriginalId','targetRevisionId','expectedCurrentRevisionId','correctionReason'] as $field)$invalid['initial-non-null-'.$field]=[false,[$field=>'x'],'original.pdf'];
foreach($invalid as $label=>[$correction,$changes,$filename])foreach([false,true] as $hit){
    shapeCase($label.($hit?'-terminal-hit':''),function()use($pdf,$correction,$changes,$filename,$hit){
        $f=new S\OriginalShapeFixture($pdf,$correction,$hit);$before=$f->repository->evidenceCanonicalJson(4512,81);$command=$f->command($changes,$filename);
        $result=$f->application->submitAssignmentOrderOriginal($command);
        assertSameValue(shapeExpected(),shapeTuple($result),'invalid scalar full result before stored replay');
        assertSameValue([0,0,0,0,0,0,0,null,0,0,0],$f->businessCounters(),'zero business ports including auth/audit/inspector');
        assertSameValue([0,1],[$f->stream->readCalls,$f->stream->closeCalls],'unread stream closed once');
        assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'stored evidence and pending facts unchanged');
    });
}
foreach(['one'=>'я','255-codepoints'=>str_repeat('я',255),'unicode-trim'=>"\u{00A0}оригинал.pdf\u{3000}",'allowed-controls'=>"a\tb\nc\rd.pdf"] as $label=>$filename){
    shapeCase('valid-filename-'.$label,function()use($pdf,$filename){$f=new S\OriginalShapeFixture($pdf);$r=$f->application->submitAssignmentOrderOriginal($f->command(filename:$filename));assertSameValue(shapeAccepted(),shapeTuple($r),'valid filename accepted');assertSameValue(1,count($f->repository->inner->accepted),'one accepted fact');assertSameValue([2,1],[$f->stream->readCalls,$f->stream->closeCalls],'stream acquired once');});
}
foreach(['one'=>['Я','Я'],'500-codepoints'=>[str_repeat('Я',500),str_repeat('Я',500)],'unicode-trim'=>[" \u{00A0}Исправлена дата\u{3000} ",'Исправлена дата'],'allowed-controls'=>["Исправлена\tдата\nскана\r","Исправлена\tдата\nскана"]] as $label=>[$raw,$normalized]){
    shapeCase('valid-reason-'.$label,function()use($pdf,$raw,$normalized){$f=new S\OriginalShapeFixture($pdf,true);$command=$f->command(['correctionReason'=>$raw]);$r=$f->application->submitAssignmentOrderOriginal($command);assertSameValue(shapeAccepted(true,'2026-09-02'),shapeTuple($r),'valid correction accepted');assertSameValue($normalized,$f->repository->inner->accepted[0]->correctionReason,'exact normalized reason persisted');assertSameValue($raw,$command->correctionReason,'passive command not mutated');assertSameValue([0,1],[$f->ids->rootCalls,$f->ids->revisionCalls],'correction preserves root identity');});
}
shapeCase('valid-leap-day',function()use($pdf){$f=new S\OriginalShapeFixture($pdf);$r=$f->application->submitAssignmentOrderOriginal($f->command(['documentDate'=>'2024-02-29']));assertSameValue(shapeAccepted(date:'2024-02-29'),shapeTuple($r),'real leap day persists unchanged');});
foreach(['rootOriginalId','targetRevisionId','expectedCurrentRevisionId'] as $field)foreach(['one'=>'!','80bytes'=>str_repeat('a',80),'quotes'=>"a'\"`:-_b"] as $label=>$id){
    shapeCase('valid-opaque-'.$field.'-'.$label,function()use($pdf,$field,$id){$f=new S\OriginalShapeFixture($pdf,true,denied:true);$r=$f->application->submitAssignmentOrderOriginal($f->command([$field=>$id]));assertSameValue(shapeExpected(reason:'authorization_denied'),shapeTuple($r),'valid opaque grammar reaches authorization without invented lineage');assertSameValue([[18,'assignment_order.original.correct']],$f->authorizer->calls,'exact authorization attempted');assertSameValue([],$f->repository->calls,'denial before lookup');});
}
foreach([false,true] as $correction){shapeCase('valid-changed-metadata-replay-'.(int)$correction,function()use($pdf,$correction){$f=new S\OriginalShapeFixture($pdf,$correction,true);$before=$f->repository->evidenceCanonicalJson(4512,81);$changes=$correction?['correctionReason'=>'Другая допустимая причина']:[];$r=$f->application->submitAssignmentOrderOriginal($f->command($changes,'другое.pdf'));assertSameValue(shapeAccepted(status:'replayed'),shapeTuple($r),'shape-valid changed metadata retains terminal replay');assertSameValue(['terminal'],$f->repository->calls,'only terminal lookup');assertSameValue([0,1],[$f->stream->readCalls,$f->stream->closeCalls],'replay closes unread supplied stream');assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'replay has no evidence mutation');});}
foreach(['initial-root','correction-revision'] as $axis)foreach($badIds as $label=>$id){
    shapeCase('generated-'.$axis.'-'.$label,function()use($pdf,$axis,$id){
        $correction=$axis==='correction-revision';$f=new S\OriginalShapeFixture($pdf,$correction,generatedRoot:$correction?'original-0001':$id,generatedRevision:$correction?$id:'revision-0001');$before=$f->repository->evidenceCanonicalJson(4512,81);
        $r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(shapeExpected('failed','persistence_failure',true),shapeTuple($r),'malformed GENERATED value fails at ID boundary');
        assertSameValue($correction?[0,1]:[1,0],[$f->ids->rootCalls,$f->ids->revisionCalls],'one failing source, no retry/next source');
        assertSameValue([0,1,1,1,null],[$f->storage->stage->finalizeCalls,$f->storage->stage->abortCalls,$f->storage->stage->closeCalls,$f->stream->closeCalls,$f->storage->stage->lease],'cleanup once before finalize');
        assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'no facts or changed existing revision');assertSameValue(0,$f->observers->deliveryCalls,'no delivery');
    });
}
foreach(['initial-root','correction-revision'] as $axis)foreach(['one'=>'!','80bytes'=>str_repeat('q',80),'punctuation'=>"a'\"`:-_b"] as $label=>$id){
    shapeCase('valid-generated-'.$axis.'-'.$label,function()use($pdf,$axis,$id){$correction=$axis==='correction-revision';$f=new S\OriginalShapeFixture($pdf,$correction,generatedRoot:$correction?'original-0001':$id,generatedRevision:$correction?$id:'revision-0001');$r=$f->application->submitAssignmentOrderOriginal($f->command());$expected=shapeAccepted($correction,$correction?'2026-09-02':'2026-09-01');$expected[$correction?5:4]=$id;assertSameValue($expected,shapeTuple($r),'allowed opaque candidate accepted unchanged');assertSameValue($id,$correction?$f->repository->inner->accepted[0]->newRevisionId:$f->repository->inner->accepted[0]->rootOriginalId,'allowed generated identity persisted as data');});
}
if($failures!==[])throw new TestFailure('Command shape failed '.count($failures).' cases: '.implode(',',$failures));
echo "PASS COMMAND-SHAPE-001 ($passed cases)\n";
