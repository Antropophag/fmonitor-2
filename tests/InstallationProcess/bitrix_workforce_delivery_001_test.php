<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Tests\Support\BitrixDeliveryFixture as F;
use FMonitor2\Workforce as W;
// BITRIX-WORKFORCE-DELIVERY-001 v0.2. Only task-owned native HTTPS servers, no real portal/DB.
function bwdPerson(int $id):array{return ['ID'=>$id%2?(string)$id:$id,'ACTIVE'=>(bool)($id%2),'LAST_NAME'=>'Работник','NAME'=>'теста','SECOND_NAME'=>'','WORK_POSITION'=>'Монтажник','EMAIL'=>'tab'.$id.'@example.invalid','UF_XING'=>(string)$id,'UF_DEPARTMENT'=>[71],'UF_EMPLOYMENT_DATE'=>null];}
function bwdOutcome(array $result,string $status,?string $reason,int $pages,int $attempts,?int $total=null):void {$expected=['status'=>$status,'reason'=>$reason,'pages'=>$pages,'attempts'=>$attempts,'batch'=>$total===null?null:['total'=>$total]];ksort($expected);ksort($result);assertSameValue($expected,$result,'exact safe delivery outcome');}
$tests=[
'complete pages request and repeat'=>static function(F $f):void{
    $f->client(['origin'=>'https://127.0.0.1:'.$f->port.'/']);[$result,$records]=$f->fetch();bwdOutcome($result,'complete',null,2,2,51);assertSameValue(array_map('bwdPerson',range(1,51)),$records,'independent exact selected raw records');$requests=$f->requests();assertSameValue(2,count($requests),'one native request per full page');
    foreach($requests as $i=>$q){assertSameValue('/rest/7/FAKE_TOKEN_123456789/user.get',$q['path'],'origin slash joins once');assertSameValue('POST',$q['method'],'readonly method request');$headers=array_change_key_case($q['headers']);assertSameValue(['application/json','application/json','HTTP/1.1'],[$headers['content-type']??null,$headers['accept']??null,$q['version']],'exact native HTTP headers/version');assertSameValue(['sort'=>'ID','order'=>'ASC','FILTER'=>['UF_DEPARTMENT'=>[71]],'start'=>$i*50,'select'=>['ID','ACTIVE','LAST_NAME','NAME','SECOND_NAME','WORK_POSITION','EMAIL','UF_XING','UF_DEPARTMENT','UF_EMPLOYMENT_DATE']],$q['request'],'exact requested params no ACTIVE/admin extras');}
    $f->scenario('raw_nulls');[$rawResult,$rawRows]=$f->fetch();bwdOutcome($rawResult,'complete',null,2,2,51);$expected=bwdPerson(1);$expected=array_replace($expected,['LAST_NAME'=>'  Работник  ','NAME'=>null,'SECOND_NAME'=>null,'WORK_POSITION'=>'  Монтажник  ','EMAIL'=>null,'UF_XING'=>' 1 ','UF_DEPARTMENT'=>['71'],'UF_EMPLOYMENT_DATE'=>'unparsed source date']);assertSameValue($expected,$rawRows[0],'raw nullable strings are preserved for later normalization');
    foreach(['reordered'=>51,'fifty'=>50,'zero'=>0] as $mode=>$total){$f->scenario($mode);[$next,$rows]=$f->fetch();bwdOutcome($next,'complete',null,$total===51?2:1,$total===51?2:1,$total);assertSameValue($total===0?[]:array_map('bwdPerson',range(1,$total)),$rows,'complete alternate page shape');}assertSameValue(51,count($records),'previous result not live');
},
'schema and scope fail closed'=>static function(F $f):void{
    $f->client();foreach(['invalid_json','duplicate_json','unknown_envelope','missing_field','unknown_field','wrong_active','wrong_total','wrong_id','wrong_department','long_string','result_object','negative_total','time_array','deep','scope'] as $mode){$f->scenario($mode);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed',$mode==='scope'?'scope_invalid':'schema_invalid',0,1);assertSameValue(null,$rows,'no partial records on validation failure');}
},
'pagination complete response required'=>static function(F $f):void{
    $f->client();foreach(['next_jump','next_string','missing_next','short','overfull','extra_next','drift','overlap','decreasing'] as $mode){$f->scenario($mode);[$r,$rows]=$f->fetch();$second=in_array($mode,['drift','overlap'],true);bwdOutcome($r,'failed','pagination_invalid',$second?1:0,$second?2:1);assertSameValue(null,$rows,'earlier page not returned');}
},
'HTTP API failures and retry'=>static function(F $f):void{
    $f->client();foreach(['401','403','404','500','api_error'] as $mode){$f->scenario($mode);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed',in_array($mode,['401','403'],true)?'authorization_failed':($mode==='api_error'?'api_failed':'transport_failed'),0,1);assertSameValue(null,$rows,'error bodies not returned');}
    $f->scenario('retry_then_ok');$start=hrtime(true);[$r,$rows]=$f->fetch();bwdOutcome($r,'complete',null,2,4,51);assertSameValue(true,hrtime(true)-$start>=3_000_000_000,'native retry backoff is not skipped');assertSameValue(51,count($rows),'retried page is complete');$retry=array_values(array_filter($f->requests(),fn($q)=>$q['mode']==='retry_then_ok'));foreach([1=>1.0,2=>2.0] as $i=>$base){$delay=$retry[$i]['at']-$retry[$i-1]['at'];assertSameValue(true,$delay>=$base&&$delay<=$base+0.50,'backoff plus <=250ms jitter with 250ms native scheduling tolerance');}
    foreach(['429','502','503','504'] as $mode){$f->scenario($mode);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,3);assertSameValue(null,$rows,'retry exhaustion no data');}
    $f->scenario('second_failure');[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',1,4);assertSameValue(null,$rows,'partial first page never exposed');
},
'deadline and request timeout'=>static function(F $f):void{
    $f->scenario('429');$f->client(['deadlineSeconds'=>1]);$start=hrtime(true);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','deadline_exceeded',0,1);assertSameValue(true,hrtime(true)-$start<900_000_000,'unfittable delay rejected without sleeping');assertSameValue(null,$rows,'deadline no data');
    $f->scenario('request_timeout');$f->client(['requestTimeoutSeconds'=>1,'deadlineSeconds'=>10]);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,1);assertSameValue(null,$rows,'read timeout is not connect retry');
    $f->scenario('connect_timeout');$f->client(['deadlineSeconds'=>10]);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,3);assertSameValue(null,$rows,'connect retry exhaustion');assertSameValue(3,count(file($f->root.'/connect-timeouts.jsonl',FILE_SKIP_EMPTY_LINES)),'three actual stalled native TLS connections');
},
'unsupported runtime fails before request'=>static function(F $f):void{
    foreach(['curl_init','posix_geteuid'] as $disabled){$f->client([], $disabled);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','configuration_unavailable',0,0);assertSameValue(null,$rows,'unsupported real PHP runtime no batch');}
},
'deadline after native success'=>static function(F $f):void{
    $f->scenario('paused_success');$f->client(['deadlineSeconds'=>1]);[$r,$rows]=$f->fetch(true);bwdOutcome($r,'failed','deadline_exceeded',0,1);assertSameValue(null,$rows,'expired native attempt never returns success');
},
'deadline before ordinary error'=>static function(F $f):void{
    $f->scenario('paused_error');$f->client(['deadlineSeconds'=>1]);[$r,$rows]=$f->fetch(true);bwdOutcome($r,'failed','deadline_exceeded',0,1);assertSameValue(null,$rows,'expired native attempt takes precedence over authorization error');
},
'limits'=>static function(F $f):void{
    $f->client();foreach(['exact_body'=>[1,0],'exact_cumulative'=>[32,1600]] as $mode=>[$pages,$total]){$f->scenario($mode);[$r,$rows]=$f->fetch();bwdOutcome($r,'complete',null,$pages,$pages,$total);assertSameValue($total,count($rows),'inclusive exact body bound');}
    foreach(['oversize','person_limit','cumulative'] as $mode){$f->scenario($mode);[$r,$rows]=$f->fetch();assertSameValue(['failed','limit_exceeded',null],[$r['status'],$r['reason'],$r['batch']],'body/person limits fail closed');assertSameValue(null,$rows,'no truncated success');if($mode!=='cumulative')assertSameValue([0,1],[$r['pages'],$r['attempts']],'early bound one attempt');else assertSameValue(true,$r['pages']>1&&$r['attempts']===$r['pages']+1,'aggregate bound reached on later page');}
},
'redirect and native peer trust'=>static function(F $f):void{
    $f->scenario('redirect');$f->client();[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,1);assertSameValue([],array_values(array_filter($f->requests(),fn($q)=>$q['method']==='GET')),'redirect trap never contacted');
    $f->scenario('full');$before=count($f->requests());$f->client(['caFile'=>null]);[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,1);assertSameValue($before,count($f->requests()),'untrusted CA prevents HTTP request');
},
'secret reload and config'=>static function(F $f):void{
    $f->client();[$r]=$f->fetch();bwdOutcome($r,'complete',null,2,2,51);file_put_contents($f->root.'/token','ROTATED_FAKE_TOKEN_987654321');[$r]=$f->fetch();bwdOutcome($r,'complete',null,2,2,51);assertSameValue('/rest/7/ROTATED_FAKE_TOKEN_987654321/user.get',array_slice($f->requests(),-1)[0]['path'],'same client rereads rotated secret');
    $before=count($f->requests());chmod($f->root.'/token',0644);try{[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','configuration_unavailable',0,0);assertSameValue(null,$rows,'invalid readable secret metadata');}finally{chmod($f->root.'/token',0600);}assertSameValue($before,count($f->requests()),'invalid secret no network');
    rename($f->root.'/token',$f->root.'/token-held');try{[$r]=$f->fetch();bwdOutcome($r,'failed','configuration_unavailable',0,0);}finally{rename($f->root.'/token-held',$f->root.'/token');}
    $f->client(['tokenFile'=>$f->root.'/missing-token']);[$r]=$f->fetch();bwdOutcome($r,'failed','configuration_unavailable',0,0);
    foreach([['origin'=>'http://127.0.0.1'],['origin'=>'https://user:pass@example.invalid'],['origin'=>'https://example.invalid/path'],['webhookUserId'=>0],['departmentIds'=>[]],['departmentIds'=>[72,71]],['tokenFile'=>'relative'],['connectTimeoutSeconds'=>3,'requestTimeoutSeconds'=>1],['deadlineSeconds'=>0]] as $bad){try{W\BitrixWorkforceDeliveryFactory::create(new W\BitrixWorkforceDeliveryConfig(...$f->parameters($bad)));throw new TestFailure('invalid config accepted');}catch(W\BitrixWorkforceDeliveryConfigurationUnavailable $e){assertSameValue(['Bitrix workforce delivery configuration unavailable.',0,null],[$e->getMessage(),$e->getCode(),$e->getPrevious()],'fixed configuration exception');}}
    assertSameValue($before,count($f->requests()),'invalid factory settings perform no network');
},
];
$failures=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}if($errors){$failures++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";}
$f=null;try{$f=new F('full',true);echo "SETUP_OK wrong hostname\n";$f->client();[$r,$rows]=$f->fetch();bwdOutcome($r,'failed','transport_failed',0,1);assertSameValue([],$f->requests(),'wrong hostname prevents HTTP');echo "PASS wrong hostname\n";}catch(Throwable $e){$failures++;echo 'FAIL wrong hostname: '.$e->getMessage()."\n";}finally{if($f!==null){$f->close();echo "CLEANUP_OK wrong hostname\n";}}
exit($failures===0?0:1);
