<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

// SAFE-RUNTIME-ERROR-CORRELATION-001. Public evidence is wire HTTP plus bytes actually appended to stderr.
const SRC_ID_HEADER='x-fmonitor-error-id';
const SRC_CANARY='CANARY_DSN_PASSWORD_COOKIE_QUERY_BODY_DOCUMENT_173';

function srcRequest(int $port,string $path,array $headers=[]):array
{
    $lines=['Host: 127.0.0.1:'.$port,'Connection: close'];
    foreach($headers as$name=>$value)$lines[]=$name.': '.$value;
    $context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'timeout'=>15,'header'=>implode("\r\n",$lines)]]);
    $body=file_get_contents('http://127.0.0.1:'.$port.$path,false,$context);$raw=$http_response_header??[];
    preg_match('#^HTTP/\S+ (\d+)#D',$raw[0]??'',$match);$out=[];
    foreach(array_slice($raw,1)as$line){$at=strpos($line,':');if($at!==false)$out[strtolower(substr($line,0,$at))][]=trim(substr($line,$at+1));}
    return ['status'=>(int)($match[1]??0),'headers'=>$out,'body'=>(string)$body];
}

function srcStart(string $root,array $env,string $stderr,bool $brokenLog=false):array
{
    $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$message);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE port');
    preg_match('/:(\d+)$/D',(string)stream_socket_get_name($socket,false),$match);$port=(int)$match[1];fclose($socket);
    $env['FMONITOR_TRUSTED_REQUEST_HOST']='127.0.0.1:'.$port;
    $command=[PHP_BINARY,'-d','display_errors=0','-d','log_errors=1'];
    if($brokenLog)$command=[...$command,'-d','error_log=/dev/full'];
    $command=[...$command,'-S','127.0.0.1:'.$port,$root.'/public/runtime.php'];
    $process=proc_open($command,[0=>['file','/dev/null','r'],1=>['file','/dev/null','a'],2=>['file',$stderr,'a']],$pipes,$root,$env);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE server');
    $deadline=microtime(true)+5;do{$probe=@fsockopen('127.0.0.1',$port,$e,$m,.1);if(is_resource($probe)){fclose($probe);return[$process,$port];}usleep(20000);}while(microtime(true)<$deadline);
    throw new TestFailure('SETUP_FAILURE listen');
}

function srcStop(mixed &$process):void{if(is_resource($process)){proc_terminate($process);proc_close($process);$process=null;}}
function srcId(array $response,string $label):string
{
    $values=$response['headers'][SRC_ID_HEADER]??[];assertSameValue(1,count($values),$label.' one error ID header');$id=$values[0]??'';
    assertSameValue(1,preg_match('/^[A-Za-z0-9_-]{16,80}$/D',$id),$label.' opaque ASCII error ID');return$id;
}
function srcRecord(string $bytes,string $id,string $label):array
{
    $matches=[];foreach(preg_split('/\R/',$bytes)as$line)if(str_contains($line,'"errorId":"'.$id.'"'))$matches[]=$line;
    assertSameValue(1,count($matches),$label.' exactly one physical record by ID');
    preg_match('/\{[^\r\n]*"errorId":"'.preg_quote($id,'/').'"[^\r\n]*\}/',$matches[0],$json);$record=json_decode($json[0]??'',true);
    assertSameValue(true,is_array($record),$label.' compact JSON record');
    assertSameValue(['event','errorId','occurredAtUtc','component','status','category','buildVersion'],array_keys($record),$label.' exact allowlist/order');
    assertSameValue(['http_failure',$id,503],[$record['event'],$record['errorId'],$record['status']],$label.' identity/status');
    assertSameValue(1,preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/D',$record['occurredAtUtc']),$label.' UTC time');
    assertSameValue(true,in_array($record['category'],['configuration','database','dependency','storage','unexpected'],true),$label.' closed category');
    return$record;
}
function srcSafe(array$response,string$label):void
{
    assertSameValue(503,$response['status'],$label.' status');
    assertSameValue("{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}\n",$response['body'],$label.' envelope');
    foreach(['cache-control'=>'no-store','retry-after'=>'60','x-content-type-options'=>'nosniff','referrer-policy'=>'no-referrer','x-frame-options'=>'DENY']as$name=>$value)assertSameValue($value,$response['headers'][$name][0]??null,$label.' '.$name);
    $wire=json_encode($response,JSON_THROW_ON_ERROR);foreach([SRC_CANARY,'RuntimeException','Stack trace','password','dsn=']as$secret)assertSameValue(false,str_contains(strtolower($wire),strtolower($secret)),$label.' no '.$secret);
}
function srcSecurity(array$response,string$label):void
{
    foreach(['retry-after'=>'60','cache-control'=>'no-store','x-content-type-options'=>'nosniff','referrer-policy'=>'no-referrer','x-frame-options'=>'DENY']as$name=>$value)assertSameValue($value,$response['headers'][$name][0]??null,$label.' '.$name);
}
function srcNoFailureRecord(string$before,string$file,array$response,int$status,string$body,string$label,array$headers=[]):void
{
    assertSameValue([$status,$body,false],[$response['status'],$response['body'],isset($response['headers'][SRC_ID_HEADER])],$label.' exact response/no ID');
    foreach($headers as$name=>$value)assertSameValue($value,$response['headers'][$name][0]??null,$label.' '.$name);
    usleep(30000);assertSameValue(srcFailureCount($before),srcFailureCount((string)file_get_contents($file)),$label.' no diagnostic record');
}
function srcFailureCount(string$bytes):int{return preg_match_all('/"event":"http_failure"/',$bytes);}

$root=dirname(__DIR__,2);$fixture=new PreopeningFixture($root);$process=null;$files=[];
try{
    $privateRoot=(realpath(sys_get_temp_dir())?:throw new TestFailure('SETUP_FAILURE temp')).'/src173-runtime-'.bin2hex(random_bytes(6));
    $base=array_replace(getenv(),$fixture->environment(),[
        'FMONITOR_SESSION_STATE_ROOT'=>$privateRoot.'/state',
        'FMONITOR_SESSION_INSTANCE'=>'safe_error_173',
        'FMONITOR_ARTIFACT_STORAGE_ROOT'=>$privateRoot.'/artifacts',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE'=>$privateRoot.'/secret/database-password',
        'FMONITOR_ORIGINAL_SAFE_LOG_FILE'=>$privateRoot.'/log/original-safe.jsonl',
        'FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:1',
        'HTTP_COOKIE'=>SRC_CANARY,
    ]);
    FMonitor2\Runtime\RuntimeStorage::prepare(FMonitor2\Runtime\RuntimeConfiguration::fromEnvironment($base));
    $baselineLog=tempnam(sys_get_temp_dir(),'src173-baseline-');$files[]=$baselineLog;[$process,$port]=srcStart($root,$base,$baselineLog);
    $baseline=srcRequest($port,'/pilot/login');assertSameValue([200,false],[ $baseline['status'],isset($baseline['headers'][SRC_ID_HEADER])],'prepared production runtime baseline reaches Yii');srcStop($process);
    assertSameValue(0,srcFailureCount((string)file_get_contents($baselineLog)),'prepared baseline has no failure record');
    // Bootstrap configuration and database failures run before a usable Yii/DB application exists.
    foreach([
        [['FMONITOR_YII_COOKIE_VALIDATION_KEY'=>''],'configuration'],
        [['FMONITOR_DB_PORT'=>'1','FMONITOR_DB_PASSWORD'=>SRC_CANARY],'database'],
    ]as$index=>[$override,$category]){
        $log=tempnam(sys_get_temp_dir(),'src173-');$files[]=$log;[$process,$port]=srcStart($root,array_replace($base,$override),$log);
        $response=srcRequest($port,'/pilot/login?secret='.rawurlencode(SRC_CANARY),['X-FMonitor-Error-ID'=>"client\x01".SRC_CANARY,'Cookie'=>SRC_CANARY]);srcSafe($response,'bootstrap '.$category);$id=srcId($response,'bootstrap '.$category);srcStop($process);$record=srcRecord((string)file_get_contents($log),$id,'bootstrap '.$category);
        assertSameValue(['bootstrap',$category,'unknown'],[$record['component'],$record['category'],$record['buildVersion']],'bootstrap '.$category.' classification/version');
        assertSameValue(false,str_contains((string)file_get_contents($log),SRC_CANARY),'bootstrap '.$category.' log privacy');
    }

    // A failed sink cannot replace the original safe response or trust the client ID.
    $log=tempnam(sys_get_temp_dir(),'src173-broken-');$files[]=$log;[$process,$port]=srcStart($root,array_replace($base,['FMONITOR_YII_COOKIE_VALIDATION_KEY'=>'']),$log,true);
    $broken=srcRequest($port,'/pilot/login',['X-FMonitor-Error-ID'=>'client-controlled']);srcSafe($broken,'broken logger');$brokenId=srcId($broken,'broken logger');assertSameValue(false,$brokenId==='client-controlled','logger failure keeps server-owned ID');srcStop($process);

    // Real Yii error handler: response sanitization must retain the generated ID and one record.
    assertSameValue('selected',$fixture->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'selected fixture');$accepted=$fixture->nativeOriginal();
    $yiiLog=$fixture->artifacts.'/server.log';$fixture->start();$cookies=[];assertSameValue(303,$fixture->login($cookies)['status'],'fixture login');
    $fixture->db->query("RENAME TABLE {$fixture->p}fm2_pilot_object_details TO {$fixture->p}fm2_pilot_object_details_src173");
    try{$global=$fixture->request('GET','/pilot/objects',[],$cookies,['X-FMonitor-Error-ID: client-global']);}
    finally{$fixture->db->query("RENAME TABLE {$fixture->p}fm2_pilot_object_details_src173 TO {$fixture->p}fm2_pilot_object_details");}
    assertSameValue("{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}",$global['body'],'Yii handler exact body');srcSecurity($global,'Yii handler');$globalId=srcId($global,'Yii handler');
    assertSameValue(false,isset($global['headers']['location'])||isset($global['headers']['set-cookie']),'Yii handler strips unsafe headers');
    usleep(100000);$globalRecord=srcRecord((string)file_get_contents($yiiLog),$globalId,'Yii handler');assertSameValue('yii_error_handler',$globalRecord['component'],'Yii handler component');

    // Success and expected 4xx remain outside the failure channel.
    $before=(string)file_get_contents($yiiLog);$ok=$fixture->request('GET','/pilot/objects',[],$cookies);assertSameValue([200,'text/html; charset=UTF-8',false,true],[$ok['status'],$ok['headers']['content-type'][0]??null,isset($ok['headers'][SRC_ID_HEADER]),str_contains($ok['body'],'Объекты монтажа')],'success material behavior');assertSameValue(srcFailureCount($before),srcFailureCount((string)file_get_contents($yiiLog)),'success no diagnostic record');
    $notFound=$fixture->request('GET','/pilot/does-not-exist',[],$cookies);srcNoFailureRecord($before,$yiiLog,$notFound,404,'{"ok":false,"reason":"NOT_FOUND"}','404');
    $method=$fixture->request('GET','/pilot/logout',[],$cookies);srcNoFailureRecord($before,$yiiLog,$method,405,'{"ok":false,"reason":"METHOD_NOT_ALLOWED"}','405',['allow'=>'POST']);
    $bad=$fixture->request('POST','/pilot/objects/4512/checklist/operations',[],$cookies,['Content-Type: application/json; charset=UTF-8','X-FM2-CSRF: bad'],SRC_CANARY);srcNoFailureRecord($before,$yiiLog,$bad,400,"{\"status\":\"rejected\"}",'400 CSRF');
    $reader=[];assertSameValue(303,$fixture->login($reader,95)['status'],'reader login');$readerToken=$fixture->token($reader);$deniedBody=json_encode(['type'=>'item_completed','itemId'=>42,'clientOperationId'=>SRC_CANARY],JSON_THROW_ON_ERROR);$denied=$fixture->request('POST','/pilot/objects/4512/checklist/operations',[],$reader,['Content-Type: application/json; charset=UTF-8','X-FM2-CSRF: '.$readerToken],$deniedBody);srcNoFailureRecord($before,$yiiLog,$denied,403,"{\"status\":\"rejected\"}",'403 RBAC');
    $fixture->insert($fixture->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'inspection.item.complete']);$token=$fixture->token($cookies);$conflictBody=json_encode(['type'=>'item_completed','itemId'=>42,'clientOperationId'=>'173-conflict'],JSON_THROW_ON_ERROR);$conflict=$fixture->request('POST','/pilot/objects/4512/checklist/operations',[],$cookies,['Content-Type: application/json; charset=UTF-8','X-FM2-CSRF: '.$token],$conflictBody);srcNoFailureRecord($before,$yiiLog,$conflict,409,"{\"status\":\"rejected\",\"message\":\"Последние 15% закрываются актом ПТО и декларацией в карточке объекта.\"}",'409 domain');
    $originalCsrf=$fixture->token($cookies);$unsupported=$fixture->request('POST','/pilot/objects/4512/assignment-orders/81/originals',[],$cookies,['Content-Type: text/plain','X-CSRF-Token: '.$originalCsrf],SRC_CANARY);srcNoFailureRecord($before,$yiiLog,$unsupported,415,"{\"error\":\"UNSUPPORTED_MEDIA_TYPE\"}\n",'415 media');
    $tooLarge=$fixture->request('POST','/pilot/objects/4512/assignment-orders/81/originals',[],$cookies,['Content-Type: application/pdf','X-CSRF-Token: '.$originalCsrf],str_repeat('A',20971521));srcNoFailureRecord($before,$yiiLog,$tooLarge,413,"{\"error\":\"REQUEST_TOO_LARGE\"}\n",'413 admission',['content-type'=>'application/json; charset=UTF-8']);

    // Controller boundaries are exercised through actual Yii routes. A test router throws closed known/generic types after authentication.
    $fixture->start(['FMONITOR_LEGACY_TABLE_PREFIX'=>'wrong_'],null,true);$execution=$fixture->request('GET','/pilot/objects/4512/execution?query='.SRC_CANARY,[],$cookies);srcControllerExpectation($execution,$fixture->artifacts.'/server.log','execution_controller','unexpected','execution','html');
    $fixture->start([],null,true);$metadata=$fixture->metadata($cookies);$metadata['originalFilename']='secret-'.SRC_CANARY.'.pdf';$missingStorage=$fixture->artifacts.'/missing-storage';$fixture->start(['FMONITOR_ARTIFACT_STORAGE_ROOT'=>$missingStorage],null,true);$privateUpload=$fixture->upload($cookies,$metadata,"%PDF-1.4\n".SRC_CANARY);srcControllerExpectation($privateUpload,$fixture->artifacts.'/server.log','original_controller','unexpected','original privacy','json');
    $fixture->start([],null,true);$engineer=[];assertSameValue(303,$fixture->login($engineer,73)['status'],'engineer login');$engineerCsrf=$fixture->token($engineer);
    $fixture->db->query("UPDATE {$fixture->p}fm2_installation_cases SET process_state='working',actual_start_date='2026-09-02',opened_at='2026-09-02 06:00:00',opened_by_user_id=18 WHERE legacy_installation_object_id=4512");
    $fixture->db->query("INSERT INTO {$fixture->p}fm2_checklist_template_associations(association_version,subject_kind,subject_id,effective_at,template_snapshot_id,template_snapshot_version,template_content_sha256,created_at) SELECT 'src173-v1','operational_case','6101','2026-09-02 00:00:00',id,snapshot_version,content_sha256,'2026-09-02 00:00:00' FROM {$fixture->p}fm2_checklist_template_snapshots ORDER BY id DESC LIMIT 1");
    $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',true);$photo=['clientOperationId'=>'44444444-4444-4444-8444-000000000173','deviceInstallationId'=>'55555555-5555-4555-8555-000000000173','type'=>'photo_uploaded','deviceTime'=>'2026-09-02T09:00:00+03:00','baseRevision'=>0,'sectionId'=>1,'sha256'=>hash('sha256',$png),'mime'=>'image/png','size'=>strlen($png),'originalName'=>'secret-'.SRC_CANARY.'.png'];$storageBlocker=$fixture->artifacts.'/storage-file';file_put_contents($storageBlocker,'blocked');$fixture->start(['FMONITOR_ARTIFACT_STORAGE_ROOT'=>$storageBlocker],null,true);$checklist=$fixture->request('POST','/pilot/objects/4512/checklist/photos',[],$engineer,['Content-Type: image/png','X-FM2-CSRF: '.$engineerCsrf,'X-FM2-Operation: '.base64_encode(json_encode($photo,JSON_THROW_ON_ERROR))],$png);srcControllerExpectation($checklist,$fixture->artifacts.'/server.log','checklist_controller','storage','checklist storage','checklist-json');
    $fixture->db->query("UPDATE {$fixture->p}fm2_installation_cases SET process_state='assignment_order_prepared',actual_start_date=NULL,opened_at=NULL,opened_by_user_id=NULL WHERE legacy_installation_object_id=4512");
    $fixture->start([],null,true);$facts=$fixture->facts();$open=['_csrf'=>$fixture->token($cookies),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000173','orderId'=>'81','revisionId'=>$accepted->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];$fixture->db->query('CREATE TABLE src173_attempts(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=MyISAM');$fixture->db->query("CREATE TRIGGER src173_open_attempt BEFORE INSERT ON {$fixture->p}fm2_assignment_order_applications FOR EACH ROW BEGIN INSERT INTO src173_attempts VALUES(NULL); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='".SRC_CANARY."'; END");$fixture->start([],srcBrokenSinkRouter($root),true);
    try{$failedCommand=$fixture->form('/pilot/objects/4512/execution',$open,$cookies);srcControllerExpectation($failedCommand,$fixture->artifacts.'/server.log','execution_controller','database','broken sink command','html-open',false);assertSameValue(1,(int)$fixture->db->query('SELECT COUNT(*) FROM src173_attempts')->fetch_column(),'failed command boundary invoked once');}
    finally{$fixture->db->query('DROP TRIGGER IF EXISTS src173_open_attempt');$fixture->db->query('DROP TABLE IF EXISTS src173_attempts');}
    assertSameValue($facts,$fixture->facts(),'failed logger/command preserves all domain facts and history');
    $fixture->db->query("CREATE TRIGGER src173_sql_privacy BEFORE INSERT ON {$fixture->p}fm2_assignment_order_applications FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='".SRC_CANARY."'");$visibleOpen=$open;$visibleOpen['requestId']='33333333-3333-4333-8333-000000000174';$fixture->start([],null,true);
    try{$sql=$fixture->form('/pilot/objects/4512/execution',$visibleOpen,$cookies);srcControllerExpectation($sql,$fixture->artifacts.'/server.log','execution_controller','dependency','SQL failure privacy','html-open');}
    finally{$fixture->db->query('DROP TRIGGER IF EXISTS src173_sql_privacy');}
    echo"PASS: SAFE-RUNTIME-ERROR-CORRELATION-001 real HTTP and factual logs\n";
}finally{srcStop($process);$fixture->close();foreach($files as$file)if(is_file($file))unlink($file);}

function srcControllerRouter(string$root,string$canary,string$kind,?string$marker=null,bool$broken=false):string
{
    $exception=$kind==='storage'?'throw new FMonitor2\\InstallationProcess\\ArtifactStorageException('.var_export($canary,true).');':'throw new RuntimeException($sql." params=".json_encode($this->params)." '.addslashes($canary).'");';$mark=$marker===null?'':'file_put_contents('.var_export($marker,true).',"attempt\\n",FILE_APPEND|LOCK_EX);';$break=$broken?'ini_set("error_log","/dev/full");':'';$condition=$kind==='sql'?'str_contains($sql,"fm2_pilot_auth_credentials")':'!str_contains($sql,"fm2_pilot_users")&&!str_contains($sql,"fm2_pilot_auth_credentials")&&!str_contains($sql,"fm2_pilot_user_roles")&&!str_contains($sql,"fm2_pilot_roles")';
    return'<?php '.$break.'require '.var_export($root.'/vendor/autoload.php',true).';require '.var_export($root.'/vendor/yiisoft/yii2/Yii.php',true).';class Src173Command extends yii\\db\\Command{protected function queryInternal($method,$fetchMode=null){$sql=$this->getRawSql();if('.$condition.'){'.$mark.$exception.'}return parent::queryInternal($method,$fetchMode);}}$config=require '.var_export($root.'/config/yii/web.php',true).';$factory=$config["components"]["db"];$config["components"]["db"]=static function()use($factory){$db=$factory();$db->commandClass=Src173Command::class;return$db;};(new yii\\web\\Application($config))->run();';
}
function srcBrokenSinkRouter(string$root):string{return'<?php ini_set("error_log","/dev/full");require '.var_export($root.'/public/yii.php',true).';';}
function srcControllerExpectation(array$response,string$log,string$component,string$category,string$label,string$envelope,bool$expectRecord=true):void
{
    assertSameValue(503,$response['status'],$label.' status');srcSecurity($response,$label);if(in_array($envelope,['html','html-open'],true)){$message=$envelope==='html'?'Результат операции неизвестен. Проверьте карточку объекта перед повтором.':'Результат открытия неизвестен. Проверьте карточку перед повтором.';$semantic=[preg_match('/<title>Сервис временно недоступен · FMonitor 2\.0<\/title>/u',$response['body']),substr_count($response['body'],$message),preg_match('#href="/pilot/objects/4512"[^>]*>Вернуться к карточке<#u',$response['body']),substr_count($response['body'],'Подождите немного и повторите переход. Внесённые ранее факты не изменены.')];assertSameValue([1,1,1,1],$semantic,$label.' complete error-page semantics');}elseif($envelope==='json')assertSameValue("{\"error\":\"SERVICE_UNAVAILABLE\"}\n",$response['body'],$label.' JSON body');elseif($envelope==='checklist-json')assertSameValue("{\"status\":\"retryable\",\"message\":\"Сервис временно недоступен.\"}",$response['body'],$label.' checklist JSON body');else assertSameValue("Service unavailable.\n",$response['body'],$label.' plain body');$id=srcId($response,$label);usleep(100000);$bytes=(string)file_get_contents($log);if(!$expectRecord){assertSameValue(0,substr_count($bytes,'"errorId":"'.$id.'"'),$label.' failed sink writes no record');return;}$record=srcRecord($bytes,$id,$label);
    assertSameValue([$component,$category],[$record['component'],$record['category']],$label.' category');assertSameValue(false,str_contains(json_encode($response,JSON_THROW_ON_ERROR).json_encode($record,JSON_THROW_ON_ERROR),SRC_CANARY),$label.' wire/record privacy');
}
