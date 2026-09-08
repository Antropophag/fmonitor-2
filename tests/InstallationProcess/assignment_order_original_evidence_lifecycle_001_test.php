<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: EVIDENCE-LIFECYCLE-001, public construction and read-only lifetime.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

function evidenceConfig($f,array $replace=[]):O\AssignmentOrderOriginalEvidenceReaderConfig {
 $args=[$f->host,$f->port,$f->database,$f->user,$f->passwordFile,$f->prefix,$f->privateRoot,$f->safeLog];
 return new O\AssignmentOrderOriginalEvidenceReaderConfig(...array_replace($args,$replace));
}
function evidenceFixed(callable $operation):void {
 $caught=null;try{$value=$operation();if($value instanceof O\AssignmentOrderOriginalEvidenceReader)$value->close();}catch(Throwable $e){$caught=$e;}
 assertSameValue(true,$caught instanceof O\AssignmentOrderOriginalEvidenceUnavailable,'exact unavailable class');
 assertSameValue(['Assignment-order original evidence unavailable.',0,null],[$caught->getMessage(),$caught->getCode(),$caught->getPrevious()],'fixed redacted exception');
}
integrityCase('evidence-exception',fn()=>assertSameValue(['Assignment-order original evidence unavailable.',0,null],[(new O\AssignmentOrderOriginalEvidenceUnavailable())->getMessage(),(new O\AssignmentOrderOriginalEvidenceUnavailable())->getCode(),(new O\AssignmentOrderOriginalEvidenceUnavailable())->getPrevious()],'literal fixed exception'));
integrityCase('evidence-config-declaration',function(){
 $r=new ReflectionClass(O\AssignmentOrderOriginalEvidenceReaderConfig::class);
 assertSameValue([true,true],[$r->isFinal(),$r->isReadOnly()],'immutable config');
 assertSameValue(['databaseHost','databasePort','databaseName','databaseUser','databasePasswordFile','tablePrefix','privateStorageRoot','safeLogFile'],array_map(fn($p)=>$p->getName(),$r->getConstructor()->getParameters()),'named config');
 $c=new O\AssignmentOrderOriginalEvidenceReaderConfig(databaseHost:'127.0.0.1',databasePort:23306,databaseName:'synthetic',databaseUser:'root',databasePasswordFile:'/synthetic/password',tablePrefix:'p_',privateStorageRoot:'/synthetic/private',safeLogFile:'/synthetic/log');
 assertSameValue(['127.0.0.1',23306,'synthetic','root','/synthetic/password','p_','/synthetic/private','/synthetic/log'],array_values(get_object_vars($c)),'public exact properties');
});
integrityCase('evidence-interface',function(){
 $r=new ReflectionClass(O\AssignmentOrderOriginalEvidenceReader::class);assertSameValue(true,$r->isInterface(),'promised interface');
 $expected=['domainCanonicalJson','requestsCanonicalJson','fingerprintsCanonicalJson','eventsCanonicalJson','safeAuditsCanonicalJson','maintenanceRequestsCanonicalJson','maintenanceAuditsCanonicalJson','unchangedProcessCanonicalJson','privateBlobsCanonicalJson','safeLogsCanonicalJson','close'];
 assertSameValue($expected,array_map(fn($m)=>$m->name,$r->getMethods()),'exact reader methods');
 foreach($r->getMethods() as $m){assertSameValue($m->name==='close'?'void':'string',(string)$m->getReturnType(),'exact return');if(in_array($m->name,['domainCanonicalJson','unchangedProcessCanonicalJson'],true))assertSameValue(['caseId','orderId'],array_map(fn($p)=>$p->name,$m->getParameters()),'named query');}
 assertSameValue(['config'],array_map(fn($p)=>$p->name,(new ReflectionMethod(O\AssignmentOrderOriginalEvidenceReaderFactory::class,'create'))->getParameters()),'factory named config');
});
A::case('evidence-valid-control',function($f){$r=O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f));try{assertSameValue('{"schema":"aoou-evidence-v1","caseId":4512,"orderId":81,"roots":[]}',$r->domainCanonicalJson(4512,81),'native valid control');}finally{$r->close();$r->close();}});
A::case('evidence-close-preserves-lock-domain',function($f){
 $s=new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalNoFaults());
 $stage=$s->beginStage();$stage->write('owned');$stage->abort();$stage->close();$before=$s->inventoryCanonicalJson();
 $held=$s->acquireDigestLock('stage-0001');assertSameValue('ok',$held->status()->value,'held lock control');
 try{$reader=O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f));$reader->close();$reader->close();$other=new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalNoFaults());$lock=$other->acquireDigestLock('stage-0001');try{assertSameValue('locked',$lock->status()->value,'reader close cannot unlink shared exclusion');}finally{$lock->release();}assertSameValue($before,$s->inventoryCanonicalJson(),'reader close preserves metadata');}finally{$held->release();}
});
foreach(['domainCanonicalJson','requestsCanonicalJson','fingerprintsCanonicalJson','eventsCanonicalJson','safeAuditsCanonicalJson','maintenanceRequestsCanonicalJson','maintenanceAuditsCanonicalJson','unchangedProcessCanonicalJson','privateBlobsCanonicalJson','safeLogsCanonicalJson'] as $method) A::case('evidence-after-close-'.$method,function($f)use($method){$r=O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f));$r->close();evidenceFixed(fn()=>$r->$method(...(in_array($method,['domainCanonicalJson','unchangedProcessCanonicalJson'],true)?[4512,81]:[])));$r->close();});
foreach(['prefix','database','port','noncanonical','missing','mode','symlink','password-crlf','password-double-lf','password-tab'] as $kind) A::case('evidence-invalid-'.$kind,function($f)use($kind){
 $replace=[];$owned=null;
 switch($kind){
  case 'prefix':$replace[5]='bad-prefix';break;
  case 'database':$replace[2]='bad!';break;
  case 'port':$replace[1]=65536;break;
  case 'noncanonical':$replace[6]=$f->privateRoot.'/.';break;
  case 'missing':$replace[7]=$f->privateRoot.'/missing-log';break;
  case 'mode':$owned=$f->privateRoot.'/invalid-log';file_put_contents($owned,'');chmod($owned,0640);$replace[7]=$owned;break;
  case 'symlink':$owned=$f->privateRoot.'/link-log';symlink($f->safeLog,$owned);$replace[7]=$owned;break;
  default:$owned=$f->privateRoot.'/invalid-password';$bytes=(getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local').match($kind){'password-crlf'=>"\r\n",'password-double-lf'=>"\n\n",default=>"\t"};file_put_contents($owned,$bytes);chmod($owned,0600);$replace[4]=$owned;
 }
 try{evidenceFixed(fn()=>O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f,$replace)));assertSameValue(false,file_exists($f->privateRoot.'/missing-log'),'missing log never created');}finally{if($owned!==null)unlink($owned);}
});
A::case('evidence-open-query-failure',function($f){$r=O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f,[5=>'absent_']));try{evidenceFixed(fn()=>$r->requestsCanonicalJson());}finally{$r->close();}});
A::case('evidence-open-json-failure',function($f){$log=$f->privateRoot.'/malformed-log';file_put_contents($log,'{invalid-json');chmod($log,0600);$r=null;try{$r=O\AssignmentOrderOriginalEvidenceReaderFactory::create(evidenceConfig($f,[7=>$log]));evidenceFixed(fn()=>$r->safeLogsCanonicalJson());}finally{if($r!==null)$r->close();unlink($log);}});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_EVIDENCE_LIFECYCLE_OK');
