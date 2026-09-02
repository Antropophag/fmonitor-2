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

function polStop(?array $server): void
{
    if ($server === null || !is_resource($server['process'])) return;
    if (proc_get_status($server['process'])['running']) proc_terminate($server['process']);
    foreach ([1,2] as $fd) if (is_resource($server['pipes'][$fd])) { stream_get_contents($server['pipes'][$fd]); fclose($server['pipes'][$fd]); }
    proc_close($server['process']);
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

function polParity(int $port, string $target): array
{
    $get = polRequest($port, 'GET', $target); $head = polRequest($port, 'HEAD', $target);
    assertSameValue($get['status'], $head['status'], 'HEAD parity status ' . $target);
    foreach (['content-type','content-length','allow','retry-after','x-content-type-options','referrer-policy','x-frame-options','content-security-policy','permissions-policy','cross-origin-opener-policy','cache-control','host'] as $name) assertSameValue(polHeader($get,$name),polHeader($head,$name),'HEAD parity header '.$name.' '.$target);
    assertSameValue('', $head['body'], 'HEAD has empty body ' . $target);
    return $get;
}

function polError(array $response, int $status, string $body, string $why, ?string $allow = null): void
{
    assertSameValue($status, $response['status'], $why . ' status');
    assertSameValue('text/plain; charset=UTF-8', polHeader($response,'content-type'), $why . ' content type');
    assertSameValue((string) strlen($body), polHeader($response,'content-length'), $why . ' content length');
    assertSameValue($body, $response['body'], $why . ' exact body');
    assertSameValue($allow, polHeader($response,'allow'), $why . ' Allow');
    assertSameValue($status === 503 ? '60' : null, polHeader($response,'retry-after'), $why . ' Retry-After');
}

function polDocument(string $html): DOMDocument
{
    $document = new DOMDocument(); $old = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors(); libxml_use_internal_errors($old); assertSameValue(true, $loaded, 'success parses as HTML');
    return $document;
}

function polSnapshot(mysqli $db): string
{
    $all=[]; $tables=$db->query('SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);
    foreach($tables as $table){$name=$table['TABLE_NAME'];$rows=$db->query("SELECT * FROM `{$name}`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn($a,$b)=>json_encode($a)<=>json_encode($b));$all[]=[$table,$rows];}
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

$token = bin2hex(random_bytes(6)); $database = 't_pol_' . $token; $reader = 'pol_' . $token; $password = 'select-' . $token;
$ownership=[];$ownerRoot='';$mutableRoot='';$protectedArtifactRoot='';$css='';$polProtectedPaths=[];$polMutableRoots=[]; $admin = polDb(); $db = null; $server = null;
try {
    $ownership=TaskOwnedArtifactRoot::create('pol',$token);$ownerRoot=$ownership['root'];$mutableRoot=$ownerRoot.'/mutable';$protectedArtifactRoot=$ownerRoot.'/protected-artifact-store';$css=$mutableRoot.'/shlz.css';mkdir($mutableRoot,0700);mkdir($protectedArtifactRoot,0700);file_put_contents($protectedArtifactRoot.'/sentinel','immutable-production-artifact');file_put_contents($css,file_get_contents(dirname(__DIR__,3).'/shlz-ui/packages/styles/dist/shlz.css'));$css=(string)realpath($css);$polProtectedPaths=[$protectedArtifactRoot,$css];$polMutableRoots=[$mutableRoot];
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $db=polDb($database);
    $db->query('CREATE TABLE legacy_users_roles(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(120),status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_users(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),email VARCHAR(300),role_id BIGINT UNSIGNED,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_fm_maintable(id BIGINT UNSIGNED PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),forbidden_secret VARCHAR(120)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE legacy_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,message VARCHAR(255)) ENGINE=InnoDB AUTO_INCREMENT=41');
    $db->query('CREATE TABLE legacy_ci_sessions(id VARCHAR(128) PRIMARY KEY,data BLOB NOT NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO legacy_users_roles VALUES(5,'Active',1),(6,'Inactive',0)");
    $db->query("INSERT INTO legacy_users VALUES(18,'Сотрудник ФКР (тест)','fkr.object-list@example.invalid',5,1),(20,'Inactive','inactive@shlz.ru',5,0),(21,'Inactive role','role-inactive@shlz.ru',6,1),(22,'Duplicate A','duplicate@shlz.ru',5,1),(23,'Duplicate B','duplicate@shlz.ru',5,1)");
    $legacy=[[4515,'  Москва, ул. Третья, д. 3 ',' 1 ',' 77-000126 ','2026-10-05 19:00:00','2026-12-20',null,null,'SECRET-4515'],[4999,'Москва, ул. Не пилотная, д. 1','4','77-009999','2026-09-30','2026-10-30',null,null,'SECRET-4999'],[4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-18',null,null,'SECRET-4512'],[4513,'Москва, ул. Вторая, д. 7','1','77-000124','2026-10-01',null,'2026-11-30',null,'SECRET-4513']];
    $insert=$db->prepare('INSERT INTO legacy_fm_maintable VALUES(?,?,?,?,?,?,?,?,?)'); foreach($legacy as $row){$insert->bind_param('issssssss',...$row);$insert->execute();}
    $db->query("INSERT INTO legacy_logs(message) VALUES('sentinel log')"); $db->query("INSERT INTO legacy_ci_sessions VALUES('sentinel','opaque')");
    ProductionProcessSchemaMigration::apply($db);\FMonitor2\Tests\Support\PilotObjectReadRbacFixture::install($db);\FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration::apply($db,'');
    $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES(3,4515,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),(1,4512,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),(2,4513,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $admin->query("CREATE USER `{$reader}`@`%` IDENTIFIED BY '{$password}'");
    foreach(['legacy_users'=>['id','name','email','role_id','status'],'legacy_users_roles'=>['id','status'],'legacy_fm_maintable'=>['id','ordadr_address','entrance','regnumber','workdatestart','workdateendadjusted','plan_finish_date'],'fm2_installation_cases'=>['id','legacy_installation_object_id','process_state']] as $table=>$columns){$quoted=implode(',',array_map(static fn($c)=>'`'.$c.'`',$columns));$admin->query("GRANT SELECT ({$quoted}) ON `{$database}`.`{$table}` TO `{$reader}`@`%`");}
    foreach(\FMonitor2\Tests\Support\LocalRbacFixture::tables()as$table)$admin->query("GRANT SELECT ON `{$database}`.`{$table}` TO `{$reader}`@`%`");
    $environment=['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$database,'FMONITOR_DB_USER'=>$reader,'FMONITOR_DB_PASSWORD'=>$password,'FMONITOR_LEGACY_TABLE_PREFIX'=>'legacy_','FMONITOR_SHLZ_CSS_PATH'=>$css,'REMOTE_USER'=>'sidorov@shlz.ru','FMONITOR_AUTH_USER_ID'=>'18'];
    $before=polSnapshot($db); $server=polStart($environment);

    // One acceptance tracer: all sub-assertions are sensitivity checks for PILOT-OBJECT-LIST-001 section 1.
    $get=polRequest($server['port'],'GET','/pilot/objects'); $head=polRequest($server['port'],'HEAD','/pilot/objects');
    assertSameValue(200,$get['status'],'collection route exists and succeeds');
    assertSameValue(true,str_contains($get['body'],'Сотрудник ФКР (тест)'),'authorized public list renders the exact canonical fixture actor');
    assertSameValue('text/html; charset=UTF-8',polHeader($get,'content-type'),'collection HTML media type');
    assertSameValue((string)strlen($get['body']),polHeader($get,'content-length'),'GET exact byte length');
    assertSameValue($get['status'],$head['status'],'HEAD status parity'); assertSameValue(polHeader($get,'content-length'),polHeader($head,'content-length'),'HEAD GET Content-Length parity'); assertSameValue('',$head['body'],'HEAD empty body');
    $document=polDocument($get['body']); $xpath=new DOMXPath($document);
    assertSameValue(1,(int)$xpath->evaluate("count(/html[@lang='ru']/body[contains(concat(' ',normalize-space(@class),' '),' shlz-scope ')])"),'inherited Russian scoped shell');
    assertSameValue(1,(int)$xpath->evaluate("count(//main[@id='main-content' and @tabindex='-1']//h1[normalize-space(.)='Объекты монтажа'])"),'exact collection heading');
    assertSameValue(0,(int)$xpath->evaluate("count(//nav[@aria-label='Основная навигация']//*[normalize-space(.)='Моя работа' or @aria-label='Моя работа'] | //nav[@aria-label='Основная навигация']//a[@href='/pilot/'])"),'approved removal predecessor: no work item or root navigation destination');
    assertSameValue(1,(int)$xpath->evaluate("count(//a[@href='/pilot/objects' and normalize-space(.)='Объекты монтажа'])"),'current collection navigation');
    assertSameValue(1,(int)$xpath->evaluate("count(//nav[@aria-label='Фильтр по происхождению данных']//a[@href='/pilot/objects?origin=demo_fixture'])"),'origin filter exposes demo data explicitly');
    assertSameValue(3,(int)$xpath->evaluate("count(//tr[@data-origin='demo_fixture'])"),'absence of immutable migration provenance is visibly classified as demo fixture');
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
    foreach(['4999','77-009999','SECRET-','process_state','Следующее действие'] as $forbidden)assertSameValue(false,str_contains($get['body'],$forbidden),'excluded value/control '.$forbidden);
    foreach(['form','input','select','textarea','button','script','style'] as $tag)assertSameValue(0,$xpath->query('//'.$tag)->length,'forbidden element '.$tag);
    assertSameValue($before,polSnapshot($db),'GET and HEAD preserve all rows and AUTO_INCREMENT');
    $query=polRequest($server['port'],'GET','/pilot/objects?sort=regnumber'); unset($get['headers']['date'],$get['headers']['connection'],$query['headers']['date'],$query['headers']['connection']); assertSameValue($get,$query,'query is byte-identical and ignored');
    foreach(['/pilot/objects/','/pilot//objects','/pilot/objects/unknown','/pilot/objects/0','/pilot/objects/extra','/pilot/objects%2f','/pilot/objects/%75nknown'] as $path)polError(polReadOnly($db,fn()=>polParity($server['port'],$path),'invalid route '.$path),404,"Not found.\n",'invalid collection route '.$path);
    foreach(['POST','PUT','PATCH','DELETE','OPTIONS'] as $method)polError(polReadOnly($db,fn()=>polRequest($server['port'],$method,'/pilot/objects',['Content-Length'=>'6'],'opaque'),'rejected method '.$method),405,"Method not allowed.\n",'rejected method '.$method,'GET, HEAD');
    polStop($server);$server=null;

    // Combined faults pin the inherited order: route/method, identity, CSS, user/role, then list read.
    $brokenEarly=array_replace(array_diff_key($environment,['REMOTE_USER'=>true]),['FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing','FMONITOR_DB_PASSWORD'=>'wrong']);
    $probe=polStart($brokenEarly);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects/'),'combined invalid route'),404,"Not found.\n",'route precedes identity, CSS and DB');polError(polReadOnly($db,fn()=>polRequest($probe['port'],'POST','/pilot/objects',['Content-Length'=>'6'],'opaque'),'combined invalid method'),405,"Method not allowed.\n",'method precedes identity, CSS and DB','GET, HEAD');polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'combined missing identity'),401,"Authentication required.\n",'identity precedes CSS and DB');}finally{polStop($probe);}
    $probe=polStart(array_replace($environment,['FMONITOR_SHLZ_CSS_PATH'=>$css.'.missing','FMONITOR_DB_PASSWORD'=>'wrong']));try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'combined invalid CSS'),503,"Service unavailable.\n",'CSS precedes DB');}finally{polStop($probe);}

    $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES(90,9090,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $legacyOnly=array_diff_key($environment,['FMONITOR_AUTH_USER_ID'=>true]);$legacyOnly['REMOTE_USER']='sidorov@shlz.ru';
    $negativeActors=[
        [$legacyOnly,401,"Authentication required.\n",'legacy-only REMOTE_USER'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'9999']),403,"Access denied.\n",'missing local user'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'19']),403,"Access denied.\n",'inactive activation'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'20']),403,"Access denied.\n",'inactive user'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'21']),403,"Access denied.\n",'inactive role'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'22']),403,"Access denied.\n",'missing exact grant'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'23']),403,"Access denied.\n",'case near-match grant'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'24']),403,"Access denied.\n",'space near-match grant'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'25']),403,"Access denied.\n",'wildcard near-match grant'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'26']),403,"Access denied.\n",'suffix near-match grant'],
        [array_replace($environment,['FMONITOR_AUTH_USER_ID'=>'27']),403,"Access denied.\n",'missing assignment'],
    ];
    foreach($negativeActors as[$actorEnvironment,$status,$body,$why]){$probe=polStart($actorEnvironment);try{polError(polReadOnly($db,fn()=>polRequest($probe['port'],'GET','/pilot/objects'),'RBAC denial '.$why),$status,$body,$why.' precedes dangling list handler/read');}finally{polStop($probe);}}
    $probe=polStart($environment);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'dangling imported case'),503,"Service unavailable.\n",'dangling imported case fails closed');}finally{polStop($probe);} $db->query('DELETE FROM fm2_installation_cases WHERE id=90');

    $db->query("UPDATE legacy_fm_maintable SET regnumber='   ' WHERE id=4513");
    $probe=polStart($environment);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),'invalid required value'),503,"Service unavailable.\n",'invalid required value fails closed');}finally{polStop($probe);} $db->query("UPDATE legacy_fm_maintable SET regnumber='77-000124' WHERE id=4513");

    foreach([[array_replace($environment,['REMOTE_USER'=>' malformed ']),401,"Authentication required.\n",'malformed identity'],[array_replace($environment,['FMONITOR_DB_PASSWORD'=>'wrong']),503,"Service unavailable.\n",'database failure']] as [$env,$status,$body,$why]){$probe=polStart($env);try{polError(polReadOnly($db,fn()=>polParity($probe['port'],'/pilot/objects'),$why),$status,$body,$why);}finally{polStop($probe);}}
    $db->query('DELETE FROM fm2_installation_cases'); $emptyBefore=polSnapshot($db); $server=polStart($environment); $empty=polParity($server['port'],'/pilot/objects'); assertSameValue(200,$empty['status'],'empty collection succeeds'); assertSameValue(true,str_contains($empty['body'],'Импортированные объекты монтажа пока отсутствуют.'),'exact empty text'); assertSameValue(0,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$empty['body']),'empty has no card links'); assertSameValue($emptyBefore,polSnapshot($db),'empty read-only'); polStop($server);$server=null;
    $caseValues=[];$legacyValues=[];for($i=1;$i<=501;$i++){$id=6000+$i;$caseValues[]="({$i},{$id},'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)";$legacyValues[]="({$id},'Ceiling address','1','C-{$id}','2026-10-01','2026-11-01',NULL,NULL,'hidden')";} $db->query('INSERT INTO legacy_fm_maintable VALUES'.implode(',',$legacyValues)); $db->query('INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES'.implode(',',$caseValues));
    $server=polStart($environment);$firstPage=polReadOnly($db,fn()=>polParity($server['port'],'/pilot/objects'),'501-case page one');assertSameValue(200,$firstPage['status'],'501 imported cases paginate');assertSameValue(50,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$firstPage['body']),'fixed page size');assertSameValue(true,str_contains($firstPage['body'],'Показано 50 из 501')&&str_contains($firstPage['body'],'class="shlz-pagination"')&&str_contains($firstPage['body'],'href="/pilot/objects?page=2" aria-label="Страница 2"'),'SHLZ pagination total and direct page rendered');assertSameValue(1,preg_match_all('~aria-current="page" aria-label="Страница 1"~',$firstPage['body']),'exact current page');assertSameValue(true,str_contains($firstPage['body'],'aria-disabled="true"')&&str_contains($firstPage['body'],'rel="next"'),'boundary directions');$lastPage=polReadOnly($db,fn()=>polParity($server['port'],'/pilot/objects?page=11'),'501-case last page');assertSameValue(200,$lastPage['status'],'last page succeeds');assertSameValue(1,preg_match_all('~href=["\']/pilot/objects/\d+["\']~',$lastPage['body']),'last page remainder');assertSameValue(true,str_contains($lastPage['body'],'href="/pilot/objects?page=11" aria-current="page"')&&str_contains($lastPage['body'],'rel="prev"')&&str_contains($lastPage['body'],'Следующая страница недоступна'),'last page current and boundary rendered');polStop($server);$server=null;
    $manifest=$db->query("SELECT u.user_id,u.full_name,u.email,u.status,u.activation_state,r.role_id,r.status role_status,p.permission FROM fm2_pilot_users u JOIN fm2_pilot_user_roles ur ON ur.user_id=u.user_id JOIN fm2_pilot_roles r ON r.role_id=ur.role_id JOIN fm2_pilot_role_permissions p ON p.role_id=r.role_id WHERE u.user_id=18 ORDER BY r.role_id,p.permission")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([['user_id'=>'18','full_name'=>'Сотрудник ФКР (тест)','email'=>'fkr.object-list@example.invalid','status'=>'1','activation_state'=>'active','role_id'=>'5101','role_status'=>'1','permission'=>'objects.read']],$manifest,'canonical object-list fixture uses exact independently approved actor and role manifest');
    $grantBefore=polSnapshot($db);$db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=5101 AND permission='objects.read'");assertSameValue(1,$db->affected_rows,'committed revoke removes exact canonical grant');$server=polStart($environment);$revoked=polRequest($server['port'],'GET','/pilot/objects');polError($revoked,403,"Access denied.\n",'committed revoke denies next invocation before list read');polStop($server);$server=null;assertSameValue(false,$grantBefore===polSnapshot($db),'only explicit fixture-admin revoke changes committed snapshot');
    echo "PASS: PILOT-OBJECT-LIST-001 public HTTP collection\n";
} finally {
    polStop($server); if($db instanceof mysqli)$db->close(); $admin->query("DROP DATABASE IF EXISTS `{$database}`"); $admin->query("DROP USER IF EXISTS `{$reader}`@`%`"); $admin->close(); if($ownership!==[])TaskOwnedArtifactRoot::cleanup($ownership,'pol',$token);
}
