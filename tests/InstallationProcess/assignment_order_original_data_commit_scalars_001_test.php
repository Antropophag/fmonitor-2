<?php

declare(strict_types=1);
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

// DATA-INTEGRITY-001§9 owns adapter validation before even escaping/transaction calls.
$invalid=[
    'requestId'=>['not-uuid','ABCDEF00-0000-4000-8000-000000000001','00000000-0000-0000-8000-000000000001'],
    'installationCaseId'=>[0,-1],'assignmentOrderId'=>[0,-1],'actorUserId'=>[0,-1],
    'rootOriginalId'=>['','bad/root',str_repeat('r',81)],'newRevisionId'=>['','bad\\revision',str_repeat('r',81)],
    'newRevisionNumber'=>[0,-1,4294967296],
    'fingerprint'=>['',str_repeat('A',64),str_repeat('a',64)],
    'compositionIdentity'=>['','arbitrary','composition-82-v1','composition-81-v0','composition-81-v01','composition-81-v9223372036854775808'],
    'compositionSha256'=>['',str_repeat('A',64)],'documentDate'=>['2026-02-31','0000-01-01','2026-9-01'],
    'uploadedAt'=>['2026-02-31T09:15:30Z','2026-09-02T09:15:30.000Z','2026-09-02T09:15:30+00:00'],
    'pdfSha256'=>['',str_repeat('A',64)],'byteSize'=>[0,-1,20971521],
    'privateContentIdentity'=>['private-content-0001','content-sha256-'.str_repeat('0',64),'bad/content'],
    'domainEventType'=>['','unknown_event'],
];
foreach([false,true] as $correction){
    $base=$correction?S\OriginalIntegrityCommits::correctionValues():[];$mode=$correction?'correction':'initial';
    foreach($invalid as $field=>$values)foreach($values as $i=>$value)integrityCase($mode.'-invalid-'.$field.'-'.$i,function()use($base,$field,$value){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');$result=$repo->commitAccepted(S\OriginalIntegrityCommits::accepted([$field=>$value]+$base));assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$result,'invalid AcceptedCommit is rollback');assertSameValue([],$db->calls,'invalid AcceptedCommit reaches no DB method');});
    integrityCase($mode.'-valid-reaches-DB-control',function()use($base){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$repo->commitAccepted(S\OriginalIntegrityCommits::accepted($base)),'unavailable DB fails rollback');assertSameValue(true,count($db->calls)>0,'valid scalar DTO must attempt DB so reject-all cannot pass');});
}
$relations=[
    'initial-revision-two'=>['newRevisionNumber'=>2],'initial-previous'=>['previousRevisionId'=>'revision-0000'],
    'initial-expected'=>['expectedCurrentRevisionId'=>'revision-0000'],'initial-reason'=>['correctionReason'=>'Unexpected reason'],
    'initial-correction-event'=>['domainEventType'=>'assignment_order_original_corrected'],
];
foreach($relations as $name=>$changes)integrityCase($name,function()use($changes){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$repo->commitAccepted(S\OriginalIntegrityCommits::accepted($changes)),'invalid initial relationships');assertSameValue([],$db->calls,'relationship invalid before SQL');});
$correctionRelations=[
    'revision-one'=>['newRevisionNumber'=>1],'previous-null'=>['previousRevisionId'=>null],'expected-null'=>['expectedCurrentRevisionId'=>null],
    'previous-slash'=>['previousRevisionId'=>'bad/id'],'expected-slash'=>['expectedCurrentRevisionId'=>'bad/id'],
    'previous-not-expected'=>['expectedCurrentRevisionId'=>'revision-0099'],'new-equals-previous'=>['newRevisionId'=>'revision-0001'],
    'reason-null'=>['correctionReason'=>null],'reason-empty'=>['correctionReason'=>''],'reason-untrimmed'=>['correctionReason'=>' Исправлено '],
    'reason-control'=>['correctionReason'=>"bad\0reason"],'reason-invalid-utf8'=>['correctionReason'=>"bad\xFF"],
    'reason-overlong'=>['correctionReason'=>str_repeat('Я',501)],'initial-event'=>['domainEventType'=>'assignment_order_original_accepted'],
];
foreach($correctionRelations as $name=>$changes)integrityCase('correction-'.$name,function()use($changes){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$repo->commitAccepted(S\OriginalIntegrityCommits::accepted($changes+S\OriginalIntegrityCommits::correctionValues())),'invalid correction relationships');assertSameValue([],$db->calls,'correction invalid before SQL');});
$attemptInvalid=[
    'requestId'=>['not-uuid','00000000-0000-7000-8000-000000000301'],'actorUserId'=>[0,-1],'installationCaseId'=>[0,-1],'assignmentOrderId'=>[0,-1],
    'status'=>[O\AssignmentOrderOriginalStatus::ACCEPTED,O\AssignmentOrderOriginalStatus::REPLAYED,O\AssignmentOrderOriginalStatus::FAILED,O\AssignmentOrderOriginalStatus::CONFLICT],
    'reason'=>[O\AssignmentOrderOriginalReason::INVALID_COMMAND,O\AssignmentOrderOriginalReason::STREAM_FAILURE,O\AssignmentOrderOriginalReason::STORAGE_FAILURE,O\AssignmentOrderOriginalReason::STALE_REVISION],
    'retryable'=>[true],'attemptedAt'=>['2026-02-31T09:15:30Z','2026-09-02 09:15:30','2026-09-02T09:15:30.000Z'],
];
foreach($attemptInvalid as $field=>$values)foreach($values as $i=>$value)integrityCase('attempt-'.$field.'-'.$i,function()use($field,$value){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$repo->commitAttempt(S\OriginalIntegrityCommits::attempt([$field=>$value])),'invalid AttemptCommit rollback');assertSameValue([],$db->calls,'invalid attempt before SQL');});
foreach([['status'=>O\AssignmentOrderOriginalStatus::REJECTED,'reason'=>O\AssignmentOrderOriginalReason::INVALID_PDF],['status'=>O\AssignmentOrderOriginalStatus::CONFLICT,'reason'=>O\AssignmentOrderOriginalReason::STALE_REVISION],['attemptedAt'=>'1970-01-01T00:00:00Z']] as $i=>$changes)integrityCase('attempt-valid-control-'.$i,function()use($changes){$db=new S\OriginalIntegrityNoSql();$repo=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,$repo->commitAttempt(S\OriginalIntegrityCommits::attempt($changes)),'unavailable DB control');assertSameValue(true,count($db->calls)>0,'valid attempt reaches DB');});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_COMMIT_SCALARS_OK');
