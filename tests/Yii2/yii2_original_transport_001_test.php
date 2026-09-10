<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// YII2-PREOPENING-JOURNEY-001: raw PDF wire, role union, immutable revisions and exact downloads.
$f=null;
$formLinkAdmission = static function (PreopeningFixture $fixture, string $originalPath): void {
 foreach ([18=>1,97=>7] as $actor=>$role) {
  $limited=[];assertSameValue(303,$fixture->login($limited,$actor)['status'],'form-link actor login');
  $fixture->db->query("DELETE FROM {$fixture->p}fm2_pilot_role_permissions WHERE role_id=$role AND permission='assignment_order.original.read'");
  try {
   $before=$fixture->facts();
   foreach(['/pilot/objects/4512','/pilot/objects/4512/assignment-order/selection']as$route){$page=$fixture->request('GET',$route,[],$limited);assertSameValue(200,$page['status'],'read/select page remains available');assertSameValue(false,str_contains($page['body'],'href="'.$originalPath.'/submit"'),'INTENDED_RED original form link requires read plus write grant');}
   assertSameValue(403,$fixture->request('GET',$originalPath.'/submit',[],$limited)['status'],'same form admission denies missing read');
   assertSameValue($before,$fixture->facts(),'affordance reads do not change facts');
  } finally {$fixture->insert($fixture->p.'fm2_pilot_role_permissions',['role_id'=>$role,'permission'=>'assignment_order.original.read']);}
  $restored=$fixture->request('GET','/pilot/objects/4512/assignment-order/selection',[],$limited);assertSameValue(200,$restored['status'],'restored read grant page');assertSameValue(true,str_contains($restored['body'],'href="'.$originalPath.'/submit"'),'full read/write grants expose working form link');
 }
};
try {
 $f=new PreopeningFixture(dirname(__DIR__,2));assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'existing public selection fixture');$f->start();$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'native FKR');
 $path='/pilot/objects/4512/assignment-orders/81/originals';$form=$f->request('GET',$path.'/submit',[],$cookies);assertSameValue(200,$form['status'],'INTENDED_RED Yii original form');$f->noLegacy();
 foreach(['documentDate','compositionConfirmed','original','Монтажник 7001','Инженер теста']as$text)assertSameValue(true,str_contains($form['body'],$text),'original form '.$text);
 $pdf=FMonitor2\Tests\Support\SelectedOriginalFixture::pdf();$pdf2=FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 144 144] >>']);$meta=$f->metadata($cookies);$encode=static fn(array$v):string=>base64_encode(json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS|JSON_THROW_ON_ERROR));
 $headers=static fn(string $encoded,string $csrf,string $media='application/pdf'):array=>['Content-Type: '.$media,'X-CSRF-Token: '.$csrf,'X-FMonitor-Original: '.$encoded];
 $before=$f->facts();$files=$f->base->privateFiles();
 foreach([
  [$headers($encode($meta),'wrong'),$pdf,400,'native CSRF'],
  [$headers('not-base64',$meta['csrfToken']),$pdf,400,'malformed metadata'],
  [$headers(base64_encode(json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).' '),$meta['csrfToken']),$pdf,400,'noncanonical whitespace'],
  [$headers($encode($meta),$meta['csrfToken'],'multipart/form-data; boundary=x'),$pdf,415,'multipart not accepted'],
  [$headers($encode($meta),$meta['csrfToken']),str_repeat('x',20971521),413,'body limit'],
 ]as[$wire,$bytes,$status,$label]){assertSameValue($status,$f->request('POST',$path,[],$cookies,$wire,$bytes)['status'],$label);assertSameValue($before,$f->facts(),'pre-command rejection zero facts '.$label);assertSameValue($files,$f->base->privateFiles(),'pre-command rejection zero files');}
 foreach([
  ['Content-Type: application/pdf','X-CSRF-Token: '.$meta['csrfToken']],
  ['Content-Type: application/pdf','X-FMonitor-Original: '.$encode($meta)],
  $headers(str_repeat('A',16385),$meta['csrfToken']),
  $headers($encode(array_replace($meta,['csrfToken'=>'different'])),$meta['csrfToken']),
 ]as$wire){assertSameValue(400,$f->request('POST',$path,[],$cookies,$wire,$pdf)['status'],'missing/mismatched/bounded metadata');assertSameValue($before,$f->facts(),'header rejection before native owner');}
 foreach(['extra','type','mode','uuid','duplicate']as$kind){
  $bad=$meta;if($kind==='extra')$bad['actorId']=18;if($kind==='type')$bad['compositionConfirmed']='true';if($kind==='mode')$bad['mode']='INITIAL';if($kind==='uuid')$bad['requestId']='not-uuid';
  $encoded=$kind==='duplicate'?base64_encode(substr(base64_decode($encode($meta)),0,-1).',"mode":"initial"}'):$encode($bad);
  assertSameValue(400,$f->request('POST',$path,[],$cookies,$headers($encoded,$meta['csrfToken']),$pdf)['status'],'closed metadata '.$kind);assertSameValue($before,$f->facts(),'metadata rejection exact');
 }
 foreach([94,95,73,96]as$actor){$other=[];assertSameValue(303,$f->login($other,$actor)['status'],'other actor login');$bad=$f->metadata($other);$prior=$f->facts();assertSameValue(403,$f->upload($other,$bad)['status'],'no upload grant '.$actor);assertSameValue($prior,$f->facts(),'denial before native command audit');}
 $formLinkAdmission($f,$path);
 $meta=$f->metadata($cookies);$wrong=$f->facts();assertSameValue(404,$f->upload($cookies,$meta,$pdf,9999)['status'],'unknown order');assertSameValue($wrong,$f->facts(),'missing context no audit');
 $before=$f->facts();$wire=$headers($encode($meta),$meta['csrfToken']);$missingLength=$f->wire('POST',$path,$wire,'',$cookies);assertSameValue([411,false],[$missingLength['status'],$missingLength['timedOut']],'missing declared length rejected');assertSameValue($before,$f->facts(),'length rejection before command');
 $incomplete=$f->wire('POST',$path,array_merge($wire,['Content-Length: '.(strlen($pdf)+10)]),$pdf,$cookies);assertSameValue(false,$incomplete['timedOut'],'incomplete framed request is bounded');assertSameValue(true,in_array($incomplete['status'],[0,400],true),'server rejects incomplete framing before PHP');assertSameValue($before,$f->facts(),'framing rejection preserves all facts');assertSameValue(200,$f->request('GET','/pilot/objects',[],$cookies)['status'],'server healthy after bad framing');
 foreach([
  array_merge($wire,['Transfer-Encoding: chunked']),
  array_merge($wire,['Content-Length: 3']),
 ]as$framing){$before=$f->facts();$badFrame=$f->wire('POST',$path,$framing,$pdf,$cookies);assertSameValue(false,$badFrame['timedOut'],'malformed wire bounded');assertSameValue(true,in_array($badFrame['status'],[0,400],true),'bad framing rejected by server or transport');assertSameValue($before,$f->facts(),'framing no command facts');}
 // Original transport inherits UUID versions 1–5; selection/opening keep their separate v4 contract.
 $meta['requestId']='22222222-2222-1222-8222-000000000001';
 $response=$f->upload($cookies,$meta,$pdf);assertSameValue(201,$response['status'],'accepted original');$receipt=json_decode($response['body'],true,flags:JSON_THROW_ON_ERROR);
 assertSameValue(['status','reasonCode','retryable','requestId','rootOriginalId','currentRevisionId','revisionNumber','documentDate','sha256','byteSize','uploadedAt'],array_keys($receipt),'canonical native Result keys');assertSameValue(['accepted',null,false,1,'2026-09-01',hash('sha256',$pdf),strlen($pdf)],[$receipt['status'],$receipt['reasonCode'],$receipt['retryable'],$receipt['revisionNumber'],$receipt['documentDate'],$receipt['sha256'],$receipt['byteSize']],'exact received PDF receipt');
 assertSameValue(json_encode($receipt,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS|JSON_THROW_ON_ERROR)."\n",$response['body'],'canonical receipt bytes and terminal LF');
 $revision1=$receipt['currentRevisionId'];$root=$receipt['rootOriginalId'];$rows=$f->rows('fm2_assignment_order_original_revisions');$files=$f->base->privateFiles();
 $replay=$f->upload($cookies,$meta,$pdf);assertSameValue(200,$replay['status'],'exact original replay');assertSameValue('replayed',json_decode($replay['body'],true)['status'],'native replay receipt');assertSameValue($rows,$f->rows('fm2_assignment_order_original_revisions'),'no duplicate original');assertSameValue($files,$f->base->privateFiles(),'replay preserves bytes');
 $correction=$f->metadata($cookies,'22222222-2222-4222-8222-000000000002');$correction['mode']='correction';$correction['documentDate']='2026-09-02';$correction['rootOriginalId']=$root;$correction['targetRevisionId']=$revision1;$correction['expectedCurrentRevisionId']=$revision1;$correction['correctionReason']='Уточнена дата';
 $changed=$f->upload($cookies,$correction,$pdf2);assertSameValue(201,$changed['status'],'correction appended');$receipt2=json_decode($changed['body'],true);assertSameValue(2,$receipt2['revisionNumber'],'revision2');$revision2=$receipt2['currentRevisionId'];$now=$f->rows('fm2_assignment_order_original_revisions');assertSameValue(2,count($now),'two immutable revisions');assertSameValue($rows[0],$now[0],'revision1 never rewritten');
 // Accepted fingerprint replay precedes stale-target validation, including a distinct request ID.
 $sameIntent=array_replace($correction,['requestId'=>'22222222-2222-4222-8222-000000000004']);$beforeReplay=$f->facts();$replayFiles=$f->base->privateFiles();
 $semanticReplay=$f->upload($cookies,$sameIntent,$pdf2);assertSameValue(200,$semanticReplay['status'],'distinct-ID identical correction is replayed');
 assertSameValue(array_replace($receipt2,['status'=>'replayed','requestId'=>$sameIntent['requestId']]),json_decode($semanticReplay['body'],true,flags:JSON_THROW_ON_ERROR),'semantic replay echoes caller ID and exact accepted evidence');
 assertSameValue($beforeReplay,$f->facts(),'semantic replay adds no terminal row or audit');assertSameValue($replayFiles,$f->base->privateFiles(),'semantic replay preserves bytes');
 $correction['requestId']='22222222-2222-4222-8222-000000000003';$correction['documentDate']='2026-09-03';$stale=$f->upload($cookies,$correction,$pdf2);assertSameValue(409,$stale['status'],'different intent with stale target conflicts');assertSameValue('stale_revision',json_decode($stale['body'],true,flags:JSON_THROW_ON_ERROR)['reasonCode'],'native stale reason preserved');assertSameValue($now,$f->rows('fm2_assignment_order_original_revisions'),'conflict preserves revisions');
 $history=$f->request('GET',$path.'/history',[],$cookies);assertSameValue(200,$history['status'],'history HTML');foreach([$revision1,$revision2,'Редакция 1','Редакция 2']as$marker)assertSameValue(true,str_contains($history['body'],$marker),'history revision link '.$marker);
 foreach([$revision1=>$pdf,$revision2=>$pdf2]as$revision=>$expectedPdf){$before=$f->facts();$files=$f->base->privateFiles();$download=$f->request('GET',$path.'/'.$revision.'/download',[],$cookies,['Range: bytes=0-9','If-None-Match: anything']);assertSameValue([200,$expectedPdf],[$download['status'],$download['body']],'full exact historical PDF');assertSameValue('attachment; filename="assignment-order-original.pdf"',$download['headers']['content-disposition'][0]??null,'fixed safe filename');assertSameValue((string)strlen($expectedPdf),$download['headers']['content-length'][0]??null,'exact content length');assertSameValue(false,isset($download['headers']['etag']),'no conditional shortcut');$head=$f->request('HEAD',$path.'/'.$revision.'/download',[],$cookies);assertSameValue([200,''],[$head['status'],$head['body']],'download HEAD same integrity');assertSameValue($before,$f->facts(),'history/download no facts');assertSameValue($files,$f->base->privateFiles(),'history/download no files');$f->noLegacy();}
 // The transport must not turn a native integrity failure into partial bytes, including HEAD.
 $stored=$f->base->privateFiles();$relative=array_search(hash('sha256',$pdf),$stored,true);assertSameValue(true,is_string($relative),'independent old PDF file located by hash');$privatePath=$f->base->privateRoot.'/'.$relative;
 $savedBytes=file_get_contents($privatePath);file_put_contents($privatePath,'corrupt');
 try {$before=$f->facts();$corruptFiles=$f->base->privateFiles();foreach(['GET','HEAD']as$method){$unavailable=$f->request($method,$path.'/'.$revision1.'/download',[],$cookies);assertSameValue(503,$unavailable['status'],'corrupt historical PDF unavailable '.$method);assertSameValue(false,str_contains($unavailable['body'],'corrupt'),'no partial evidence');if($method==='HEAD')assertSameValue('',$unavailable['body'],'failed HEAD has no body');}assertSameValue($before,$f->facts(),'integrity failure no DB repair');assertSameValue($corruptFiles,$f->base->privateFiles(),'integrity failure no file repair');}
 finally {file_put_contents($privatePath,$savedBytes);}
 assertSameValue($stored,$f->base->privateFiles(),'test restores exact private bytes');
 foreach(['missing','hardlink','symlink','loose','busy']as$fault){
  $backup=$f->base->control.'/private-test-backup';$lock=null;
  if($fault==='missing'||$fault==='symlink'){rename($privatePath,$backup);if($fault==='symlink')symlink($backup,$privatePath);}
  if($fault==='hardlink')link($privatePath,$backup);
  if($fault==='loose')chmod($privatePath,0644);
  if($fault==='busy'){$lock=fopen($f->base->privateRoot.'/.lock-'.hash('sha256','content-sha256-'.hash('sha256',$pdf)),'r+b');assertSameValue(true,flock($lock,LOCK_EX|LOCK_NB),'fixture holds conflicting lease');}
  try{$before=$f->facts();foreach(['GET','HEAD']as$method){$r=$f->request($method,$path.'/'.$revision1.'/download',[],$cookies);assertSameValue(503,$r['status'],'unsafe private evidence '.$fault.' '.$method);assertSameValue(false,str_starts_with($r['body'],'%PDF-'),'never publishes unsafe bytes');}assertSameValue($before,$f->facts(),'unsafe download no facts');}
  finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}if($fault==='loose')chmod($privatePath,0600);if($fault==='hardlink')unlink($backup);if($fault==='symlink')unlink($privatePath);if($fault==='missing'||$fault==='symlink')rename($backup,$privatePath);}
 }
 assertSameValue([200,$pdf],array_values(array_intersect_key($f->request('GET',$path.'/'.$revision1.'/download',[],$cookies),array_flip(['status','body']))),'exact bytes after released faults');

 $formLinkAdmission($f,$path);
 $unappliedEngineer=[];$f->login($unappliedEngineer,73);$beforeRead=$f->facts();$unappliedCard=$f->request('GET','/pilot/objects/4512',[],$unappliedEngineer);assertSameValue(200,$unappliedCard['status'],'selected engineer may read card');
 foreach([$path.'/history',$path.'/'.$revision2.'/download']as$link)assertSameValue(false,str_contains($unappliedCard['body'],'href="'.$link.'"'),'selected engineer has no document link before application');assertSameValue($beforeRead,$f->facts(),'unapplied card affordances read only');
 // Corrupt only the new grant query's role-code dependency; card facts/identity still read normally.
 $rolesTable=$f->p.'fm2_pilot_roles';$f->db->query("ALTER TABLE $rolesTable RENAME COLUMN code TO unavailable_code");
 try {
  $beforeAdmission=$f->facts();$admissionFiles=$f->base->privateFiles();$observed=[];
  foreach(['card'=>'/pilot/objects/4512','selection'=>'/pilot/objects/4512/assignment-order/selection','form'=>$path.'/submit','history'=>$path.'/history']as$surface=>$route){$actorCookies=in_array($surface,['card','history'],true)?$unappliedEngineer:$cookies;foreach(['GET','HEAD']as$method){$unavailable=$f->request($method,$route,[],$actorCookies);$observed[$surface.' '.$method]=$unavailable['status'];if($method==='HEAD')assertSameValue('',$unavailable['body'],'unavailable read HEAD empty');assertSameValue(false,str_contains($unavailable['body'],'href="'.$path.'/history"'),'no partial document controls');}}
  assertSameValue(array_fill_keys(['card GET','card HEAD','selection GET','selection HEAD','form GET','form HEAD','history GET','history HEAD'],503),$observed,'INTENDED_RED unavailable admission maps503 at every read consumer');
  assertSameValue($beforeAdmission,$f->facts(),'admission failure performs no data or schema repair');assertSameValue($admissionFiles,$f->base->privateFiles(),'admission failure preserves PDFs');
 }
 finally{$f->db->query("ALTER TABLE $rolesTable RENAME COLUMN unavailable_code TO code");}
 foreach([94=>403,95=>403,96=>200,73=>403]as$actor=>$status){$other=[];assertSameValue(303,$f->login($other,$actor)['status'],'reader role login');assertSameValue($status,$f->request('GET',$path.'/'.$revision1.'/download',[],$other)['status'],'reader role union before assignment '.$actor);}
 $baseRows=$f->rows('fm2_assignment_order_original_revisions');
 foreach([['not_pdf','not a PDF'],['unsafe_pdf',FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus::forbidden('JavaScript')]]as$i=>[$label,$bytes]){$bad=$f->metadata($cookies,sprintf('22222222-2222-4222-8222-%012d',10+$i));$bad['mode']='correction';$bad['rootOriginalId']=$root;$bad['targetRevisionId']=$revision2;$bad['expectedCurrentRevisionId']=$revision2;$bad['correctionReason']='Проверка отказа';$rejected=$f->upload($cookies,$bad,$bytes);assertSameValue(422,$rejected['status'],$label);$reason=json_decode($rejected['body'],true,flags:JSON_THROW_ON_ERROR);assertSameValue($label,$reason['reasonCode'],'native rejection reason preserved');assertSameValue($baseRows,$f->rows('fm2_assignment_order_original_revisions'),'invalid PDF no accepted facts');}
 assertSameValue(0,count($f->rows('fm2_assignment_order_applications')),'original work stays separate before explicit application fixture');
 $applied=FMonitor2\AssignmentOrderComposition\ProductionAssignmentOrderApplicationFactory::create($f->db,$f->p)->applyAssignmentOrderOriginal(new FMonitor2\AssignmentOrderComposition\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000001',4512,81,$revision2,0,18));assertSameValue('applied',$applied->status,'public current assignment fixture');
 $engineer=[];assertSameValue(303,$f->login($engineer,73)['status'],'assigned engineer login');$before=$f->facts();assertSameValue(200,$f->request('GET',$path.'/'.$revision1.'/download',[],$engineer)['status'],'current engineer may read historical original');assertSameValue($before,$f->facts(),'authorized engineer download read only');

 $appliedCard=$f->request('GET','/pilot/objects/4512',[],$engineer);assertSameValue(200,$appliedCard['status'],'applied engineer card');foreach([$path.'/history',$path.'/'.$revision2.'/download']as$link)assertSameValue(true,str_contains($appliedCard['body'],'href="'.$link.'"'),'current applied engineer sees document link');
 // A known assigned engineer is unavailable, not forbidden, when assignment evidence cannot be read.
 $assignmentTable=$f->p.'fm2_assignment_order_applications';$unavailableTable=$assignmentTable.'_unavailable';$f->db->query("RENAME TABLE $assignmentTable TO $unavailableTable");
 try{$beforeUnavailable=$f->facts();$filesUnavailable=$f->base->privateFiles();foreach(['GET','HEAD']as$method){$unavailable=$f->request($method,$path.'/'.$revision1.'/download',[],$engineer);assertSameValue(503,$unavailable['status'],'INTENDED_RED assignment lookup failure is unavailable, not authorization denial');assertSameValue('60',$unavailable['headers']['retry-after'][0]??null,'read unavailable retry guidance');}assertSameValue($beforeUnavailable,$f->facts(),'assignment-read failure performs no repair');assertSameValue($filesUnavailable,$f->base->privateFiles(),'assignment-read failure preserves PDF');}
 finally{$f->db->query("RENAME TABLE $unavailableTable TO $assignmentTable");}
 $faultMetadata=$f->metadata($cookies,'22222222-2222-4222-8222-000000000098');$faultMetadata=array_replace($faultMetadata,['mode'=>'correction','rootOriginalId'=>$root,'targetRevisionId'=>$revision2,'expectedCurrentRevisionId'=>$revision2,'correctionReason'=>'Проверка отказа хранения']);
 $trigger=$f->p.'original_persistence_fault';$f->db->query("CREATE TRIGGER $trigger BEFORE INSERT ON {$f->p}fm2_assignment_order_original_revisions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic original fault'");
 try{$before=$f->rows('fm2_assignment_order_original_revisions');$failed=$f->upload($cookies,$faultMetadata,$pdf);assertSameValue(503,$failed['status'],'native original persistence failure');assertSameValue('60',$failed['headers']['retry-after'][0]??null,'native failed retry-after');$data=json_decode($failed['body'],true,flags:JSON_THROW_ON_ERROR);assertSameValue(array_keys($receipt),array_keys($data),'invoked failure retains11-field Result');assertSameValue(['failed','persistence_failure',true,$faultMetadata['requestId']],[$data['status'],$data['reasonCode'],$data['retryable'],$data['requestId']],'exact native failed reason');assertSameValue(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",$failed['body'],'canonical native failure bytes');assertSameValue($before,$f->rows('fm2_assignment_order_original_revisions'),'failed persistence no accepted revision');}
 finally{$f->db->query("DROP TRIGGER $trigger");}
 $maximum=$pdf.str_repeat(' ',20971520-strlen($pdf));assertSameValue(FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector())->inspect($maximum)->status,'valid exact-cap corpus');
 $maxMeta=$f->metadata($cookies,'22222222-2222-4222-8222-000000000099');$maxMeta=array_replace($maxMeta,['mode'=>'correction','rootOriginalId'=>$root,'targetRevisionId'=>$revision2,'expectedCurrentRevisionId'=>$revision2,'correctionReason'=>'Проверка размера']);$maxResult=$f->upload($cookies,$maxMeta,$maximum);assertSameValue(201,$maxResult['status'],'exact20MiB accepted through Yii');$maxReceipt=json_decode($maxResult['body'],true,flags:JSON_THROW_ON_ERROR);assertSameValue([20971520,hash('sha256',$maximum)],[$maxReceipt['byteSize'],$maxReceipt['sha256']],'exact received boundary bytes');

 echo "PASS: YII2-PREOPENING-JOURNEY-001 raw original transport, history, roles and bytes\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}

// Raw upload admission deliberately does not inherit the form's original.read requirement.
foreach ([18=>1,97=>7] as $rawActor=>$rawRole) {
 $rawFixture=null;
 try {
  $rawFixture=new PreopeningFixture(dirname(__DIR__,2));$rawFixture->start();$rawCookies=[];$rawFixture->login($rawCookies,$rawActor);
  assertSameValue(303,$rawFixture->selection($rawCookies)['status'],'raw-only actor selected composition through HTTP');
  $rawFixture->db->query("DELETE FROM {$rawFixture->p}fm2_pilot_role_permissions WHERE role_id=$rawRole AND permission='assignment_order.original.read'");
  $rawMetadata=$rawFixture->metadata($rawCookies);$first=$rawFixture->upload($rawCookies,$rawMetadata);assertSameValue(201,$first['status'],'initial raw POST needs write grant, not form read grant');
  $receipt=json_decode($first['body'],true,flags:JSON_THROW_ON_ERROR);$initialRows=$rawFixture->rows('fm2_assignment_order_original_revisions');assertSameValue(1,count($initialRows),'one raw initial fact');assertSameValue((string)$rawActor,$initialRows[0]['actor_user_id'],'raw actor attribution');
  assertSameValue(403,$rawFixture->request('GET','/pilot/objects/4512/assignment-orders/81/originals/submit',[],$rawCookies)['status'],'form still requires read after raw acceptance');
  $rawMetadata=array_replace($rawFixture->metadata($rawCookies,'22222222-2222-4222-8222-000000000002'),['mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$receipt['rootOriginalId'],'targetRevisionId'=>$receipt['currentRevisionId'],'expectedCurrentRevisionId'=>$receipt['currentRevisionId'],'correctionReason'=>'Уточнена дата']);
  $corrected=$rawFixture->upload($rawCookies,$rawMetadata);assertSameValue(201,$corrected['status'],'correction raw POST needs correct grant, not form read grant');
  $rows=$rawFixture->rows('fm2_assignment_order_original_revisions');assertSameValue(2,count($rows),'raw correction appends one fact');assertSameValue($initialRows[0],$rows[0],'raw correction never rewrites first fact');assertSameValue((string)$rawActor,$rows[1]['actor_user_id'],'correction actor attribution');assertSameValue('2026-09-02',$rows[1]['document_date'],'correction date stored');
  assertSameValue(0,count($rawFixture->rows('fm2_assignment_order_applications')),'raw write does not apply composition');assertSameValue(null,$rawFixture->rows('fm2_installation_cases')[0]['actual_start_date'],'raw write does not open');$rawFixture->noLegacy();
 } finally {if($rawFixture instanceof PreopeningFixture)$rawFixture->close();}
}
echo "PASS: raw initial/correction retain write-only admission for FKR and manager\n";
