<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/SelectionSchemaWorkerControl.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\OriginalHistoryFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalFixture as Native;
use FMonitor2\Tests\Support\SelectionNativeFixture as Selection;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\AssignmentOrderComposition as C;
// ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001 v0.1, native public production port.
function historyPage(object $r,string $status='found',int $object=4512,int $order=81,int $after=0,int $limit=50):?object {
    $x=$r->readHistory($object,$order,$after,$limit);assertSameValue($status,$x->status->value,'exact history status');assertSameValue($status==='found',$x->page!==null,'history iff found');return $x->page;
}
function historyPdf(object $r,string $id,string $status='found',int $object=4512,int $order=81):?object {
    $x=$r->prepareDownload($object,$order,$id);assertSameValue($status,$x->status->value,'exact prepared PDF status');assertSameValue($status==='found',$x->download!==null,'prepared PDF iff found');return $x->download;
}
$tests=[
'pagination and exact historical bytes'=>static function(F $f):void {
    $r=$f->reader();$f->observe(function()use($f,$r){
        $page=historyPage($r);$expected=$f->context(true)+['totalRevisions'=>2,'afterRevisionNumber'=>0,'nextAfterRevisionNumber'=>null,'revisions'=>[$f->revision(false),$f->revision(true)]];
        assertSameValue($expected,$page->metadata(),'exact independent history whitelist');$copy=$page->metadata();$copy['revisions'][0]['documentDate']='2000-01-01';assertSameValue($expected,$page->metadata(),'history copy immutable');
        assertSameValue($f->context(true)+['totalRevisions'=>2,'afterRevisionNumber'=>0,'nextAfterRevisionNumber'=>1,'revisions'=>[$f->revision(false)]],historyPage($r,'found',4512,81,0,1)->metadata(),'first page cursor');
        assertSameValue($f->context(true)+['totalRevisions'=>2,'afterRevisionNumber'=>1,'nextAfterRevisionNumber'=>null,'revisions'=>[$f->revision(true)]],historyPage($r,'found',4512,81,1,1)->metadata(),'second page cursor');
        foreach([2,9] as $after)assertSameValue($f->context(true)+['totalRevisions'=>2,'afterRevisionNumber'=>$after,'nextAfterRevisionNumber'=>null,'revisions'=>[]],historyPage($r,'found',4512,81,$after)->metadata(),'cursor at or past end');
        foreach([false,true] as $second){$revision=$f->revision($second);$pdf=historyPdf($r,$revision['revisionId']);assertSameValue($f->context(false)+['revision'=>$revision],$pdf->metadata(),'exact historical prepared metadata');$bytes=$second?F::otherPdf():Native::pdf();assertSameValue($bytes,$pdf->bytes(),'selected immutable PDF, not current leaf');assertSameValue($revision['sha256'],hash('sha256',$pdf->bytes()),'independent PDF digest');$copy=$pdf->metadata();$copy['revision']['sha256']='changed';assertSameValue($revision,$pdf->metadata()['revision'],'prepared metadata immutable');$buffer=$pdf->bytes();$buffer[0]='x';assertSameValue($bytes,$pdf->bytes(),'prepared bytes immutable');}
    });
},
'prepared snapshot survives correction and pending order'=>static function(F $f):void {
    $r=$f->reader();$page=historyPage($r);$old=historyPdf($r,$f->first->currentRevisionId());$before=$page->metadata();
    $third=$f->correct($f->second,Native::pdf(),3,'2026-09-02');assertSameValue(['accepted',3],[$third->status()->value,$third->revisionNumber()],'third public correction');
    assertSameValue($before,$page->metadata(),'already obtained page not live');assertSameValue(Native::pdf(),$old->bytes(),'old prepared bytes survive later correction');
    $f->observe(function()use($r,$f,$third){$next=historyPage($r,'found',4512,81,2,1)->metadata();assertSameValue([3,$third->currentRevisionId(),3,null],[$next['totalRevisions'],$next['currentRevisionId'],$next['revisions'][0]['revisionNumber'],$next['nextAfterRevisionNumber']],'cursor sees newly appended correction');historyPdf($r,$f->first->currentRevisionId());});
    $c=Selection::command(2,1,7002);$new=new C\SelectAssignmentOrderCompositionCommand($c->requestId,C\AssignmentOrderCompositionMode::NEW_ORDER,$c->installationObjectId,$c->actorUserId,$c->installerTabIds,$c->controlEngineerUserId,$c->expectedSelectionRevision);assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition($new)->status()->value,'new pending order82');
    $f->observe(function()use($r,$f){historyPage($r);historyPdf($r,$f->first->currentRevisionId());historyPage($r,'not_found',4512,82);historyPdf($r,$f->first->currentRevisionId(),'not_found',4512,82);});
    $other=$f->base->app()->submitAssignmentOrderOriginal(new O\SubmitAssignmentOrderOriginalCommand('33333333-3333-4333-8333-000000000004',O\AssignmentOrderOriginalMode::INITIAL,4512,82,18,'2026-09-04',true,null,null,null,null,new O\AssignmentOrderOriginalUpload(new FMonitor2\Tests\Support\SelectedOriginalInput(Native::pdf()),'signed.pdf','application/pdf')));
    assertSameValue('accepted',$other->status()->value,'second order has its own accepted original');
    $f->observe(function()use($r,$f,$other){historyPdf($r,$f->first->currentRevisionId(),'not_found',4512,82);historyPdf($r,$other->currentRevisionId(),'not_found');assertSameValue(82,historyPdf($r,$other->currentRevisionId(),'found',4512,82)->metadata()['orderId'],'own second-order original found');});
},
'arguments absence and configuration'=>static function(F $f):void {
    $r=$f->reader();$id=$f->first->currentRevisionId();$f->observe(function()use($r,$id){
        foreach([[0,81,0,50],[-1,81,0,50],[4512,0,0,50],[4512,81,-1,50],[4512,81,4294967296,50],[4512,81,0,0],[4512,81,0,101]] as $args)historyPage($r,'invalid_argument',...$args);
        foreach(['','a/b','a\\b','white space',str_repeat('a',81),"a\0b"] as $bad)historyPdf($r,$bad,'invalid_argument');
        historyPdf($r,$id,'invalid_argument',0);historyPage($r,'not_found',4513);historyPage($r,'not_found',4512,999);historyPdf($r,'revision-absent','not_found');historyPdf($r,$id,'not_found',4513);
    });
    $closed=$f->base->selection->schema->source->connect($f->base->selection->schema->source->name);$closed->close();
    foreach([[str_repeat('p',26),$f->base->privateRoot],['bad-',$f->base->privateRoot],['','relative'],['',"/bad\0root"]] as [$p,$root]){try{O\AssignmentOrderOriginalHistoryReaderFactory::create($closed,$root,$p);throw new TestFailure('invalid factory configuration accepted');}catch(O\AssignmentOrderOriginalHistoryConfigurationUnavailable $e){assertSameValue(['Original history configuration unavailable.',0,null],[$e->getMessage(),$e->getCode(),$e->getPrevious()],'fixed sanitized config before closed DB');}}
    $dead=O\AssignmentOrderOriginalHistoryReaderFactory::create($closed,$f->base->privateRoot,$f->prefix);historyPage($dead,'invalid_argument',0);historyPage($dead,'unavailable');historyPdf($dead,$id,'unavailable');
    $wrong=O\AssignmentOrderOriginalHistoryReaderFactory::create($f->base->selection->db,$f->base->privateRoot,'wrong_');historyPage($wrong,'unavailable');historyPdf($wrong,$id,'unavailable');
},
'caller transaction charset and null database'=>static function(F $f):void {
    $r=$f->reader();$db=$f->base->selection->db;$id=$f->first->currentRevisionId();$db->query('CREATE TABLE fixture_history_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$db->begin_transaction();
    try{$db->query('INSERT INTO fixture_history_sentinel VALUES(1)');historyPage($r,'unavailable');historyPdf($r,$id,'unavailable');assertSameValue(['1','1'],[$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],$db->query('SELECT COUNT(*) n FROM fixture_history_sentinel')->fetch_assoc()['n']],'caller TX and uncommitted write retained');}finally{$db->rollback();}
    assertSameValue('0',$db->query('SELECT COUNT(*) n FROM fixture_history_sentinel')->fetch_assoc()['n'],'caller write not committed');$db->set_charset('latin1');try{historyPage($r,'unavailable');historyPdf($r,$id,'unavailable');assertSameValue('latin1',$db->character_set_name(),'charset preserved');}finally{$db->set_charset('utf8mb4');}
    $none=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',null,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    try{$none->set_charset('utf8mb4');$empty=O\AssignmentOrderOriginalHistoryReaderFactory::create($none,$f->base->privateRoot,$f->prefix);historyPage($empty,'unavailable');historyPdf($empty,$id,'unavailable');assertSameValue([null,'0'],array_values($none->query('SELECT DATABASE() db,@@in_transaction active')->fetch_assoc()),'no selected DB synthesized');}finally{$none->close();}
},
'corrupt backing not empty success'=>static function(F $f):void {
    $r=$f->reader();$db=$f->base->selection->db;$db->query('UPDATE `'.$f->prefix.'fm2_assignment_order_original_revisions` SET byte_size=328 WHERE revision_number=1');
    $f->observe(function()use($r,$f){historyPage($r,'unavailable');historyPdf($r,$f->first->currentRevisionId(),'unavailable');historyPdf($r,$f->second->currentRevisionId(),'unavailable');});
    assertSameValue('0',$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'failed owned snapshots closed');
},
'private root independent metadata and immutable buffer'=>static function(F $f):void {
    $r=$f->reader();$pdf=historyPdf($r,$f->first->currentRevisionId());$before=$f->files();$root=$f->base->privateRoot;rename($root,$root.'-held');
    try{historyPage($r);historyPdf($r,$f->first->currentRevisionId(),'unavailable');assertSameValue(Native::pdf(),$pdf->bytes(),'prepared buffer survives unavailable root');}finally{rename($root.'-held',$root);}assertSameValue($before,$f->files(),'root restored exact');
    $alias=$f->base->control.'/private-alias';symlink($root,$alias);try{$other=$f->reader($alias);historyPage($other);historyPdf($other,$f->first->currentRevisionId(),'unavailable');}finally{unlink($alias);}
},
'file integrity aliases and missing lease'=>static function(F $f):void {
    $r=$f->reader();$id=$f->first->currentRevisionId();$path=$f->base->privateRoot.'/content-78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62.pdf';$bytes=file_get_contents($path);
    foreach([substr($bytes,0,-1),$bytes.'x','x'.substr($bytes,1)] as $bad){file_put_contents($path,$bad);try{$f->observe(function()use($r,$id){historyPage($r);historyPdf($r,$id,'unavailable');});}finally{file_put_contents($path,$bytes);}}
    rename($path,$path.'-held');try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));symlink($path.'-held',$path);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{unlink($path);}mkdir($path,0700);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{rmdir($path);}}finally{rename($path.'-held',$path);}
    $alias=$f->base->control.'/content-hardlink';link($path,$alias);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{unlink($alias);}
    $lock=$f->base->privateRoot.'/.lock-'.hash('sha256','content-sha256-78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62');rename($lock,$lock.'-held');try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{rename($lock.'-held',$lock);}
    $root=$f->base->privateRoot;chmod($root,0750);try{$f->observe(fn()=>historyPdf($r,$id));}finally{chmod($root,0700);}
    foreach([$root=>0755,$path=>0644,$lock=>0644] as $target=>$mode){clearstatcache(true,$target);$originalMode=fileperms($target)&0777;chmod($target,$mode);try{$f->observe(function()use($r,$id){historyPage($r);historyPdf($r,$id,'unavailable');});}finally{chmod($target,$originalMode);}}
    rename($lock,$lock.'-held');try{symlink($lock.'-held',$lock);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{unlink($lock);}mkdir($lock,0700);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{rmdir($lock);}}finally{rename($lock.'-held',$lock);}
    $lockAlias=$f->base->control.'/digest-hardlink';link($lock,$lockAlias);try{$f->observe(fn()=>historyPdf($r,$id,'unavailable'));}finally{unlink($lockAlias);}
    $f->observe(fn()=>historyPdf($r,$id));assertSameValue($bytes,file_get_contents($path),'file restores exact original bytes');
},
'public digest exclusion and descriptor release'=>static function(F $f):void {
    $r=$f->reader();$observer=new class implements O\AssignmentOrderOriginalStorageObserver{public function observe(O\AssignmentOrderOriginalStorageEvent $event,?string $identity):void{}};
    $storage=O\AssignmentOrderOriginalPrivateStorageFactory::create($f->base->privateRoot,$observer,new O\AssignmentOrderOriginalNoFaults());$page=$storage->listOrphans('2026-09-06T00:00:00Z',10,null);assertSameValue('ok',$page->status()->value,'real storage inventory');$candidate=null;foreach($page->candidates() as $item)if($item->sha256===$f->first->sha256())$candidate=$item;assertSameValue(true,$candidate!==null,'accepted native content candidate');$lease=$storage->acquireDigestLock($candidate->opaqueIdentity);assertSameValue('ok',$lease->status()->value,'actual native exclusive digest lease');
    $worker=null;
    try{$f->observe(function()use($f,&$worker){
        $config=$f->base->control.'/download-worker.json';file_put_contents($config,json_encode(['database'=>$f->base->selection->schema->source->name,'prefix'=>$f->prefix,'root'=>$f->base->privateRoot,'revision'=>$f->first->currentRevisionId()],JSON_THROW_ON_ERROR));chmod($config,0600);
        $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/original_history_download_worker.php',$config],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new TestFailure('owned history worker start');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$worker=['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
        aossWaitPhase($worker,'ready');$done=aossReapWorker($worker,3);assertSameValue([0,'',false],[$done['exit'],$done['stderr'],$done['signaled']],'busy read returns without waiting for parent lease release');if(!preg_match('/^RESULT (.+)$/m',$done['stdout'],$m))throw new TestFailure('history worker result');assertSameValue(['unavailable',false],json_decode($m[1],true,512,JSON_THROW_ON_ERROR),'actual exclusive lease prevents prepared bytes');echo "NATIVE_DIGEST_LEASE_EXCLUDES_DOWNLOAD\n";
    });}finally{$lease->release();if($worker!==null)aossCleanupWorker($worker);}
    for($i=0;$i<3;$i++)$f->observe(fn()=>historyPdf($r,$f->first->currentRevisionId()));$lease=$storage->acquireDigestLock($candidate->opaqueIdentity);try{assertSameValue('ok',$lease->status()->value,'successful reads released shared lease');}finally{$lease->release();}
},
'exact twenty MiB and excess byte'=>static function(F $f):void {
    $bytes=Native::pdf().str_repeat(' ',20971520-strlen(Native::pdf()));assertSameValue(O\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new O\FMonitorPassivePdfInspector())->inspect($bytes)->status,'real bounded PDF fixture');$big=$f->correct($f->second,$bytes,3,'2026-09-02');assertSameValue(['accepted',20971520,hash('sha256',$bytes)],[$big->status()->value,$big->byteSize(),$big->sha256()],'native accepted max-size backing');$r=$f->reader();
    $f->observe(function()use($r,$big,$bytes){$pdf=historyPdf($r,$big->currentRevisionId());assertSameValue($bytes,$pdf->bytes(),'exact20MiB not truncated');assertSameValue(20971520,$pdf->metadata()['revision']['byteSize'],'metadata size bound');});
    $path=$f->base->privateRoot.'/content-'.$big->sha256().'.pdf';file_put_contents($path,'x',FILE_APPEND);try{$f->observe(fn()=>historyPdf($r,$big->currentRevisionId(),'unavailable'));}finally{file_put_contents($path,$bytes);}
},
];
$failures=0;foreach(['',str_repeat('p',25)] as $prefix)foreach($tests as $name=>$test){$f=null;$errors=[];$label=$name.' prefix'.strlen($prefix);
    try{$f=new F($prefix);echo "SETUP_OK $label\n";$test($f);}catch(Throwable $error){$errors[]=$error->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $label\n";}catch(Throwable $error){$errors[]='cleanup: '.$error->getMessage();}
    if($errors!==[]){$failures++;echo "FAIL $label: ".implode(' | ',$errors)."\n";}else echo "PASS $label\n";
}exit($failures===0?0:1);
