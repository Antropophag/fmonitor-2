<?php

declare(strict_types=1);
// FMonitor test classification: FMONITOR_TEST_DB — actual isolated synthetic MariaDB.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

$f=null;
try{
    $f=new S\OriginalIntegrityDatabase();$repo=new O\AssignmentOrderOriginalMariaDbRepository($f->db,$f->prefix);
    integrityCase('literal-fingerprint-control',function(){assertSameValue('dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d',S\OriginalIntegrityCommits::acceptedValues()['fingerprint'],'independent parent literal encoding');});
    integrityCase('real-accepted-read-control',function()use($f,$repo){$f->resetOriginals();$v=$f->seedAccepted();$before=$f->facts();$lookup=$repo->findTerminalRequest($v['requestId']);assertSameValue(O\AssignmentOrderOriginalLookupStatus::FOUND,$lookup->status(),'exact durable accepted row found');assertSameValue(['accepted',null,false,$v['requestId'],'original-0001','revision-0001',1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'],integrityTuple($lookup->result()),'complete independently seeded evidence');assertSameValue($before,$f->facts(),'read does not mutate facts');});
    $mutations=[
        'request-retryable'=>['requests','retryable=1'],
        'request-replayed'=>['requests',"status='replayed'"],
        'request-wrong-case'=>['requests','installation_case_id=9999'],
        'request-wrong-order'=>['requests','assignment_order_id=82'],
        'request-wrong-actor'=>['requests',"actor_identity='19'"],
        'request-padded-actor'=>['requests',"actor_identity='018'"],
        'request-overflow-actor'=>['requests',"actor_identity='9223372036854775808'"],
        'request-wrong-mode'=>['requests',"mode='correction'"],
        'request-evidence-null'=>['requests','sha256=NULL'],
        'request-evidence-wrong'=>['requests',"sha256='".str_repeat('a',64)."'"],
        'request-number-zero'=>['requests','revision_number=0'],
        'request-date-zero'=>['requests',"document_date='0000-00-00'"],
        'request-size-zero'=>['requests','byte_size=0'],
        'request-size-overflow'=>['requests','byte_size=20971521'],
        'request-fractional-time'=>['requests',"uploaded_at_utc='2026-09-02 09:15:30.000001'"],
        'request-attempt-time-mismatch'=>['requests',"attempted_at_utc='2026-09-02 09:15:31.000000'"],
        'request-wrong-UUID'=>['requests',"request_id='00000000-0000-4000-8000-000000000999'"],
        'root-wrong-case'=>['roots','installation_case_id=9999'],
        'root-current-absent'=>['roots',"current_revision_id='revision-0099'"],
        'root-composition-hash'=>['roots',"composition_sha256='".str_repeat('a',64)."'"],
        'root-composition-id'=>['roots',"composition_identity='composition-82-v1'"],
        'revision-wrong-request'=>['revisions',"request_id='00000000-0000-4000-8000-000000000999'"],
        'revision-number-zero'=>['revisions','revision_number=0'],
        'revision-number-gap'=>['revisions','revision_number=2'],
        'revision-wrong-event'=>['revisions',"event_type='assignment_order_original_corrected'"],
        'revision-wrong-actor'=>['revisions','actor_user_id=19'],
        'revision-bad-content'=>['revisions',"private_content_identity='private-content-0001'"],
        'revision-size-mismatch'=>['revisions','byte_size=328'],
        'revision-hash-uppercase'=>['revisions',"pdf_sha256='".str_repeat('A',64)."'"],
        'revision-fractional-time'=>['revisions',"uploaded_at_utc='2026-09-02 09:15:30.000001'"],
        'revision-previous-initial'=>['revisions',"previous_revision_id='revision-0099'"],
        'revision-reason-initial'=>['revisions',"correction_reason='Unexpected correction'"],
        'event-wrong-type'=>['events',"event_type='assignment_order_original_corrected'"],
        'event-wrong-actor'=>['events','actor_user_id=19'],
        'event-wrong-order'=>['events','assignment_order_id=82'],
        'event-wrong-time'=>['events',"occurred_at_utc='2026-09-02 09:15:31.000000'"],
        'audit-wrong-actor'=>['audits',"actor_identity='19'"],
        'audit-wrong-mode'=>['audits',"mode='correction'"],
        'audit-wrong-time'=>['audits',"attempted_at_utc='2026-09-02 09:15:31.000000'"],
    ];
    foreach($mutations as $name=>[$suffix,$assignment])integrityCase('real-corrupt-'.$name,function()use($f,$repo,$suffix,$assignment){
        $f->resetOriginals();$v=$f->seedAccepted();$f->corrupt(function(mysqli $db)use($f,$suffix,$assignment){$db->query('UPDATE `'.$f->prefix.'fm2_assignment_order_original_'.$suffix.'` SET '.$assignment);assertSameValue(1,$db->affected_rows,'one synthetic corrupted row');});
        $before=$f->facts();$catalog=$f->catalog();$results=[];
        foreach(['request'=>fn()=>$repo->findTerminalRequest($v['requestId']),'fingerprint'=>fn()=>$repo->findAcceptedFingerprint($v['fingerprint']),'lineage'=>fn()=>$repo->findLineage($v['rootOriginalId'])] as $kind=>$read)$results[$kind]=$read()->status();
        assertSameValue(array_fill_keys(['request','fingerprint','lineage'],O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE),$results,'corrupt cross-table backing is unavailable at every owned read');
        assertSameValue($before,$f->facts(),'no read repair or partial deletion');assertSameValue($catalog,$f->catalog(),'no catalog repair');
    });
    foreach(['roots','revisions','requests','events','audits'] as $suffix)integrityCase('real-missing-backing-'.$suffix,function()use($f,$repo,$suffix){$f->resetOriginals();$v=$f->seedAccepted();$f->corrupt(fn(mysqli $db)=>$db->query('DELETE FROM `'.$f->prefix.'fm2_assignment_order_original_'.$suffix.'`'));$before=$f->facts();$r=$repo->findTerminalRequest($v['requestId']);assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$r->status(),'missing atomic backing is not reliable absence');assertSameValue($before,$f->facts(),'missing backing is not repaired');});
    integrityCase('real-wrong-stored-fingerprint',function()use($f,$repo){$f->resetOriginals();$v=$f->seedAccepted();$bad=str_repeat('a',64);$f->corrupt(fn(mysqli $db)=>$db->query('UPDATE `'.$f->prefix.'fm2_assignment_order_original_revisions` SET operation_fingerprint='.$f->q($bad)));$before=$f->facts();assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$repo->findAcceptedFingerprint($bad)->status(),'found revision fingerprint must recompute from exact evidence');assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$repo->findTerminalRequest($v['requestId'])->status(),'terminal backing fingerprint also validated');assertSameValue($before,$f->facts(),'no fingerprint repair');});
    integrityCase('real-duplicate-accepted-audit',function()use($f,$repo){$f->resetOriginals();$v=$f->seedAccepted();$row=$f->rows('fm2_assignment_order_original_audits')[0];unset($row['audit_id']);$f->insert('fm2_assignment_order_original_audits',$row);$before=$f->facts();assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$repo->findTerminalRequest($v['requestId'])->status(),'accepted backing has exactly one accepted audit even with nullable SQL unique key');assertSameValue($before,$f->facts(),'duplicate audit not deduplicated');});
    integrityCase('real-rejected-valid-control',function()use($f,$repo){$f->resetOriginals();$f->seedRejected();$r=$repo->findTerminalRequest('00000000-0000-4000-8000-000000000301');assertSameValue(O\AssignmentOrderOriginalLookupStatus::FOUND,$r->status(),'rejection with exact audit found');assertSameValue(O\AssignmentOrderOriginalReason::INVALID_PDF,$r->result()->reasonCode(),'stored rejection retained');});
    integrityCase('real-stored-invalid-command',function()use($f,$repo){$f->resetOriginals();$f->seedRejected('invalid_command');$before=$f->facts();assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$repo->findTerminalRequest('00000000-0000-4000-8000-000000000301')->status(),'physical CHECK does not authorize an invalid-shape terminal fact');assertSameValue($before,$f->facts(),'invalid command row not repaired');});
    integrityCase('real-denial-original-audit-presence-control',function()use($f,$repo){$f->resetOriginals();$f->seedRejected('authorization_denied');assertSameValue(O\AssignmentOrderOriginalLookupStatus::FOUND,$repo->findTerminalRequest('00000000-0000-4000-8000-000000000301')->status(),'original denial terminal/audit is valid without deciding repeated-denial policy');});
    integrityCase('real-historical-request-after-correction',function()use($f,$repo){$f->resetOriginals();$first=$f->seedAccepted();$second=$f->seedAccepted(S\OriginalIntegrityCommits::correctionValues());$before=$f->facts();$old=$repo->findTerminalRequest($first['requestId']);$latest=$repo->findTerminalRequest($second['requestId']);assertSameValue([O\AssignmentOrderOriginalLookupStatus::FOUND,O\AssignmentOrderOriginalLookupStatus::FOUND],[$old->status(),$latest->status()],'both immutable requests remain valid');assertSameValue(['revision-0001',1,'2026-09-01'],[$old->result()->currentRevisionId(),$old->result()->revisionNumber(),$old->result()->documentDate()],'old request does not follow current pointer');assertSameValue(['revision-0002',2,'2026-09-02'],[$latest->result()->currentRevisionId(),$latest->result()->revisionNumber(),$latest->result()->documentDate()],'latest request own evidence');assertSameValue('revision-0001',$repo->findAcceptedFingerprint($first['fingerprint'])->result()->currentRevisionId(),'historical fingerprint binds old revision');assertSameValue($before,$f->facts(),'historical reads do not mutate');});
    integrityCase('real-lineage-complete-control',function()use($f,$repo){$f->resetOriginals();$f->seedAccepted();$f->seedAccepted(S\OriginalIntegrityCommits::correctionValues());$line=$repo->findLineage('original-0001');assertSameValue(O\AssignmentOrderOriginalLookupStatus::FOUND,$line->status(),'current lineage found');assertSameValue(true,$line instanceof O\AssignmentOrderOriginalCompleteLineageLookup,'INTENDED_RED: complete real lineage metadata');assertSameValue([4512,81,['revision-0001','revision-0002'],'revision-0002',2],[$line->installationCaseId(),$line->assignmentOrderId(),$line->revisionIds(),$line->currentRevisionId(),$line->currentRevisionNumber()],'complete ordered lineage');});
    integrityCase('real-genuine-absence-controls',function()use($f,$repo){$f->resetOriginals();assertSameValue(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND,$repo->findTerminalRequest('00000000-0000-4000-8000-000000000123')->status(),'genuinely absent request');assertSameValue(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND,$repo->findAcceptedFingerprint(str_repeat('b',64))->status(),'genuinely absent fingerprint');assertSameValue(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND,$repo->findLineage('original-0123')->status(),'genuinely absent root');$ref=$repo->hasCommittedContent('orphan-content-0123');assertSameValue([O\AssignmentOrderOriginalLookupStatus::FOUND,false],[$ref->status(),$ref->referenced()],'zero reference count is valid false');});
    foreach(['findTerminalRequest'=>['not-a-uuid','ABCDEF00-0000-4000-8000-000000000001'],'findAcceptedFingerprint'=>['',str_repeat('A',64)],'findLineage'=>['','bad/root'],'hasCommittedContent'=>['','bad/content']] as $method=>$arguments)foreach($arguments as $i=>$argument)integrityCase('invalid-read-argument-'.$method.'-'.$i,function()use($method,$argument){$db=new S\OriginalIntegrityNoSql();$reader=new O\AssignmentOrderOriginalMariaDbRepository($db,'data_');assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$reader->{$method}($argument)->status(),'invalid lookup argument is unavailable');assertSameValue([],$db->calls,'invalid lookup is rejected before any DB method');});
    integrityCase('real-active-caller-transaction-preserved',function()use($f,$repo){$f->resetOriginals();$f->seedAccepted();$f->db->begin_transaction();try{$f->db->query('UPDATE `'.$f->prefix."fm2_installation_cases` SET process_state='caller-owned-uncommitted' WHERE id=9999");assertSameValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE,$repo->findTerminalRequest('00000000-0000-4000-8000-000000000001')->status(),'reader may not replace caller transaction');assertSameValue('1',(string)$f->db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'caller transaction remains active');}finally{$f->db->rollback();}$row=$f->db->query('SELECT process_state FROM `'.$f->prefix.'fm2_installation_cases` WHERE id=9999')->fetch_assoc();assertSameValue('fixture-decoy-v1',$row['process_state'],'caller rollback still owns mutation');});
}finally{if($f!==null)$f->close();}
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_MARIADB_READS_OK');
