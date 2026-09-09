<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';
require __DIR__ . '/Yii2AuthFixture.php';

use FMonitor2\Tests\Yii2\Yii2AuthFixture;

/** @return array{process:resource,pipes:array,port:int} */
function ysfStart(string $root, array $specific, ?int $port = null, ?string $fault = null): array
{
    if ($port === null) {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
        if (!is_resource($listener)) throw new TestFailure("SETUP_FAILURE: port {$code} {$message}");
        $address = (string) stream_socket_get_name($listener, false); fclose($listener);
        preg_match('/:(\d+)$/D', $address, $match); $port = (int) ($match[1] ?? 0);
    }
    $environment = getenv(); if (!is_array($environment)) throw new TestFailure('SETUP_FAILURE: env');
    foreach (array_keys($environment) as $key) if (str_starts_with((string) $key, 'FMONITOR_')) unset($environment[$key]);
    $environment = array_replace($environment, $specific);
    $command = [PHP_BINARY, '-d', 'display_errors=0', '-d', 'expose_php=0'];
    if ($fault !== null) {
        $environment['FMONITOR_TEST_SESSION_FAULT'] = $fault;
        $command[] = '-d'; $command[] = 'auto_prepend_file=' . $root . '/tests/Support/Yii2SessionFaultPrepend.php';
    }
    array_push($command, '-S', "127.0.0.1:{$port}", $root . '/public/yii.php');
    $pipes=[];$process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: server');$deadline=microtime(true)+5;
    do{if(!proc_get_status($process)['running'])throw new TestFailure('SETUP_FAILURE: server exited');$socket=@fsockopen('127.0.0.1',$port,$ignoredCode,$ignoredMessage,.1);if(is_resource($socket)){fclose($socket);return compact('process','pipes','port');}usleep(20000);}while(microtime(true)<$deadline);
    throw new TestFailure('SETUP_FAILURE: server listen');
}

function ysfStop(?array &$server): void
{
    if($server===null)return;proc_terminate($server['process']);$deadline=microtime(true)+2;
    while(proc_get_status($server['process'])['running']&&microtime(true)<$deadline)usleep(20000);
    if(proc_get_status($server['process'])['running'])proc_terminate($server['process'],9);
    foreach($server['pipes']as$pipe)if(is_resource($pipe))fclose($pipe);proc_close($server['process']);$server=null;
}

/** @return array{status:int,headers:array<string,list<string>>,body:string} */
function ysfRequest(array $server,string $method,string $path,array $fields,array &$cookies):array
{
    $body=$fields===[]?'':http_build_query($fields);$lines=['Host: fmonitor.example.test','Connection: close'];
    if($cookies!==[])$lines[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($cookies),$cookies));
    if($body!=='')$lines[]='Content-Type: application/x-www-form-urlencoded';
    $context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'timeout'=>5,'method'=>$method,'header'=>implode("\r\n",$lines),'content'=>$body]]);
    $bytes=file_get_contents('http://127.0.0.1:'.$server['port'].$path,false,$context);$raw=$http_response_header??[];$headers=[];
    foreach(array_slice($raw,1)as$line){$at=strpos($line,':');if($at!==false)$headers[strtolower(substr($line,0,$at))][]=trim(substr($line,$at+1));}
    foreach($headers['set-cookie']??[]as$cookie)if(preg_match('/^([^=;]+)=([^;]*)/D',$cookie,$m)===1){if($m[2]==='')unset($cookies[$m[1]]);else$cookies[$m[1]]=$m[2];}
    preg_match('#^HTTP/\S+ (\d+)#D',$raw[0]??'',$match);return['status'=>(int)($match[1]??0),'headers'=>$headers,'body'=>is_string($bytes)?$bytes:''];
}

function ysfCsrf(string $html):string
{
    if(preg_match('/name="_csrf" value="([^"]+)"/',$html,$match)!==1)throw new TestFailure('SETUP_FAILURE: CSRF');
    return html_entity_decode($match[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
}

function ysfSafeFailure(array $response,Yii2AuthFixture $fixture,array $secrets,string $label):void
{
    assertSameValue(503,$response['status'],"INTENDED_RED: {$label} safe status");
    assertSameValue(false,isset($response['headers']['location']),"{$label} no success redirect");
    foreach($response['headers']['set-cookie']??[]as$cookie)if(preg_match('/^fm2yii(?:_[0-9]+)?=([^;]*)/D',$cookie,$match)===1){
        preg_match('/(?:^|;)\s*expires=([^;]+)/i',$cookie,$expires);$past=isset($expires[1])&&strtotime($expires[1])!==false&&strtotime($expires[1])<time();
        $expired=strtolower($match[1])==='deleted'&&(str_contains(strtolower($cookie),'max-age=0')||$past);
        assertSameValue(false,$match[1]!==''&&!$expired,"{$label} no live authenticated cookie");
    }
    assertSameValue(['no-store'],$response['headers']['cache-control']??[],"{$label} non-cacheable");
    assertSameValue(['ok'=>false,'reason'=>'SERVICE_UNAVAILABLE'],json_decode(trim($response['body']),true,8,JSON_THROW_ON_ERROR),"{$label} generic body");
    $encoded=json_encode($response,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    foreach([$fixture->email,$fixture->password,$fixture->passwordHash,$fixture->sessionPath,...$secrets]as$secret)
        assertSameValue(false,str_contains($encoded,$secret),"{$label} hides private material");
}

function ysfAssertMarker(string $marker,string $operation):void
{
    $lines=is_file($marker)?file($marker,FILE_IGNORE_NEW_LINES):false;
    assertSameValue(true,is_array($lines)&&count($lines)>=1,"{$operation} boundary reached");
    foreach($lines?:[]as$line)assertSameValue($operation,$line,"{$operation} marker contains operation only");
}

$root=dirname(__DIR__,2);$fixture=null;$server=null;
try{
    $fixture=new Yii2AuthFixture($root);$environment=$fixture->environment();
    $marker=dirname($fixture->sessionPath).'/session-fault.log';$environment['FMONITOR_TEST_SESSION_FAULT_MARKER']=$marker;

    $server=ysfStart($root,$environment);$passwordCookies=[];
    $emailForm=ysfRequest($server,'GET','/pilot/login',[],$passwordCookies);
    assertSameValue(200,$emailForm['status'],'normal email form before write fault');
    $passwordForm=ysfRequest($server,'POST','/pilot/login',['_csrf'=>ysfCsrf($emailForm['body']),'email'=>$fixture->email],$passwordCookies);
    assertSameValue(200,$passwordForm['status'],'normal password form before write fault');
    $passwordCsrf=ysfCsrf($passwordForm['body']);$port=$server['port'];ysfStop($server);

    $server=ysfStart($root,$environment,$port,'write');
    $writeFailure=ysfRequest($server,'POST','/pilot/login',['_csrf'=>$passwordCsrf,'email'=>$fixture->email,'password'=>$fixture->password],$passwordCookies);
    ysfSafeFailure($writeFailure,$fixture,[...array_values($passwordCookies),$environment['FMONITOR_YII_COOKIE_VALIDATION_KEY'],$environment['FMONITOR_DB_PASSWORD']],'late session write failure');
    ysfAssertMarker($marker,'write');
    ysfStop($server);@unlink($marker);

    $server=ysfStart($root,$environment,$port);$logoutCookies=[];
    $loginForm=ysfRequest($server,'GET','/pilot/login',[],$logoutCookies);
    $passwordForm=ysfRequest($server,'POST','/pilot/login',['_csrf'=>ysfCsrf($loginForm['body']),'email'=>$fixture->email],$logoutCookies);
    $login=ysfRequest($server,'POST','/pilot/login',['_csrf'=>ysfCsrf($passwordForm['body']),'email'=>$fixture->email,'password'=>$fixture->password],$logoutCookies);
    assertSameValue(303,$login['status'],'normal authenticated session before destroy fault');
    $roles=ysfRequest($server,'GET','/pilot/admin/roles',[],$logoutCookies);assertSameValue(200,$roles['status'],'normal protected form before destroy fault');
    $logoutCsrf=ysfCsrf($roles['body']);ysfStop($server);

    $server=ysfStart($root,$environment,$port,'destroy');
    $destroyFailure=ysfRequest($server,'POST','/pilot/logout',['_csrf'=>$logoutCsrf],$logoutCookies);
    ysfSafeFailure($destroyFailure,$fixture,[...array_values($logoutCookies),$environment['FMONITOR_YII_COOKIE_VALIDATION_KEY'],$environment['FMONITOR_DB_PASSWORD']],'late session destroy failure');
    ysfAssertMarker($marker,'destroy');
    echo "PASS: YII2-AUTH-001 late session persistence failures\n";
}finally{ysfStop($server);if($fixture instanceof Yii2AuthFixture)$fixture->close();}
