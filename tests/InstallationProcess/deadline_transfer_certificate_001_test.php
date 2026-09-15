<?php
// DEADLINE-TRANSFER-CERTIFICATE-001; root-authored public application specification.
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require dirname(__DIR__).'/Yii2/PreopeningFixture.php';
use FMonitor2\DeadlineTransferCertificate\DeadlineTransferCertificates as Certificates;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Pdf;
function certStream(string $bytes):Closure{$offset=0;return static function()use($bytes,&$offset):?string{if($offset>=strlen($bytes))return null;$chunk=substr($bytes,$offset,65536);$offset+=strlen($chunk);return$chunk;};}
function certReject(string $reason,Closure $action):void{try{$action();}catch(DomainException $e){assertSameValue($reason,$e->getMessage(),'exact certificate refusal');return;}throw new TestFailure('Expected '.$reason);}
$fixture=null;
try{
    $fixture=new PreopeningFixture(dirname(__DIR__,2));$db=$fixture->db;$p=$fixture->p;
    assertSameValue(true,class_exists(Certificates::class),'RED_ASSERTION certificate public owner absent after canonical fixture setup');
    \FMonitor2\Tests\Support\DeadlineCertificateFixture::grants($db,$p);
    $db->query("INSERT INTO {$p}fm2_installation_cases SELECT 6102,4513,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM {$p}fm2_installation_cases WHERE id=6101");
    $clock=static fn():string=>'2026-09-14T12:00:00Z';$service=new Certificates($db,$p,$clock);
    $seq=0;$command=static function(array $changes=[])use(&$seq):array{return array_replace(['requestId'=>sprintf('00000000-0000-4000-8000-%012d',++$seq),'installationCaseId'=>6101,'actorId'=>18,'expectedVersion'=>0,'certificateDate'=>'2026-09-01','newDeadline'=>'2026-09-20','correctionReason'=>null,'sourceLabel'=>'Fixture certificate','sourceLocator'=>'fixture://certificate/A','mediaType'=>'application/pdf'],$changes);};
    $a=Pdf::passiveClassic();$b=$a."\n";
    $counts=static function()use($db,$p):array{$out=[];foreach(['roots','revisions','operations','pdf_chunks'] as $suffix)$out[]=(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_deadline_certificate_{$suffix}")->fetch_column();return$out;};
    assertSameValue(['current'=>null,'history'=>[]],$service->read(18,6101),'no certificate yet');
    assertSameValue(null,$service->currentEvidence(6101),'no implicit certificate');
    foreach([73,94,96] as $actor){$called=false;$c=$command(['actorId'=>$actor]);certReject('FORBIDDEN',static fn()=>$service->submit($c,static function()use(&$called):?string{$called=true;return null;}));assertSameValue(false,$called,'authorization before byte acquisition');}
    foreach([73,94] as $actor)certReject('FORBIDDEN',static fn()=>$service->read($actor,6101));
    $called=false;certReject('FORBIDDEN',static fn()=>$service->submit($command(['actorId'=>73,'installationCaseId'=>999999]),static function()use(&$called):?string{$called=true;return null;}));assertSameValue(false,$called,'unknown case does not disclose before authorization');
    $before=$counts();$initial=$command();$accepted=$service->submit($initial,certStream($a));$r1=$accepted['revision'];
    assertSameValue('accepted',$accepted['status'],'initial accepted');
    foreach(['installationCaseId'=>6101,'revisionNumber'=>1,'previousRevisionId'=>null,'certificateDate'=>'2026-09-01','newDeadline'=>'2026-09-20','correctionReason'=>null,'actorId'=>18,'recordedAt'=>'2026-09-14T12:00:00Z','sourceLabel'=>'Fixture certificate','sourceLocator'=>'fixture://certificate/A','pdfSha256'=>hash('sha256',$a),'byteSize'=>strlen($a)] as $key=>$value)assertSameValue($value,$r1[$key],$key);
    assertSameValue([1,1,1,1],$counts(),'one root revision and receipt');
    assertSameValue(['status'=>'replayed','revision'=>$r1],$service->submit($initial,certStream($a)),'idempotent exact replay');
    foreach([['newDeadline'=>'2026-09-21'],['certificateDate'=>'2026-09-02'],['sourceLocator'=>'fixture://changed'],['sourceLabel'=>'Changed'],['installationCaseId'=>6102],['expectedVersion'=>1,'correctionReason'=>'Changed mode']] as $changes)certReject('OPERATION_CONFLICT',static fn()=>$service->submit(array_replace($initial,$changes),certStream($a)));
    certReject('OPERATION_CONFLICT',static fn()=>$service->submit($initial,certStream($b)));assertSameValue([1,1,1,1],$counts(),'conflicts conserve all facts');
    $correction=$command(['expectedVersion'=>1,'certificateDate'=>'2026-09-15','newDeadline'=>'2026-08-16','correctionReason'=>'Date corrected']);$r2=$service->submit($correction,certStream($b))['revision'];
    assertSameValue([2,$r1['id'],'2026-08-16'],[$r2['revisionNumber'],$r2['previousRevisionId'],$r2['newDeadline']],'correction link and date');
    foreach([['correctionReason'=>'Another reason'],['expectedVersion'=>2]] as $changes)certReject('OPERATION_CONFLICT',static fn()=>$service->submit(array_replace($correction,$changes),certStream($b)));
    $history=['current'=>$r2,'history'=>[$r1,$r2]];assertSameValue($history,$service->read(18,6101),'immutable ordered history');assertSameValue($history,$service->read(96,6101),'OTIZ can read');
    foreach([[$r1,$a],[$r2,$b]] as [$revision,$bytes]){$download=$service->download(96,$revision['id']);assertSameValue([$bytes,'application/pdf',strlen($bytes),hash('sha256',$bytes)],[$download['bytes'],$download['mediaType'],$download['byteSize'],$download['pdfSha256']],'exact historical download');assertSameValue(false,strpbrk($download['filename'],"/\\\r\n"),'safe filename');}
    $reordered=array_reverse($initial,true);$reordered['transportOnly']='ignored';assertSameValue(['status'=>'replayed','revision'=>$r1],$service->submit($reordered,certStream($a)),'semantic replay ignores order and undeclared transport fields');
    foreach([$r1,$r2]as$revision){$keys=array_keys($service->download(96,$revision['id']));sort($keys);assertSameValue(['byteSize','bytes','filename','mediaType','pdfSha256'],$keys,'exact public download shape');}
    $evidence=$service->currentEvidence(6101);assertSameValue(array_merge($r2,['source'=>['label'=>$r2['sourceLabel'],'locator'=>'fm2_deadline_certificate_revisions/'.$r2['id'],'contentSha256'=>hash('sha256',json_encode($r2,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))]]),$evidence,'complete current revision evidence');assertSameValue($r2['id'],$evidence['id'],'current leaf without document date cutoff');assertSameValue('fm2_deadline_certificate_revisions/'.$r2['id'],$evidence['source']['locator'],'revision provenance');assertSameValue(hash('sha256',json_encode($r2,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),$evidence['source']['contentSha256'],'exact metadata content digest');
    assertSameValue(['status'=>'replayed','revision'=>$r1],$service->submit($initial,certStream($a)),'initial replay after correction');
    certReject('STALE_REVISION',static fn()=>$service->submit($command(['expectedVersion'=>1,'correctionReason'=>'Stale']),certStream($a)));
    certReject('STALE_REVISION',static fn()=>$service->submit($command(),certStream($a)));
    $before=$counts();
    foreach([['requestId'=>'BAD'],['actorId'=>'18'],['installationCaseId'=>0],['expectedVersion'=>-1],['mediaType'=>'text/plain'],['certificateDate'=>'2026-02-30'],['newDeadline'=>'2026-9-1'],['sourceLabel'=>''],['sourceLocator'=>''],['sourceLabel'=>str_repeat('x',501)],['expectedVersion'=>2,'correctionReason'=>null],['expectedVersion'=>0,'correctionReason'=>'Unexpected']] as $changes)certReject('INVALID_COMMAND',static fn()=>$service->submit($command($changes),certStream($a)));
    foreach([
        ['requestId'=>'AAAAAAAA-AAAA-4AAA-8AAA-AAAAAAAAAAAA'],['requestId'=>'00000000-0000-1000-8000-000000000001'],['requestId'=>'00000000-0000-4000-7000-000000000001'],
        ['installationCaseId'=>'6101'],['actorId'=>0],['actorId'=>18.0],['expectedVersion'=>'2'],['expectedVersion'=>2.0],['installationCaseId'=>6101.0],
        ['certificateDate'=>'0000-00-00'],['certificateDate'=>null],['newDeadline'=>20260920],['sourceLabel'=>' label'],['sourceLabel'=>'label '],['sourceLocator'=>' locator'],['sourceLocator'=>'locator '],['sourceLabel'=>'  '],['sourceLocator'=>'  '],['sourceLocator'=>str_repeat('я',501)],['sourceLabel'=>[]],['mediaType'=>'Application/PDF'],
        ['expectedVersion'=>2,'correctionReason'=>'  '],['expectedVersion'=>2,'correctionReason'=>str_repeat('я',1001)],['expectedVersion'=>2,'correctionReason'=>42]
    ] as $changes)certReject('INVALID_COMMAND',static fn()=>$service->submit($command($changes),certStream($a)));
    $db->begin_transaction();try{try{$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Nested']),certStream($a));throw new TestFailure('Caller transaction must be refused');}catch(LogicException){}assertSameValue(1,(int)$db->query('SELECT @@in_transaction')->fetch_column(),'caller transaction remains active');}finally{$db->rollback();}
    $called=false;certReject('CASE_NOT_FOUND',static fn()=>$service->submit($command(['installationCaseId'=>999999]),static function()use(&$called):?string{$called=true;return null;}));assertSameValue(false,$called,'missing case rejected before byte acquisition');
    foreach(['','%PDF-1.7 invalid',Pdf::forbidden('JavaScript'),str_repeat('x',20971521)] as $bytes)certReject('INVALID_PDF',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Invalid PDF']),certStream($bytes)));
    certReject('STORAGE_UNAVAILABLE',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Interrupted']),static fn():string=>''));
    certReject('STORAGE_UNAVAILABLE',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Interrupted']),static function():?string{throw new RuntimeException('injected stream failure');}));
    certReject('STORAGE_UNAVAILABLE',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Oversized chunk']),static fn():string=>str_repeat('x',65537)));
    assertSameValue($before,$counts(),'all invalid commands conserve facts');assertSameValue($history,$service->read(18,6101),'history unchanged after failures');
    foreach([73,94] as $actor)certReject('FORBIDDEN',static fn()=>$service->download($actor,$r1['id']));
    $db->query("CREATE TRIGGER certificate_failure BEFORE INSERT ON {$p}fm2_deadline_certificate_revisions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='injected certificate insert failure'");
    try{certReject('STORAGE_UNAVAILABLE',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'DB failure']),certStream($a)));}finally{$db->query('DROP TRIGGER certificate_failure');}
    assertSameValue($before,$counts(),'DB failure rolls back all facts');assertSameValue($history,$service->read(18,6101),'DB failure preserves current leaf');



    $db->query("CREATE TRIGGER certificate_chunk_failure BEFORE INSERT ON {$p}fm2_deadline_certificate_pdf_chunks FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='injected chunk failure'");try{certReject('STORAGE_UNAVAILABLE',static fn()=>$service->submit($command(['expectedVersion'=>2,'correctionReason'=>'Chunk failure']),certStream($a)));}finally{$db->query('DROP TRIGGER certificate_chunk_failure');}assertSameValue($before,$counts(),'chunk insertion rollback preserves all facts');

    $lostDb=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$fixture->database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $commitFailure=new Certificates($lostDb,$p,$clock,static function(string $phase)use($lostDb):void{if($phase==='beforeCommit')$lostDb->close();});
    certReject('OUTCOME_UNKNOWN',static fn()=>$commitFailure->submit($command(['expectedVersion'=>2,'correctionReason'=>'Commit connection lost']),certStream($a)));assertSameValue($before,$counts(),'lost connection before actual commit did not activate partial state');
    $schemaColumns=['roots'=>['installation_case_id','current_revision_id','current_version'],'revisions'=>['id','installation_case_id','revision_number','previous_revision_id','certificate_date','new_deadline','correction_reason','actor_id','recorded_at','source_label','source_locator','pdf_sha256','byte_size'],'operations'=>['actor_id','request_id','request_sha256','revision_id'],'pdf_chunks'=>['revision_id','chunk_no','bytes']];
    foreach($schemaColumns as $suffix=>$expected){$observed=array_column($db->query("SHOW COLUMNS FROM {$p}fm2_deadline_certificate_{$suffix}")->fetch_all(MYSQLI_ASSOC),'Field');assertSameValue($expected,$observed,'exact canonical columns '.$suffix);}
    certReject('CASE_NOT_FOUND',static fn()=>$service->read(18,999999));certReject('CASE_NOT_FOUND',static fn()=>$service->download(18,999999));
    // Controlled lost response after committed facts; same identity resolves uncertainty.
    $uncertain=$command(['expectedVersion'=>2,'correctionReason'=>'Lost response']);
    $observer=new Certificates($db,$p,$clock,static function(string $phase):void{if($phase==='afterCommit')throw new RuntimeException('response lost');});
    certReject('OUTCOME_UNKNOWN',static fn()=>$observer->submit($uncertain,certStream($a)));
    $resolved=$service->submit($uncertain,certStream($a));assertSameValue('replayed',$resolved['status'],'unknown outcome replays exact committed result');assertSameValue(3,$resolved['revision']['revisionNumber'],'one committed revision despite lost response');
    $before=$counts();$precommit=new Certificates($db,$p,$clock,static function(string $phase):void{if($phase==='beforeCommit')throw new RuntimeException('before commit fault');});
    certReject('STORAGE_UNAVAILABLE',static fn()=>$precommit->submit($command(['expectedVersion'=>3,'correctionReason'=>'Rollback']),certStream($a)));assertSameValue($before,$counts(),'precommit observer rollback');
    // Independent processes start together and contend on one current version.
    $barrier=$fixture->artifacts.'/certificate-start';$workers=[];
    try{
        for($i=0;$i<2;$i++){$c=$command(['expectedVersion'=>3,'correctionReason'=>'Concurrent '.$i]);$pipes=[];$proc=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/deadline_certificate_worker.php',$fixture->database,$p,json_encode($c,JSON_THROW_ON_ERROR),$barrier],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new TestFailure('Worker setup');$workers[]=[$proc,$pipes];}
        touch($barrier);$results=[];foreach($workers as [$proc,$pipes]){$results[]=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue(0,proc_close($proc),'concurrent worker '.$stderr);}sort($results);assertSameValue(['STALE_REVISION','accepted'],$results,'one concurrent correction wins');
    }finally{if(is_file($barrier))unlink($barrier);}
    $historyNow=$service->read(18,6101);assertSameValue([1,2,3,4],array_column($historyNow['history'],'revisionNumber'),'linear concurrent history');
    $r2=$historyNow['current'];

    // Largest supported PDF is chunked, avoiding the shared server packet limit.
    $large=$a.str_repeat(' ',20971520-strlen($a));$largeRevision=$service->submit($command(['actorId'=>97,'expectedVersion'=>4,'correctionReason'=>'Manager full size PDF']),certStream($large))['revision'];
    assertSameValue(20971520,$largeRevision['byteSize'],'inclusive20MiB limit');assertSameValue($large,$service->download(96,$largeRevision['id'])['bytes'],'chunked bytes reconstruct exactly');unset($large);
    $before=$counts();foreach(['user','role'] as $disabled){if($disabled==='user')$db->query("UPDATE {$p}fm2_pilot_users SET status=0 WHERE user_id=18");else $db->query("UPDATE {$p}fm2_pilot_roles SET status=0 WHERE role_id=1");try{certReject('FORBIDDEN',static fn()=>$service->read(18,6101));certReject('FORBIDDEN',static fn()=>$service->submit($command(['expectedVersion'=>5,'correctionReason'=>'Disabled']),certStream($a)));}finally{if($disabled==='user')$db->query("UPDATE {$p}fm2_pilot_users SET status=1 WHERE user_id=18");else $db->query("UPDATE {$p}fm2_pilot_roles SET status=1 WHERE role_id=1");}}
    $db->query("UPDATE {$p}fm2_pilot_role_permissions SET permission='DEADLINE_CERTIFICATE.WRITE' WHERE role_id=1 AND BINARY permission='deadline_certificate.write'");try{certReject('FORBIDDEN',static fn()=>$service->submit($command(['expectedVersion'=>5,'correctionReason'=>'Near match']),certStream($a)));}finally{$db->query("UPDATE {$p}fm2_pilot_role_permissions SET permission='deadline_certificate.write' WHERE role_id=1 AND BINARY permission='DEADLINE_CERTIFICATE.WRITE'");}assertSameValue($before,$counts(),'disabled/near-match authorization conserves facts');
    // Exact schema inventory and binary persistence, without runtime DDL.
    $tables=FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV28::tables($p);assertSameValue(78,count($tables),'v27 exact table inventory');assertSameValue(42,count(FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV28::autoIncrement($p)),'v27 counters');
    foreach(['roots','revisions','operations','pdf_chunks'] as $suffix)assertSameValue(true,in_array($p.'fm2_deadline_certificate_'.$suffix,$tables,true),'recovery includes certificate '.$suffix);
    $chunks=$db->query("SELECT chunk_no,HEX(bytes) hex_bytes FROM {$p}fm2_deadline_certificate_pdf_chunks WHERE revision_id=".(int)$largeRevision['id']." ORDER BY chunk_no")->fetch_all(MYSQLI_ASSOC);assertSameValue(range(0,319),array_map('intval',array_column($chunks,'chunk_no')),'contiguous bounded chunks');$reconstructed='';foreach($chunks as $chunk)$reconstructed.=hex2bin($chunk['hex_bytes']);assertSameValue($largeRevision['pdfSha256'],hash('sha256',$reconstructed),'hex-blob database roundtrip');unset($chunks,$reconstructed);

    $barrier=$fixture->artifacts.'/certificate-initial-start';$workers=[];
    try{for($i=0;$i<2;$i++){$c=$command(['installationCaseId'=>6102]);$pipes=[];$proc=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/deadline_certificate_worker.php',$fixture->database,$p,json_encode($c,JSON_THROW_ON_ERROR),$barrier],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new TestFailure('Worker setup');$workers[]=[$proc,$pipes];}touch($barrier);$results=[];foreach($workers as [$proc,$pipes]){$results[]=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue(0,proc_close($proc),'initial worker '.$stderr);}sort($results);assertSameValue(['STALE_REVISION','accepted'],$results,'one concurrent initial wins');}finally{if(is_file($barrier))unlink($barrier);}
    assertSameValue([1],array_column($service->read(18,6102)['history'],'revisionNumber'),'one initial root history');
    $r2=$largeRevision;

    foreach(['certificate_date'=>"'0000-00-00'",'new_deadline'=>"'0000-00-00'",'revision_number'=>'99','previous_revision_id'=>(string)$r2['id']]as$column=>$invalid){$id=(int)$r1['id'];$original=$db->query("SELECT $column FROM {$p}fm2_deadline_certificate_revisions WHERE id=$id")->fetch_column();$mode=$db->query('SELECT @@SESSION.sql_mode')->fetch_column();$db->query("SET SESSION sql_mode=''");try{$db->query("UPDATE {$p}fm2_deadline_certificate_revisions SET $column=$invalid WHERE id=$id");certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->currentEvidence(6101));certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->read(96,6101));certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->download(96,$r2['id']));}finally{$db->prepare("UPDATE {$p}fm2_deadline_certificate_revisions SET $column=? WHERE id=?")->execute([$original,$id]);$db->query("SET SESSION sql_mode='".$db->real_escape_string($mode)."'");}}
    $rowId=(int)$r2['id'];
    foreach([['byte_size','1',(string)$r2['byteSize']],['certificate_date',"'0000-00-00'","'".$r2['certificateDate']."'"],['previous_revision_id','NULL',(string)$r2['previousRevisionId']]] as [$column,$badValue,$restore]){
        $oldMode=$db->query('SELECT @@SESSION.sql_mode')->fetch_column();$db->query("SET SESSION sql_mode=''");
        try{$db->query("UPDATE {$p}fm2_deadline_certificate_revisions SET {$column}={$badValue} WHERE id={$rowId}");certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->currentEvidence(6101));certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->download(96,$rowId));}
        finally{$db->query("UPDATE {$p}fm2_deadline_certificate_revisions SET {$column}={$restore} WHERE id={$rowId}");$db->query("SET SESSION sql_mode='".$db->real_escape_string($oldMode)."'");}
    }
    $db->query("UPDATE {$p}fm2_deadline_certificate_roots SET current_version=100 WHERE installation_case_id=6101");try{certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->currentEvidence(6101));}finally{$db->query("UPDATE {$p}fm2_deadline_certificate_roots SET current_version=5 WHERE installation_case_id=6101");}
    $rowId=(int)$r2['id'];$db->query("UPDATE {$p}fm2_deadline_certificate_revisions SET pdf_sha256=REPEAT('0',64) WHERE id={$rowId}");
    certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->currentEvidence(6101));certReject('CERTIFICATE_EVIDENCE_INVALID',static fn()=>$service->download(96,$rowId));
    // Inclusive UTF-8 character boundaries, independent fresh case.
    $db->query("INSERT INTO {$p}fm2_installation_cases SELECT 6103,4514,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM {$p}fm2_installation_cases WHERE id=6101");
    $max=$command(['installationCaseId'=>6103,'sourceLabel'=>str_repeat('я',500),'sourceLocator'=>str_repeat('ю',500),'certificateDate'=>'2024-02-29']);$maxInitial=$service->submit($max,certStream($a))['revision'];assertSameValue([$max['sourceLabel'],$max['sourceLocator'],'2024-02-29'],[$maxInitial['sourceLabel'],$maxInitial['sourceLocator'],$maxInitial['certificateDate']],'inclusive source/date boundaries');
    $maxCorrection=$service->submit($command(['installationCaseId'=>6103,'expectedVersion'=>1,'correctionReason'=>str_repeat('я',1000)]),certStream($a))['revision'];assertSameValue(str_repeat('я',1000),$maxCorrection['correctionReason'],'inclusive UTF-8 correction reason boundary');
    $otherActor=$service->submit(array_replace($max,['actorId'=>97,'expectedVersion'=>2,'correctionReason'=>'Independent actor identity']),certStream($a));assertSameValue(['accepted',97,3],[$otherActor['status'],$otherActor['revision']['actorId'],$otherActor['revision']['revisionNumber']],'request identity is scoped by actor');
    echo "PASS DEADLINE-TRANSFER-CERTIFICATE-001 application\n";
}finally{if($fixture!==null)$fixture->close();}
