<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/LocalRbacFixture.php';
require dirname(__DIR__) . '/Support/PilotObjectReadRbacFixture.php';

// Specification: PILOT-OBJECT-LIST-001 v0.1.
// Specification: PILOT-OBJECT-READ-RBAC-FIXTURES-001 v1.
// Confirmed public seam: raw HTTP GET|HEAD /pilot/objects.

use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\Tests\Support\HttpReadOnlyFilesystemGuard;
use FMonitor2\Tests\Support\TaskOwnedArtifactRoot;

function polDb(?string $database = null): mysqli
{
    $db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $db->set_charset('utf8mb4');
    return $db;
}

function polDbAs(string $user,string $password,string $database): mysqli
{
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',$user,$password,$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');return$db;
}

function polDeniedSelect(mysqli $db,string $sql,string $why): void
{
    try{$result=$db->query($sql);if($result===false)return;}catch(mysqli_sql_exception){return;}
    throw new TestFailure($why.' must be denied');
}

function polPort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) throw new TestFailure('allocate loopback port');
    $name = (string) stream_socket_get_name($socket, false); fclose($socket);
    return (int) substr($name, strrpos($name, ':') + 1);
}

function polStart(array $environment): array
{
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $port = polPort(); $command = ['/usr/bin/env', '-i'];
        foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
        $command = [...$command, PHP_BINARY, '-d', 'expose_php=0', '-S', '127.0.0.1:' . $port, dirname(__DIR__, 2) . '/public/router.php'];
        $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
        if (!is_resource($process)) throw new TestFailure('start real PHP server');
        fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $probe = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, .1);
            if ($probe !== false) { fclose($probe); return compact('process', 'pipes', 'port'); }
            if (!(proc_get_status($process)['running'] ?? false)) break;
            usleep(50000);
        }
        foreach ([1,2] as $fd) if (is_resource($pipes[$fd])) fclose($pipes[$fd]);
        proc_close($process);
    }
    throw new TestFailure('PHP server did not listen');
}

function polStop(?array $server): string
{
    if ($server === null || !is_resource($server['process'])) return '';
    if (proc_get_status($server['process'])['running']) proc_terminate($server['process']);
    $diagnostics=''; foreach ([1,2] as $fd) if (is_resource($server['pipes'][$fd])) { $diagnostics.=stream_get_contents($server['pipes'][$fd]); fclose($server['pipes'][$fd]); }
    proc_close($server['process']);
    return $diagnostics;
}

function polRequestRaw(int $port, string $method, string $target, array $headers = [], string $body = ''): array
{
    $socket = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, 5);
    if ($socket === false) throw new TestFailure('connect raw HTTP');
    $wire = "{$method} {$target} HTTP/1.1\r\nHost: list.example\r\nConnection: close\r\n";
    foreach ($headers as $name => $value) $wire .= "{$name}: {$value}\r\n";
    fwrite($socket, $wire . "\r\n" . $body); fflush($socket); stream_socket_shutdown($socket, STREAM_SHUT_WR); stream_set_timeout($socket, 5);
    $raw = ''; while (!feof($socket)) { $chunk = fread($socket, 65536); if ($chunk === false) break; $raw .= $chunk; } fclose($socket);
    [$head, $responseBody] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    $lines = explode("\r\n", $head); preg_match('/^HTTP\/\d\.\d (\d{3})/', (string) array_shift($lines), $match); $parsed = [];
    foreach ($lines as $line) if (str_contains($line, ':')) { [$name,$value] = explode(':', $line, 2); $parsed[strtolower(trim($name))][] = trim($value); }
    return ['status'=>(int)($match[1]??0),'headers'=>$parsed,'body'=>$responseBody];
}

function polRequest(int $port, string $method, string $target, array $headers = [], string $body = ''): array
{
    global $polProtectedPaths, $polMutableRoots;
    return HttpReadOnlyFilesystemGuard::observe(
        static fn(): array => polRequestRaw($port, $method, $target, $headers, $body),
        $polProtectedPaths,
        $polMutableRoots,
    );
}

function polHeader(array $response, string $name): ?string
{
    $values = $response['headers'][strtolower($name)] ?? [];
    return count($values) === 1 ? $values[0] : null;
}

function polStableResponse(array $response): array
{
    unset($response['headers']['date'], $response['headers']['connection']);
    return $response;
}

function polParity(int $port, string $target): array
{
    $get = polRequest($port, 'GET', $target); $head = polRequest($port, 'HEAD', $target);
    assertSameValue($get['status'], $head['status'], 'HEAD parity status ' . $target);
    foreach (['content-type','content-length','allow','retry-after','x-content-type-options','referrer-policy','x-frame-options','content-security-policy','permissions-policy','cross-origin-opener-policy','cache-control','host'] as $name) assertSameValue(polHeader($get,$name),polHeader($head,$name),'HEAD parity header '.$name.' '.$target);
    assertSameValue('', $head['body'], 'HEAD has empty body ' . $target);
    return $get;
}

function polError(array $response, int $status, string $body, string $why, ?string $allow = null, ?string $retryAfter = null): void
{
    assertSameValue($status, $response['status'], $why . ' status');
    assertSameValue('text/plain; charset=UTF-8', polHeader($response,'content-type'), $why . ' content type');
    assertSameValue((string) strlen($body), polHeader($response,'content-length'), $why . ' content length');
    assertSameValue($body, $response['body'], $why . ' exact body');
    assertSameValue($allow, polHeader($response,'allow'), $why . ' Allow');
    assertSameValue($retryAfter, polHeader($response,'retry-after'), $why . ' exact Retry-After');
}

function polRejectsErrorHeaderMismatch(callable $probe, string $why): void
{
    try{$probe();}catch(TestFailure $failure){assertSameValue(true,str_contains($failure->getMessage(),'exact Retry-After'),$why.' fails specifically on Retry-After');return;}
    throw new TestFailure($why.' must reject mismatched Retry-After');
}

function polAuthorizationError(array $response,int $status,string $body,string $why,bool $correlation=false): void
{
    polError($response,$status,$body,$why);
    $expected=['cache-control','content-length','content-security-policy','content-type','cross-origin-opener-policy','permissions-policy','referrer-policy','x-content-type-options','x-frame-options'];
    if($correlation)$expected[]='x-correlation-id'; sort($expected);
    $actual=array_values(array_diff(array_keys($response['headers']),['date','connection','host']));sort($actual);
    assertSameValue($expected,$actual,$why.' exact singleton application headers');
    assertSameValue('no-store',polHeader($response,'cache-control'),$why.' no-store');
    assertSameValue('nosniff',polHeader($response,'x-content-type-options'),$why.' nosniff');
    assertSameValue('no-referrer',polHeader($response,'referrer-policy'),$why.' no-referrer');
    assertSameValue('DENY',polHeader($response,'x-frame-options'),$why.' DENY');
    assertSameValue("default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",polHeader($response,'content-security-policy'),$why.' exact CSP');
    assertSameValue('camera=(), microphone=(), geolocation=()',polHeader($response,'permissions-policy'),$why.' singleton Permissions-Policy');
    assertSameValue('same-origin',polHeader($response,'cross-origin-opener-policy'),$why.' singleton COOP');
    foreach(['set-cookie','location','www-authenticate','access-control-allow-origin','server','x-powered-by']as$name)assertSameValue(null,polHeader($response,$name),$why.' omits '.$name);
    assertSameValue($correlation?1:0,preg_match('/^[0-9a-f]{12}$/D',(string)polHeader($response,'x-correlation-id')),$why.' correlation contract');
    foreach(['mysqli','SQL','fm2_','role','permission','user_id','password','/home/','4512']as$secret)assertSameValue(false,str_contains($response['body'],$secret),$why.' redacts '.$secret);
}

function polUnavailable(array $environment,mysqli $db,string $category,string $why): void
{
    $probe=polStart($environment);
    try{$response=polReadOnly($db,fn()=>polRequest($probe['port'],'GET','/pilot/objects'),$why);polAuthorizationError($response,503,"Service unavailable.\n",$why,true);$id=(string)polHeader($response,'x-correlation-id');}
    finally{$log=polStop($probe);}
    preg_match_all('/^FMONITOR_AUTHORIZATION_UNAVAILABLE category=([A-Z_]+) correlation_id=([0-9a-f]{12})$/m',str_replace("\r",'',$log),$events,PREG_SET_ORDER);
    assertSameValue(1,count($events),$why.' exactly one safe logger event');assertSameValue($category,$events[0][1]??null,$why.' safe logger category');assertSameValue($id,$events[0][2]??null,$why.' response/log correlation equality');
    foreach(['SELECT','INSERT','UPDATE','DELETE','fm2_','objects.read','user_id','password','FMONITOR_DB_','legacy_','/home/','4512']as$secret)assertSameValue(false,str_contains($log,$secret),$why.' logger redacts '.$secret);
}

function polDocument(string $html): DOMDocument
{
    $document = new DOMDocument(); $old = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors(); libxml_use_internal_errors($old); assertSameValue(true, $loaded, 'success parses as HTML');
    return $document;
}

function polObjectListClassificationHooks(DOMXPath $xpath): array
{
    $normalized=static fn(string$value):string=>mb_strtolower(preg_replace('/\s+/u',' ',trim($value))??'','UTF-8');
    $structuralForbidden=static function(string$value)use($normalized):bool{$value=$normalized($value);return preg_match('~(?:^|[^a-z0-9])(?:origin|provenance|classification|migration|demo|source)(?:$|[^a-z0-9])|источник|происхожд|классификац|миграц|демо~u',$value)===1;};
    $copyForbidden=static function(string$value)use($normalized):bool{$value=$normalized($value);return preg_match('~^(?:origin|provenance|classification|migration|demo|source|источник|происхождение|классификация|миграция|демо)$|(?:data origin|origin classification|data provenance|provenance classification|migration data|demo data|data source)|(?:источник данных|происхождение данных|классификация данных|классификация происхождения|миграционные данные|демо[ -]данные|только миграция|нативный импорт|актив(?:ен|ны) с cutover|локальн(?:ая|ые) проверк(?:а|и))~u',$value)===1;};$violations=[];
    foreach($xpath->query("//main[@id='main-content'] | //main[@id='main-content']//*")as$element){
        foreach($element->attributes as$attribute){$name=strtolower($attribute->name);$isGenericData=str_starts_with($name,'data-');if((in_array($name,['id','class','role'],true)||$isGenericData)&&$structuralForbidden($attribute->value))$violations[]=$element->nodeName.'@'.$name.'='.$attribute->value;if($isGenericData&&$structuralForbidden($name))$violations[]=$element->nodeName.'@'.$name;}
    }
    foreach($xpath->query("//main[@id='main-content']//text()[not(ancestor::script or ancestor::style or ancestor::*[@hidden or @aria-hidden='true'] or ancestor::*[contains(concat(' ',normalize-space(@class),' '),' fm2-db-text ') or @data-db-text] or ancestor::a[starts-with(@href,'/pilot/objects/')])]")as$text)if($copyForbidden((string)$text->nodeValue))$violations[]='visible-copy='.$normalized((string)$text->nodeValue);
    return array_values(array_unique($violations));
}

function polObjectListPaginationHooks(DOMXPath $xpath): array
{
    $violations=[];
    foreach($xpath->query("//main[@id='main-content']//*")as$element){
        foreach($element->attributes as$attribute){$name=strtolower($attribute->name);$value=mb_strtolower($attribute->value,'UTF-8');if($name==='href'&&str_contains($value,'page='))$violations[]=$element->nodeName.'@href='.$attribute->value;if(in_array($name,['id','class','role','aria-label'],true)&&(str_contains($value,'pagination')||str_contains($value,'pager')||str_contains($value,'пагинац')||str_contains($value,'страниц')))$violations[]=$element->nodeName.'@'.$name.'='.$attribute->value;if(str_starts_with($name,'data-')&&(str_contains($name,'page')||str_contains($name,'pagination')||str_contains($name,'pager')))$violations[]=$element->nodeName.'@'.$name;}
    }
    $copy=preg_replace('/\s+/u',' ',trim((string)$xpath->evaluate("string(//main[@id='main-content'])")));
    foreach(['Показано ','Следующая страница','Предыдущая страница','Страница ','Страницы ']as$literal)if(str_contains($copy,$literal))$violations[]='copy='.$literal;
    if((int)$xpath->evaluate("count(//main[@id='main-content']//*[@aria-current='page'])")!==0)$violations[]='aria-current=page';
    return array_values(array_unique($violations));
}

function polSnapshot(mysqli $db): string
{
    $all=[]; $tables=$db->query('SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);
    foreach($tables as $table){$name=$table['TABLE_NAME'];$create=array_values($db->query("SHOW CREATE TABLE `{$name}`")->fetch_assoc());$rows=$db->query("SELECT * FROM `{$name}`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn($a,$b)=>strcmp(serialize($a),serialize($b)));$all[]=[$table,$create,$rows];}
    return hash('sha256',serialize($all));
}

function polReadOnly(mysqli $db, callable $request, string $why): mixed
{
    global $polProtectedPaths, $polMutableRoots;
    $before = polSnapshot($db);
    $result = HttpReadOnlyFilesystemGuard::observe($request, $polProtectedPaths, $polMutableRoots);
    assertSameValue($before, polSnapshot($db), $why . ' preserves all rows and AUTO_INCREMENT');
    return $result;
}

$token = bin2hex(random_bytes(6)); $database = 't_pol_' . $token; $reader = 'pol_' . $token; $denialReader='pold_'.$token;$listReadFaultReader='polf_'.$token; $password = 'select-' . $token;
$foreignDatabase='foreign_pol_'.$token;$ownership=[];$ownerRoot='';$mutableRoot='';$protectedArtifactRoot='';$foreignPath='';$foreignBefore=[];$css='';$polProtectedPaths=[];$polMutableRoots=[];$cleanupAttempts=[]; $admin = polDb(); $db = null;$listFaultDb=null; $server = null;
try {
    $ownership=TaskOwnedArtifactRoot::create('pol',$token);$ownerRoot=$ownership['root'];$mutableRoot=$ownerRoot.'/mutable';$protectedArtifactRoot=$ownerRoot.'/protected-artifact-store';$foreignPath=$ownership['parent'].'/foreign-'.$token;$css=$mutableRoot.'/shlz.css';mkdir($mutableRoot,0700);mkdir($protectedArtifactRoot,0700);mkdir($foreignPath,0700);file_put_contents($foreignPath.'/keep','foreign-bytes');scandir($foreignPath);$foreignBefore=[lstat($foreignPath),lstat($foreignPath.'/keep'),hash_file('sha256',$foreignPath.'/keep')];file_put_contents($protectedArtifactRoot.'/sentinel','immutable-production-artifact');file_put_contents($css,file_get_contents(dirname(__DIR__,3).'/shlz-ui/packages/styles/dist/shlz.css'));$css=(string)realpath($css);$polProtectedPaths=[$protectedArtifactRoot,$css,$foreignPath];$polMutableRoots=[$mutableRoot];
    $admin->query("CREATE DATABASE `{$foreignDatabase}` DEFAULT CHARSET=utf8mb4");$admin->query("CREATE TABLE `{$foreignDatabase}`.sentinel(id INT PRIMARY KEY,payload VARCHAR(40))");$admin->query("INSERT INTO `{$foreignDatabase}`.sentinel VALUES(1,'foreign-db-bytes')");
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $db=polDb($database);
    $safe503=['status'=>503,'headers'=>['content-type'=>['text/plain; charset=UTF-8'],'content-length'=>[(string)strlen("Service unavailable.\n")]],'body'=>"Service unavailable.\n"];$retry503=$safe503;$retry503['headers']['retry-after']=['60'];polError($safe503,503,"Service unavailable.\n",'no-retry helper positive control');polError($retry503,503,"Service unavailable.\n",'retry helper positive control',retryAfter:'60');polRejectsErrorHeaderMismatch(fn()=>polError($retry503,503,"Service unavailable.\n",'unexpected retry negative control'),'no-retry helper sensitivity');polRejectsErrorHeaderMismatch(fn()=>polError($safe503,503,"Service unavailable.\n",'missing retry negative control',retryAfter:'60'),'required-retry helper sensitivity');
    $classificationMainProbe=polDocument('<!doctype html><html><head><meta charset="utf-8"></head><body><main id="main-content" class="fm2-origin-view"><span>4513</span></main></body></html>');assertSameValue(1,count(polObjectListClassificationHooks(new DOMXPath($classificationMainProbe))),'classification oracle catches object-list main class mutation');
    $classificationDataValueProbe=polDocument('<!doctype html><html><head><meta charset="utf-8"></head><body><main id="main-content"><span data-kind="migration">4513</span></main></body></html>');assertSameValue(1,count(polObjectListClassificationHooks(new DOMXPath($classificationDataValueProbe))),'classification oracle catches generic data attribute value mutation');
    $classificationCopyProbe=polDocument('<!doctype html><html><head><meta charset="utf-8"></head><body><main id="main-content"><span>Источник данных: импорт</span></main></body></html>');assertSameValue(1,count(polObjectListClassificationHooks(new DOMXPath($classificationCopyProbe))),'classification oracle catches normalized visible copy mutation');
    $classificationSafeProbe=polDocument('<!doctype html><html><head><meta charset="utf-8"></head><body><aside class="fm2-provenance-panel">Источник данных</aside><main id="main-content"><span class="fm2-db-text">Москва, ул. Демонтажная, д. 7</span><span class="fm2-db-text">Source, дом 2</span><a href="/pilot/objects/4513">4513</a><span>2026-10-01 — 2026-11-30</span></main></body></html>');assertSameValue([],polObjectListClassificationHooks(new DOMXPath($classificationSafeProbe)),'classification oracle accepts collision-bearing DB object facts and ignores shared-shell provenance consumers');
    $paginationProbe=polDocument('<!doctype html><html><head><meta charset="utf-8"></head><body><main id="main-content"><nav class="shlz-pagination" aria-label="Страницы"><a href="/pilot/objects?page=2" data-page="2">Следующая страница</a></nav></main></body></html>');assertSameValue(true,count(polObjectListPaginationHooks(new DOMXPath($paginationProbe)))>=5,'pagination oracle catches nav, class, query, data and copy mutations');
    $db->query('CREATE TABLE legacy_users_roles(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(120),status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_users(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),email VARCHAR(300),role_id BIGINT UNSIGNED,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_fm_maintable(id BIGINT UNSIGNED PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),forbidden_secret VARCHAR(120)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,message VARCHAR(255)) ENGINE=InnoDB AUTO_INCREMENT=41');
    $db->query('CREATE TABLE legacy_ci_sessions(id VARCHAR(128) PRIMARY KEY,data BLOB NOT NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO legacy_users_roles VALUES(5,'Active',1),(6,'Inactive',0)");
    $db->query("INSERT INTO legacy_users VALUES(18,'Legacy identity decoy','legacy.object-list@example.invalid',5,1),(20,'Inactive','inactive@shlz.ru',5,0),(21,'Inactive role','role-inactive@shlz.ru',6,1),(22,'Duplicate A','duplicate@shlz.ru',5,1),(23,'Duplicate B','duplicate@shlz.ru',5,1)");
    $legacy=[[4515,'  Москва, ул. Третья, д. 3 ',' 1 ',' 77-000126 ','2026-10-05 19:00:00','2026-12-20',null,null,'SECRET-4515'],[4999,'Москва, ул. Не пилотная, д. 1','4','77-009999','2026-09-30','2026-10-30',null,null,'SECRET-4999'],[4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-18',null,null,'SECRET-4512'],[4513,'Москва, ул. Вторая, д. 7','1','77-000124','2026-10-01',null,'2026-11-30',null,'SECRET-4513']];
    $insert=$db->prepare('INSERT INTO legacy_fm_maintable VALUES(?,?,?,?,?,?,?,?,?)'); foreach($legacy as $row){$insert->bind_param('issssssss',...$row);$insert->execute();}
    $db->query("INSERT INTO legacy_logs(message) VALUES('sentinel log')"); $db->query("INSERT INTO legacy_ci_sessions VALUES('sentinel','opaque')");
    ProductionProcessSchemaMigration::apply($db);\FMonitor2\Tests\Support\PilotObjectReadRbacFixture::install($db);\FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration::apply($db,'');
    $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES(3,4515,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),(1,4512,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),(2,4513,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $admin->query("CREATE USER `{$reader}`@`%` IDENTIFIED BY '{$password}'");$admin->query("CREATE USER `{$denialReader}`@`%` IDENTIFIED BY '{$password}'");$admin->query("CREATE USER `{$listReadFaultReader}`@`%` IDENTIFIED BY '{$password}'");
    foreach(['legacy_users'=>['id','name','email','role_id','status'],'legacy_users_roles'=>['id','status'],'legacy_fm_maintable'=>['id','ordadr_address','entrance','regnumber','workdatestart','workdateendadjusted','plan_finish_date'],'fm2_installation_cases'=>['id','legacy_installation_object_id','process_state']] as $table=>$columns){$quoted=implode(',',array_map(static fn($c)=>'`'.$c.'`',$columns));$admin->query("GRANT SELECT ({$quoted}) ON `{$database}`.`{$table}` TO `{$reader}`@`%`");}
    foreach(\FMonitor2\Tests\Support\LocalRbacFixture::tables()as$table)$admin->query("GRANT SELECT ON `{$database}`.`{$table}` TO `{$reader}`@`%`");
    foreach(\FMonitor2\Tests\Support\LocalRbacFixture::tables()as$table)$admin->query("GRANT SELECT ON `{$database}`.`{$table}` TO `{$denialReader}`@`%`");
    foreach(\FMonitor2\Tests\Support\LocalRbacFixture::tables()as$table)$admin->query("GRANT SELECT ON `{$database}`.`{$table}` TO `{$listReadFaultReader}`@`%`");
    foreach(['legacy_fm_maintable'=>['id','ordadr_address','entrance','regnumber','workdatestart','workdateendadjusted','plan_finish_date']]as$table=>$columns){$quoted=implode(',',array_map(static fn($column)=>'`'.$column.'`',$columns));$admin->query("GRANT SELECT ({$quoted}) ON `{$database}`.`{$table}` TO `{$listReadFaultReader}`@`%`");}
    $environment=['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$database,'FMONITOR_DB_USER'=>$reader,'FMONITOR_DB_PASSWORD'=>$password,'FMONITOR_LEGACY_TABLE_PREFIX'=>'legacy_','FMONITOR_SHLZ_CSS_PATH'=>$css,'FMONITOR_AUTH_USER_ID'=>'18'];
    $pilotCss=(string)realpath(dirname(__DIR__,2).'/app/PilotHttp/pilot.css');assertSameValue(true,$pilotCss!==''&&basename($pilotCss)==='pilot.css','configured removal sentinel has canonical pilot CSS');$environment['FMONITOR_PILOT_CSS_PATH']=$pilotCss;$polProtectedPaths[]=$pilotCss;
    $before=polSnapshot($db);

    // Own-slice RED is deliberately first. Generic pre-GREEN fixtures grant via
    // role 900018, so deleting canonical role 5101 does not revoke the public
    // route. GREEN must install 5101; then this exact public GET becomes 403.
    $db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=5101 AND permission='objects.read'");$removed=$db->affected_rows;
    $redServer=polStart($environment);try{$redResponse=polRequest($redServer['port'],'GET','/pilot/objects');polAuthorizationError($redResponse,403,"Access denied.\n",'canonical fixture revoke controls public list before navigation');}finally{polStop($redServer);}
    assertSameValue(1,$removed,'canonical fixture contains exact independently approved grant');
    $db->query("INSERT INTO fm2_pilot_role_permissions(role_id,permission) VALUES(5101,'objects.read')");
    $listFaultDb=polDbAs($listReadFaultReader,$password,$database);assertSameValue(['user_id'=>'18','full_name'=>'Сотрудник ФКР (тест)','email'=>'fkr.object-list@example.invalid'],array_intersect_key($listFaultDb->query("SELECT u.user_id,u.full_name,u.email FROM fm2_pilot_users u JOIN fm2_pilot_user_roles ur ON ur.user_id=u.user_id JOIN fm2_pilot_roles r ON r.role_id=ur.role_id JOIN fm2_pilot_role_permissions p ON p.role_id=r.role_id WHERE u.user_id=18 AND u.status=1 AND u.activation_state='active' AND r.status=1 AND p.permission='objects.read'")->fetch_assoc(),array_flip(['user_id','full_name','email'])),'list-fault principal resolves exact local authority and representation identity');assertSameValue('4513',(string)$listFaultDb->query('SELECT id FROM legacy_fm_maintable WHERE id=4513')->fetch_assoc()['id'],'list-fault principal can read valid legacy list fact');polDeniedSelect($listFaultDb,'SELECT id FROM fm2_installation_cases LIMIT 1','list-fault principal exact case read');$listFaultDb->close();$listFaultDb=null;
    $listFaultEnvironment=array_replace($environment,['FMONITOR_DB_USER'=>$listReadFaultReader]);$probe=polStart($listFaultEnvironment);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'isolated list DB read unavailable'),503,"Service unavailable.\n",'list DB query unavailable after exact local authority and identity',retryAfter:'60');}finally{polStop($probe);}
    polUnavailable(array_replace($environment,['FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing','FMONITOR_DB_PASSWORD'=>'wrong']),$db,'AUTHORIZATION_READ_FAILED','local authorization precedes CSS under combined DB and CSS faults');
    $before=polSnapshot($db); $server=polStart($environment);

    // One acceptance tracer: all sub-assertions are sensitivity checks for PILOT-OBJECT-LIST-001 section 1.
    $get=polRequest($server['port'],'GET','/pilot/objects'); $head=polRequest($server['port'],'HEAD','/pilot/objects');
    assertSameValue(200,$get['status'],'collection route exists and succeeds');
    assertSameValue(false,array_key_exists('REMOTE_USER',$environment),'positive local object-list environment omits legacy transport identity');
    assertSameValue(true,str_contains($get['body'],'Сотрудник ФКР (тест)'),'authorized public list renders the exact canonical fixture actor');
    assertSameValue(false,str_contains($get['body'],'Legacy identity decoy'),'legacy user name decoy does not replace local representation identity');assertSameValue(false,str_contains($get['body'],'legacy.object-list@example.invalid'),'legacy email decoy is not rendered or used as local identity');
    assertSameValue('text/html; charset=UTF-8',polHeader($get,'content-type'),'collection HTML media type');
    assertSameValue((string)strlen($get['body']),polHeader($get,'content-length'),'GET exact byte length');
    assertSameValue($get['status'],$head['status'],'HEAD status parity'); assertSameValue(polHeader($get,'content-length'),polHeader($head,'content-length'),'HEAD GET Content-Length parity'); assertSameValue('',$head['body'],'HEAD empty body');
    $document=polDocument($get['body']); $xpath=new DOMXPath($document);
    assertSameValue(1,(int)$xpath->evaluate("count(/html[@lang='ru']/body[contains(concat(' ',normalize-space(@class),' '),' shlz-scope ')])"),'inherited Russian scoped shell');
    assertSameValue(1,(int)$xpath->evaluate("count(/html/head/link[@rel='stylesheet' and @href='/pilot/assets/pilot.css'])"),'configured pilot CSS composition');
    assertSameValue(1,(int)$xpath->evaluate("count(//div[contains(concat(' ',normalize-space(@class),' '),' fm2-shell ')])"),'configured shared shell composition');
    assertSameValue(1,(int)$xpath->evaluate("count(//main[@id='main-content' and @tabindex='-1']//h1[normalize-space(.)='Объекты монтажа'])"),'exact collection heading');
    assertSameValue(0,(int)$xpath->evaluate("count(//nav[@aria-label='Основная навигация']//*[normalize-space(.)='Моя работа' or @aria-label='Моя работа'] | //nav[@aria-label='Основная навигация']//a[@href='/pilot/'])"),'approved removal predecessor: no work item or root navigation destination');
    assertSameValue(1,(int)$xpath->evaluate("count(//a[@href='/pilot/objects' and normalize-space(.)='Объекты монтажа'])"),'current collection navigation');
    assertSameValue("default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",polHeader($get,'content-security-policy'),'success preserves current exact scripted UI-shell CSP');
    assertSameValue(1,(int)$xpath->evaluate("count(//script[@src='/pilot/assets/navigation.js' and count(@*)=1 and not(normalize-space(.))])"),'current UI-shell exact source-only navigation script');
    assertSameValue(1,$xpath->query('//script')->length,'current UI-shell has no additional script');
    assertSameValue(0,(int)$xpath->evaluate("count(//*[@*[starts-with(translate(name(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'on')]] | //a[starts-with(translate(normalize-space(@href),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'javascript:')])"),'current UI-shell has no inline event or javascript URL');
    assertSameValue(0,(int)$xpath->evaluate("count(//nav[contains(@aria-label,'происхождени') or contains(@aria-label,'Происхождени')] | //a[contains(@href,'origin=')] | //form[contains(@action,'origin=')] | //*[@name='origin'] | //*[@data-origin or @data-source-origin or @data-migration-category or @data-process-state])"),'approved queue has no origin filter, origin URL/control, data-origin or process-classification attribute');
    assertSameValue(0,(int)$xpath->evaluate("count(//main[@id='main-content']//nav)"),'object-list main has no filter or pagination navigation');
    assertSameValue([],polObjectListClassificationHooks($xpath),'object-list main has no origin/provenance/classification/migration/demo application hook');
    assertSameValue([],polObjectListPaginationHooks($xpath),'normal object-list success has no pagination nav/control/class/data/copy/query state');
    // Public shlz-ui docs fix a native a.shlz-link contract whose long text wraps in a
    // narrow consumer surface. Table instead owns a horizontally overflowing wrapper,
    // so this no-application-CSS slice chooses the spec-permitted semantic-list branch.
    $semanticLists=$xpath->query("//main[@id='main-content']//*[self::ul or self::ol][li]");
    assertSameValue(1,$semanticLists->length,'one narrow-flow semantic object list');
    assertSameValue(0,$xpath->query("//main[@id='main-content']//table | //main[@id='main-content']//*[contains(concat(' ',normalize-space(@class),' '),' shlz-table-wrap ')]")->length,'narrow representation does not select horizontally scrolling Table');
    $items=$xpath->query('./li',$semanticLists->item(0)); $actual=[];
    $expected=[['4513','/pilot/objects/4513',['77-000124','Москва, ул. Вторая, д. 7','1','2026-10-01','2026-11-30']],['4512','/pilot/objects/4512',['77-000123','Москва, ул. Примерная, д. 10','2','2026-10-05','2026-12-18']],['4515','/pilot/objects/4515',['77-000126','Москва, ул. Третья, д. 3','1','2026-10-05','2026-12-20']]];
    assertSameValue(3,$items->length,'exact semantic object item count');
    foreach($items as $item){$itemXpath=new DOMXPath($document);$links=$itemXpath->query(".//a[starts-with(@href,'/pilot/objects/') and contains(concat(' ',normalize-space(@class),' '),' shlz-link ')]",$item);assertSameValue(1,$links->length,'one public shlz Link in each semantic item');$link=$links->item(0);$actual[]=[trim($link->textContent),$link->getAttribute('href'),preg_replace('/\s+/u',' ',trim($item->textContent))];}
    foreach($expected as $index=>$row){assertSameValue($row[1],$actual[$index][1],'canonical semantic item order '.$row[0]);assertSameValue($row[0],$actual[$index][0],'exact visible linked ID '.$row[0]);assertSameValue(1,substr_count($actual[$index][2],$row[0]),'object ID appears once in own item '.$row[0]);foreach($row[2] as $literal)assertSameValue(true,str_contains($actual[$index][2],$literal),'approved value belongs to item '.$row[0].' '.$literal);}
    foreach(['4999','77-009999','SECRET-','process_state','Следующее действие','Происхождение данных','Только миграция','Нативный импорт','Активен с cutover','Активны с cutover','Демо-данные','Демо','Локальная проверка','Локальные проверки','migration_native','migration_active','demo_fixture','local_audit'] as $forbidden)assertSameValue(false,str_contains($get['body'],$forbidden),'excluded value/control '.$forbidden);
    foreach(['form','input','select','textarea','button','style'] as $tag)assertSameValue(0,$xpath->query('//'.$tag)->length,'forbidden element '.$tag);
    assertSameValue($before,polSnapshot($db),'GET and HEAD preserve all rows and AUTO_INCREMENT');
    $repeat=polReadOnly($db,fn()=>polRequest($server['port'],'GET','/pilot/objects'),'no-REMOTE positive repeat');assertSameValue(polStableResponse($get),polStableResponse($repeat),'no-REMOTE local actor repeat is byte-equivalent');
    $decoyServer=polStart(array_replace($environment,['REMOTE_USER'=>'legacy.object-list@example.invalid']));try{$decoy=polReadOnly($db,fn()=>polRequest($decoyServer['port'],'GET','/pilot/objects'),'legacy REMOTE decoy alongside local actor');assertSameValue(polStableResponse($get),polStableResponse($decoy),'legacy REMOTE decoy cannot replace local identity or grant');}finally{polStop($decoyServer);}
    $missingActorCombined=array_diff_key(array_replace($environment,['REMOTE_USER'=>'legacy.object-list@example.invalid','FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing','FMONITOR_PILOT_CSS_PATH'=>$pilotCss.'.missing','FMONITOR_DB_PASSWORD'=>'wrong']),['FMONITOR_AUTH_USER_ID'=>true]);$probe=polStart($missingActorCombined);try{polAuthorizationError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'missing local actor precedes combined CSS and DB faults'),401,"Authentication required.\n",'legacy REMOTE decoy cannot rescue missing local actor under combined faults');}finally{polStop($probe);}
    $probe=polStart(array_replace($environment,['FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing']));try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'isolated invalid CSS after local authorization'),503,"Service unavailable.\n",'CSS unavailable after healthy local authorization and before list read',retryAfter:'60');}finally{polStop($probe);}
    foreach(['/pilot/objects?sort=regnumber','/pilot/objects?origin=demo_fixture','/pilot/objects?origin=migration','/pilot/objects?origin=arbitrary'] as $queryTarget){$query=polReadOnly($db,fn()=>polRequest($server['port'],'GET',$queryTarget),'ignored query '.$queryTarget);assertSameValue(polStableResponse($get),polStableResponse($query),'query is byte-identical and ignored '.$queryTarget);}
    foreach(['/pilot/objects/','/pilot//objects','/pilot/objects/unknown','/pilot/objects/0','/pilot/objects/extra','/pilot/objects%2f','/pilot/objects/%75nknown'] as $path)polError(polReadOnly($db,fn()=>polParity($server['port'],$path),'invalid route '.$path),404,"Not found.\n",'invalid collection route '.$path);
    foreach(['POST','PUT','PATCH','DELETE','OPTIONS'] as $method)polError(polReadOnly($db,fn()=>polRequest($server['port'],$method,'/pilot/objects',['Content-Length'=>'6'],'opaque'),'rejected method '.$method),405,"Method not allowed.\n",'rejected method '.$method,'GET, HEAD');
    polStop($server);$server=null;

    // Combined faults pin the successor order: route/method, local RBAC
    // admission, inherited CSS, then list read. REMOTE_USER is not a positive
    // object-list dependency.
    $brokenEarly=array_replace($environment,['FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing','FMONITOR_DB_PASSWORD'=>'wrong']);
    $probe=polStart($brokenEarly);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects/'),'combined invalid route'),404,"Not found.\n",'route precedes local authorization, CSS and DB');polError(polReadOnly($db,fn()=>polRequest($probe['port'],'POST','/pilot/objects',['Content-Length'=>'6'],'opaque'),'combined invalid method'),405,"Method not allowed.\n",'method precedes local authorization, CSS and DB','GET, HEAD');}finally{polStop($probe);}

    $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES(90,9090,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $legacyOnly=array_diff_key(array_replace($environment,['FMONITOR_DB_USER'=>$denialReader,'REMOTE_USER'=>'legacy.object-list@example.invalid']),['FMONITOR_AUTH_USER_ID'=>true]);
    $denialEnvironment=array_replace($environment,['FMONITOR_DB_USER'=>$denialReader]);
    $negativeActors=[
        [$legacyOnly,401,"Authentication required.\n",'legacy-only REMOTE_USER'],
        [array_diff_key($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>true]),401,"Authentication required.\n",'trusted actor key absent'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'']),401,"Authentication required.\n",'empty trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'0']),401,"Authentication required.\n",'zero trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'-1']),401,"Authentication required.\n",'negative trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'abc']),401,"Authentication required.\n",'alphabetic trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>' 18']),401,"Authentication required.\n",'leading-space trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'18 ']),401,"Authentication required.\n",'trailing-space trusted actor'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'9999']),403,"Access denied.\n",'missing local user'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'19']),403,"Access denied.\n",'inactive activation'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'20']),403,"Access denied.\n",'inactive user'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'21']),403,"Access denied.\n",'inactive role'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'22']),403,"Access denied.\n",'missing exact grant'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'23']),403,"Access denied.\n",'case near-match grant'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'24']),403,"Access denied.\n",'space near-match grant'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'25']),403,"Access denied.\n",'wildcard near-match grant'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'26']),403,"Access denied.\n",'suffix near-match grant'],
        [array_replace($denialEnvironment,['FMONITOR_AUTH_USER_ID'=>'27']),403,"Access denied.\n",'missing assignment'],
    ];
    foreach($negativeActors as[$actorEnvironment,$status,$body,$why]){$probe=polStart($actorEnvironment);try{polAuthorizationError(polReadOnly($db,fn()=>polRequest($probe['port'],'GET','/pilot/objects',['Cookie'=>'actor=18','X-Authenticated-User-ID'=>'18']),'RBAC denial '.$why),$status,$body,$why.' precedes forbidden list handler/read');}finally{polStop($probe);}}
    $probe=polStart($environment);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'dangling imported case'),503,"Service unavailable.\n",'dangling imported case fails closed',retryAfter:'60');}finally{polStop($probe);} $db->query('DELETE FROM fm2_installation_cases WHERE id=90');

    $db->query("UPDATE legacy_fm_maintable SET regnumber='   ' WHERE id=4513");
    $probe=polStart($environment);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'invalid required value'),503,"Service unavailable.\n",'invalid required value fails closed',retryAfter:'60');}finally{polStop($probe);} $db->query("UPDATE legacy_fm_maintable SET regnumber='77-000124' WHERE id=4513");

    $db->query('RENAME TABLE fm2_pilot_role_permissions TO fm2_pilot_role_permissions_missing');
    try{polUnavailable($environment,$db,'AUTHORIZATION_SCHEMA_INVALID','authorization schema unavailable');}
    finally{$db->query('RENAME TABLE fm2_pilot_role_permissions_missing TO fm2_pilot_role_permissions');}
    polUnavailable(array_replace($environment,['FMONITOR_DB_PASSWORD'=>'wrong']),$db,'AUTHORIZATION_READ_FAILED','authorization DB read failure');
    $db->query('DELETE FROM fm2_installation_cases'); $emptyBefore=polSnapshot($db); $server=polStart($environment); $empty=polParity($server['port'],'/pilot/objects'); assertSameValue(200,$empty['status'],'empty collection succeeds'); assertSameValue(true,str_contains($empty['body'],'Импортированные объекты монтажа пока отсутствуют.'),'exact empty text'); assertSameValue(0,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$empty['body']),'empty has no card links'); assertSameValue($emptyBefore,polSnapshot($db),'empty read-only'); polStop($server);$server=null;
    $caseValues=[];$legacyValues=[];for($i=1;$i<=501;$i++){$id=6000+$i;$caseValues[]="({$i},{$id},'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)";$legacyValues[]="({$id},'Ceiling address','1','C-{$id}','2026-10-01','2026-11-01',NULL,NULL,'hidden')";} $db->query('INSERT INTO legacy_fm_maintable VALUES'.implode(',',$legacyValues)); $db->query('INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES'.implode(',',$caseValues));
    $server=polStart($environment);$overflow=polReadOnly($db,fn()=>polParity($server['port'],'/pilot/objects'),'501-case overflow');assertSameValue(503,$overflow['status'],'501 imported cases fail closed without pagination');assertSameValue("Service unavailable.\n",$overflow['body'],'501-case overflow exact redacted body');assertSameValue('60',polHeader($overflow,'retry-after'),'501-case overflow Retry-After');assertSameValue(0,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$overflow['body']),'501-case overflow never returns a partial page');polStop($server);$server=null;$db->query('DELETE FROM fm2_installation_cases WHERE id=501');$server=polStart($environment);$ceiling=polReadOnly($db,fn()=>polParity($server['port'],'/pilot/objects'),'500-case ceiling');assertSameValue(200,$ceiling['status'],'exactly 500 imported cases succeed');assertSameValue(500,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$ceiling['body']),'exactly 500 returns the complete unpaginated set');$ceilingXpath=new DOMXPath(polDocument($ceiling['body']));assertSameValue([],polObjectListPaginationHooks($ceilingXpath),'500-case success has no pagination nav/control/class/data/copy/query state');assertSameValue(0,(int)$ceilingXpath->evaluate("count(//main[@id='main-content']//nav | //main[@id='main-content']//*[self::form or self::input or self::select or self::textarea or self::button])"),'500-case success has no pagination navigation or controls');assertSameValue([],polObjectListClassificationHooks($ceilingXpath),'500-case success has no origin/provenance/classification/migration/demo application hook');polStop($server);$server=null;
    $manifest=$db->query("SELECT u.user_id,u.full_name,u.email,u.status,u.activation_state,r.role_id,r.status role_status,p.permission FROM fm2_pilot_users u JOIN fm2_pilot_user_roles ur ON ur.user_id=u.user_id JOIN fm2_pilot_roles r ON r.role_id=ur.role_id JOIN fm2_pilot_role_permissions p ON p.role_id=r.role_id WHERE u.user_id=18 ORDER BY r.role_id,p.permission")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([['user_id'=>'18','full_name'=>'Сотрудник ФКР (тест)','email'=>'fkr.object-list@example.invalid','status'=>'1','activation_state'=>'active','role_id'=>'5101','role_status'=>'1','permission'=>'objects.read']],$manifest,'canonical object-list fixture uses exact independently approved actor and role manifest');
    $grantBefore=polSnapshot($db);$db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=5101 AND permission='objects.read'");assertSameValue(1,$db->affected_rows,'committed revoke removes exact canonical grant');$server=polStart($environment);$revoked=polRequest($server['port'],'GET','/pilot/objects');polError($revoked,403,"Access denied.\n",'committed revoke denies next invocation before list read');polStop($server);$server=null;assertSameValue(false,$grantBefore===polSnapshot($db),'only explicit fixture-admin revoke changes committed snapshot');
    echo "PASS: PILOT-OBJECT-LIST-001 public HTTP collection\n";
} finally {
    $primary=$GLOBALS['__pol_primary']??null;$cleanupErrors=[];
    foreach([
        'server PID/pipes'=>static fn()=>polStop($server),
        'list-fault DB resource'=>static fn()=>$listFaultDb instanceof mysqli?$listFaultDb->close():null,
        'DB resource'=>static fn()=>$db instanceof mysqli?$db->close():null,
        'list-fault DB user'=>static fn()=>$admin->query("DROP USER IF EXISTS `{$listReadFaultReader}`@`%`"),
        'denial DB user'=>static fn()=>$admin->query("DROP USER IF EXISTS `{$denialReader}`@`%`"),
        'reader DB user'=>static fn()=>$admin->query("DROP USER IF EXISTS `{$reader}`@`%`"),
        'task database'=>static fn()=>$admin->query("DROP DATABASE IF EXISTS `{$database}`"),
        'mutable root'=>static fn()=>$ownership!==[]?TaskOwnedArtifactRoot::cleanup($ownership,'pol',$token):null,
    ]as$name=>$cleanup){$cleanupAttempts[$name]=($cleanupAttempts[$name]??0)+1;try{$cleanup();}catch(Throwable $failure){$cleanupErrors[]=$failure;}}
    try{assertSameValue(array_fill_keys(['server PID/pipes','list-fault DB resource','DB resource','list-fault DB user','denial DB user','reader DB user','task database','mutable root'],1),$cleanupAttempts,'cleanup inventory attempted exactly once');assertSameValue([], $admin->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='{$database}'")->fetch_all(MYSQLI_ASSOC),'exact task DB removed');assertSameValue([], $admin->query("SELECT User FROM mysql.user WHERE User IN ('{$reader}','{$denialReader}','{$listReadFaultReader}')")->fetch_all(MYSQLI_ASSOC),'exact task users removed');assertSameValue([['SCHEMA_NAME'=>$foreignDatabase]],$admin->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='{$foreignDatabase}'")->fetch_all(MYSQLI_ASSOC),'foreign DB preserved');assertSameValue([['id'=>'1','payload'=>'foreign-db-bytes']],$admin->query("SELECT * FROM `{$foreignDatabase}`.sentinel")->fetch_all(MYSQLI_ASSOC),'foreign DB bytes preserved');assertSameValue($foreignBefore,[lstat($foreignPath),lstat($foreignPath.'/keep'),hash_file('sha256',$foreignPath.'/keep')],'foreign file bytes and metadata preserved');}catch(Throwable $failure){$cleanupErrors[]=$failure;}
    try{$admin->query("DROP DATABASE IF EXISTS `{$foreignDatabase}`");if(is_file($foreignPath.'/keep'))unlink($foreignPath.'/keep');if(is_dir($foreignPath))rmdir($foreignPath);$admin->close();}catch(Throwable $failure){$cleanupErrors[]=$failure;}
    if($cleanupErrors!==[])throw new TestFailure('attempt-all cleanup failure: '.$cleanupErrors[0]->getMessage(),0,$cleanupErrors[0]);
}
