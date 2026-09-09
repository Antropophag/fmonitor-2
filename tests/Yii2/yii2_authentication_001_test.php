<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';
require __DIR__ . '/Yii2AuthFixture.php';

use FMonitor2\Tests\Yii2\Yii2AuthFixture;

/** @return array{process:resource,pipes:array,port:int} */
function yaStart(string $root, array $specific, ?int $port = null): array
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
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-d', 'expose_php=0', '-S', "127.0.0.1:{$port}", $root . '/public/yii.php'], [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: server');
    $deadline = microtime(true) + 5;
    do { if (!proc_get_status($process)['running']) throw new TestFailure('SETUP_FAILURE: server exited'); $socket=@fsockopen('127.0.0.1',$port,$ignoredCode,$ignoredMessage,.1); if(is_resource($socket)){fclose($socket);return compact('process','pipes','port');} usleep(20000); } while(microtime(true)<$deadline);
    throw new TestFailure('SETUP_FAILURE: server listen');
}

function yaStop(?array &$server): void
{
    if ($server === null) return; proc_terminate($server['process']); $deadline=microtime(true)+2;
    while(proc_get_status($server['process'])['running']&&microtime(true)<$deadline)usleep(20000);
    if(proc_get_status($server['process'])['running'])proc_terminate($server['process'],9);
    foreach($server['pipes'] as $pipe)if(is_resource($pipe))fclose($pipe); proc_close($server['process']); $server=null;
}

/** @return array{status:int,headers:array<string,list<string>>,body:string} */
function yaRequest(array $server,string $method,string $path,array $fields=[],array &$cookies=[],array $extra=[]):array
{
    $body=$fields===[]?'':http_build_query($fields);$lines=['Host: fmonitor.example.test','Connection: close'];
    if($cookies!==[])$lines[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($cookies),$cookies));
    if($body!=='')$lines[]='Content-Type: application/x-www-form-urlencoded'; foreach($extra as$line)$lines[]=$line;
    $context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'timeout'=>5,'method'=>$method,'header'=>implode("\r\n",$lines),'content'=>$body]]);
    $bytes=file_get_contents('http://127.0.0.1:'.$server['port'].$path,false,$context);$raw=$http_response_header??[];$headers=[];
    foreach(array_slice($raw,1)as$line){$at=strpos($line,':');if($at!==false)$headers[strtolower(substr($line,0,$at))][]=trim(substr($line,$at+1));}
    foreach($headers['set-cookie']??[]as$cookie)if(preg_match('/^([^=;]+)=([^;]*)/D',$cookie,$m)===1){if($m[2]==='')unset($cookies[$m[1]]);else$cookies[$m[1]]=$m[2];}
    preg_match('#^HTTP/\S+ (\d+)#D',$raw[0]??'',$match);return['status'=>(int)($match[1]??0),'headers'=>$headers,'body'=>is_string($bytes)?$bytes:''];
}

function yaCsrf(string $html): string
{
    if(preg_match('/name="_csrf" value="([^"]+)"/',$html,$match)!==1)throw new TestFailure('SETUP_FAILURE: rendered Yii CSRF');return html_entity_decode($match[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
}

function yaAssertSafe503(array $response, string $secret, string $label): void
{
    assertSameValue(503,$response['status'],"{$label} status");
    assertSameValue(false,isset($response['headers']['location']),"{$label} no redirect");
    assertSameValue(false,isset($response['headers']['set-cookie']),"{$label} no cookie");
    assertSameValue(false,str_contains(json_encode($response,JSON_THROW_ON_ERROR),$secret),"{$label} no secret");
}

function yaLogin(array $server,Yii2AuthFixture $fixture,array &$cookies,string $password):array
{
    $get=yaRequest($server,'GET','/pilot/login',[],$cookies);assertSameValue(200,$get['status'],'login GET');$csrf=yaCsrf($get['body']);
    $email=yaRequest($server,'POST','/pilot/login',['_csrf'=>$csrf,'email'=>$fixture->email],$cookies);assertSameValue(200,$email['status'],'email step');
    return yaRequest($server,'POST','/pilot/login',['_csrf'=>yaCsrf($email['body']),'email'=>$fixture->email,'password'=>$password],$cookies);
}

$root=dirname(__DIR__,2);$fixture=null;$server=null;
try{
    $fixture=new Yii2AuthFixture($root);$environment=$fixture->environment();$server=yaStart($root,$environment);$legacy=['fm2auth_'.$server['port']=>str_repeat('a',64)];
    $spoofCookies=[];$spoof=yaRequest($server,'GET','/pilot/login',[],$spoofCookies,['Host: attacker.example.test']);assertSameValue(400,$spoof['status'],'spoofed or duplicate Host rejected before auth');assertSameValue([],array_keys($spoofCookies),'spoofed Host creates no session cookie');
    $spoofCookies=[];$spoof=yaRequest($server,'GET','/pilot/login',[],$spoofCookies,['Host: attacker.example.test']);assertSameValue(400,$spoof['status'],'noncanonical Host rejected before auth');assertSameValue([], $spoofCookies, 'Host rejection creates no session cookie');
    $legacyRoles=yaRequest($server,'GET','/pilot/admin/roles',[],$legacy);
    assertSameValue([303,'/pilot/login'],[$legacyRoles['status'],$legacyRoles['headers']['location'][0]??null],'INTENTIONAL_RED: YII2-AUTH-001 legacy cookie is guest and protected route redirects');

    $direct=[];$directLogin=yaLogin($server,$fixture,$direct,$fixture->password);assertSameValue([303,'/pilot/objects'],[$directLogin['status'],$directLogin['headers']['location'][0]??null],'direct login preserves existing default');
    $cookies=[];$guestRoles=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(303,$guestRoles['status'],'protected GET stores return target');$anonymousId=$cookies['fm2yii_test']??null;$login=yaLogin($server,$fixture,$cookies,$fixture->password);
    assertSameValue([303,'/pilot/admin/roles'],[$login['status'],$login['headers']['location'][0]??null],'successful two-step login');
    assertSameValue(true,isset($cookies['fm2yii_test']),'new isolated Yii cookie namespace');
    assertSameValue(false,$anonymousId===$cookies['fm2yii_test'],'successful login rotates session ID');
    $loginCookie=implode('; ',$login['headers']['set-cookie']??[]);assertSameValue(true,str_contains(strtolower($loginCookie),'path=/pilot'),'cookie path');assertSameValue(true,str_contains(strtolower($loginCookie),'httponly'),'cookie HttpOnly');assertSameValue(true,str_contains(strtolower($loginCookie),'samesite=strict'),'cookie SameSite Strict');assertSameValue(false,(bool)preg_match('/(?:^|;)\s*Secure(?:;|$)/i',$loginCookie),'HTTP cookie omits Secure');
    assertSameValue($fixture->passwordHash,$fixture->currentHash(),'login preserves Argon2id hash');
    assertSameValue([0,0],[$fixture->attemptCount(true),$fixture->attemptCount(false)],'successful login clears accumulated attempts');
    $roles=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(200,$roles['status'],'authorized roles read');assertSameValue(true,str_contains($roles['body'],'Администратор доступа'),'seeded role label rendered');assertSameValue(true,str_contains($roles['body'],'Администрирование'),'public permission label rendered');assertSameValue(200,yaRequest($server,'GET','/pilot/assets/shlz.css',[],$cookies)['status'],'official shlz CSS served');assertSameValue(200,yaRequest($server,'GET','/pilot/assets/pilot.css',[],$cookies)['status'],'pilot CSS served');$fixture->setRoleName('Изменённая роль проверки');$changedRoles=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(true,str_contains($changedRoles['body'],'Изменённая роль проверки'),'next GET reads current role label');

    $restartPort=$server['port'];yaStop($server);$server=yaStart($root,$environment,$restartPort);$restarted=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(200,$restarted['status'],'session survives process restart');
    $fixture->setPermission('Access.Administer');$nearMatch=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(403,$nearMatch['status'],'near-match permission denied');assertSameValue(false,str_contains($nearMatch['body'],'Изменённая роль проверки'),'denial emits no protected role label');$fixture->setPermission('access.administer');
    $fixture->setRoleStatus(0);$inactiveRole=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue(403,$inactiveRole['status'],'inactive role denied on next request');assertSameValue(false,str_contains($inactiveRole['body'],'Изменённая роль проверки'),'inactive denial emits no role label');$fixture->setRoleStatus(1);
    $fixture->setUserState(1,'active',2);$versionDenied=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue([303,'/pilot/login'],[$versionDenied['status'],$versionDenied['headers']['location'][0]??null],'session version alone invalidates same active identity');
    $cookies=[];$versionLogin=yaLogin($server,$fixture,$cookies,$fixture->password);assertSameValue(303,$versionLogin['status'],'fresh login after session version change');
    $fixture->removeCredential();$credentialDenied=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue([303,'/pilot/login'],[$credentialDenied['status'],$credentialDenied['headers']['location'][0]??null],'missing credential invalidates active identity renewal');$fixture->restoreCredential();$cookies=[];assertSameValue(303,yaLogin($server,$fixture,$cookies,$fixture->password)['status'],'fresh login after credential restore');
    $fixture->setUserState(1,'blocked');$blocked=yaRequest($server,'GET','/pilot/admin/roles',[],$cookies);assertSameValue([303,'/pilot/login'],[$blocked['status'],$blocked['headers']['location'][0]??null],'blocked identity removed on next request');
    $fixture->setUserState(1,'active',2);$cookies=[];$invalid=yaRequest($server,'POST','/pilot/login',['email'=>$fixture->email],$cookies);assertSameValue(400,$invalid['status'],'login CSRF denied');assertSameValue([0,0],[$fixture->attemptCount(true),$fixture->attemptCount(false)],'CSRF creates no attempt');

    foreach([[1,'invited','invited'],[1,'blocked','blocked'],[0,'active','disabled']]as[$status,$state,$label]){$fixture->setUserState($status,$state,2);$stateCookies=[];$form=yaRequest($server,'GET','/pilot/login',[],$stateCookies);$denied=yaRequest($server,'POST','/pilot/login',['_csrf'=>yaCsrf($form['body']),'email'=>$fixture->email],$stateCookies);assertSameValue(200,$denied['status'],"{$label} email is neutrally denied");assertSameValue(false,str_contains($denied['body'],'name="password"'),"{$label} identity never reaches password admission");}
    $fixture->setUserState(1,'active',2);$fixture->clearAttempts();

    $wrong=[];$failed=yaLogin($server,$fixture,$wrong,'Wrong fixture password 2026');assertSameValue(200,$failed['status'],'wrong password stays on form');assertSameValue(1,$fixture->attemptCount(false),'failed attempt persisted');
    $fixture->clearAttempts();$tooLong=[];$longFailure=yaLogin($server,$fixture,$tooLong,str_repeat('x',201));assertSameValue(200,$longFailure['status'],'password over 200 bytes denied');assertSameValue(1,$fixture->attemptCount(false),'oversized password recorded without verification');
    $fixture->clearAttempts();$limited=[];$limitedGet=yaRequest($server,'GET','/pilot/login',[],$limited);$limitedEmail=yaRequest($server,'POST','/pilot/login',['_csrf'=>yaCsrf($limitedGet['body']),'email'=>$fixture->email],$limited);$limitedCsrf=yaCsrf($limitedEmail['body']);$fixture->seedFailedAttempts(10);$limitedResult=yaRequest($server,'POST','/pilot/login',['_csrf'=>$limitedCsrf,'email'=>$fixture->email,'password'=>$fixture->password],$limited);assertSameValue(200,$limitedResult['status'],'rate limit denies correct password');assertSameValue(11,$fixture->attemptCount(false),'single rate-limited password POST persisted once');
    $fixture->clearAttempts();$active=[];$ok=yaLogin($server,$fixture,$active,$fixture->password);assertSameValue(303,$ok['status'],'fresh login after version change');
    $page=yaRequest($server,'GET','/pilot/admin/roles',[],$active);$csrf=yaCsrf($page['body']);
    $getLogout=yaRequest($server,'GET','/pilot/logout',[],$active);assertSameValue(405,$getLogout['status'],'GET logout denied');assertSameValue(200,yaRequest($server,'GET','/pilot/admin/roles',[],$active)['status'],'GET logout preserves session');
    $badLogout=yaRequest($server,'POST','/pilot/logout',['_csrf'=>'invalid'],$active);assertSameValue(400,$badLogout['status'],'bad logout CSRF denied');
    $logout=yaRequest($server,'POST','/pilot/logout',['_csrf'=>$csrf],$active);assertSameValue([303,'/pilot/login'],[$logout['status'],$logout['headers']['location'][0]??null],'logout success');
    $after=yaRequest($server,'GET','/pilot/admin/roles',[],$active);assertSameValue(303,$after['status'],'logout invalidates protected session');

    yaStop($server);$missingEnvironment=array_replace($environment,['FMONITOR_YII_SESSION_PATH'=>dirname($fixture->sessionPath).'/missing-session-path']);$server=yaStart($root,$missingEnvironment,$restartPort);$faultCookies=[];yaAssertSafe503(yaRequest($server,'GET','/pilot/login',[],$faultCookies),$environment['FMONITOR_YII_COOKIE_VALIDATION_KEY'],'missing session path');yaStop($server);
    $databaseEnvironment=array_replace($environment,['FMONITOR_DB_PORT'=>'1']);$server=yaStart($root,$databaseEnvironment,$restartPort);$faultCookies=[];$databaseForm=yaRequest($server,'GET','/pilot/login',[],$faultCookies);assertSameValue(200,$databaseForm['status'],'anonymous login form has no eager DB dependency');$databasePost=yaRequest($server,'POST','/pilot/login',['_csrf'=>yaCsrf($databaseForm['body']),'email'=>$fixture->email],$faultCookies);yaAssertSafe503($databasePost,$environment['FMONITOR_DB_PASSWORD'],'database unavailable');
    echo "PASS: YII2-AUTH-001 public authentication, session and roles\n";
}finally{yaStop($server);if($fixture instanceof Yii2AuthFixture)$fixture->close();}
