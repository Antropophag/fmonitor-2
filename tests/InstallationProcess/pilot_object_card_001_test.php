<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/LocalRbacFixture.php';

use FMonitor2\Tests\Support\HttpReadOnlyFilesystemGuard;
use FMonitor2\Tests\Support\TaskOwnedArtifactRoot;

// Specification: PILOT-OBJECT-CARD-001 v0.2.
// Confirmed public seam: raw HTTP GET|HEAD /pilot/objects/{positive-id}.

use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;

function pocDb(?string $database = null): mysqli
{
    $db = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
        $database,
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $db->set_charset('utf8mb4');
    return $db;
}

function pocMigrate(string $database, string $prefix): void
{
    $environment = [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
    $command = [...$command, PHP_BINARY, dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php'];
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) throw new TestFailure('canonical migration start');
    fclose($pipes[0]); $stdout=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $exit=proc_close($process); $result=json_decode(trim($stdout),true);
    assertSameValue([0,true,''],[$exit,$result['ok']??null,$stderr],'canonical migration prepares shared-shell fixture');
}

function pocPort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) throw new TestFailure('allocate loopback port');
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    return (int) substr($name, strrpos($name, ':') + 1);
}

/** @return array{process:resource,pipes:array<int,resource>,port:int} */
function pocStart(array $environment): array
{
    for ($attempt = 0; $attempt < 10; $attempt++) {
    $port = pocPort();
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
    $command = [...$command, PHP_BINARY, '-d', 'expose_php=0', '-S', '127.0.0.1:' . $port, dirname(__DIR__, 2) . '/public/router.php'];
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) throw new TestFailure('start php server');
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $deadline = microtime(true) + 5;
    while (microtime(true) < $deadline) {
        $socket = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, .1);
        if ($socket !== false) { fclose($socket); return compact('process', 'pipes', 'port'); }
        if (!(proc_get_status($process)['running'] ?? false)) break;
        usleep(50000);
    }
    foreach ([1, 2] as $fd) if (is_resource($pipes[$fd])) fclose($pipes[$fd]);
    proc_close($process);
    }
    throw new TestFailure('php server did not listen');
}

function pocStop(?array $server): void
{
    if ($server === null || !is_resource($server['process'])) return;
    if (proc_get_status($server['process'])['running']) proc_terminate($server['process']);
    foreach ([1, 2] as $fd) if (is_resource($server['pipes'][$fd])) { stream_get_contents($server['pipes'][$fd]); fclose($server['pipes'][$fd]); }
    proc_close($server['process']);
}

function pocRequestRaw(int $port, string $method, string $target, array $headers = [], string $body = ''): array
{
    $socket = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, 5);
    if ($socket === false) throw new TestFailure('connect raw HTTP');
    $request = "{$method} {$target} HTTP/1.1\r\nHost: card.example\r\nConnection: close\r\n";
    foreach ($headers as $name => $value) $request .= "{$name}: {$value}\r\n";
    $request .= "\r\n{$body}";
    fwrite($socket, $request); fflush($socket); stream_socket_shutdown($socket, STREAM_SHUT_WR); stream_set_timeout($socket, 5);
    $raw = '';
    while (!feof($socket)) { $chunk = fread($socket, 65536); if ($chunk === false) break; $raw .= $chunk; }
    fclose($socket);
    return pocParseRaw($raw);
}

function pocRequest(int $port, string $method, string $target, array $headers = [], string $body = ''): array
{
    global $pocProtectedPaths, $pocMutableRoots;
    return HttpReadOnlyFilesystemGuard::observe(
        static fn(): array => pocRequestRaw($port, $method, $target, $headers, $body),
        $pocProtectedPaths,
        $pocMutableRoots,
    );
}

function pocParseRaw(string $raw): array
{
    [$head, $responseBody] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    $lines = explode("\r\n", $head); $statusLine = array_shift($lines); preg_match('/^HTTP\/\d\.\d (\d{3})/', (string) $statusLine, $match);
    $parsed = [];
    foreach ($lines as $line) if (str_contains($line, ':')) { [$name, $value] = explode(':', $line, 2); $parsed[strtolower(trim($name))][] = trim($value); }
    return ['status'=>(int)($match[1]??0), 'headers'=>$parsed, 'body'=>$responseBody];
}

/** @return array{array,array} */
function pocConcurrentGets(int $port, string $target, ?callable $duringObservedRequests = null): array
{
    global $pocProtectedPaths, $pocMutableRoots;
    return HttpReadOnlyFilesystemGuard::observe(
        static function () use ($port, $target, $duringObservedRequests): array {
            $sockets=[];
            for($i=0;$i<2;$i++) { $socket=stream_socket_client("tcp://127.0.0.1:{$port}",$errno,$error,5); if($socket===false) throw new TestFailure('connect concurrent raw HTTP'); $sockets[]=$socket; }
            try {
                $wire="GET {$target} HTTP/1.1\r\nHost: card.example\r\nConnection: close\r\n\r\n";
                foreach($sockets as $socket){fwrite($socket,$wire);fflush($socket);stream_socket_shutdown($socket,STREAM_SHUT_WR);stream_set_timeout($socket,5);}
                if ($duringObservedRequests !== null) $duringObservedRequests();
                $responses=[]; foreach($sockets as $socket){$raw='';while(!feof($socket)){$chunk=fread($socket,65536);if($chunk===false)break;$raw.=$chunk;}$responses[]=pocParseRaw($raw);} return [$responses[0],$responses[1]];
            } finally {
                foreach ($sockets as $socket) if (is_resource($socket)) fclose($socket);
            }
        },
        $pocProtectedPaths,
        $pocMutableRoots,
    );
}

function pocParity(int $port, string $target, array $getHeaders = [], string $body = ''): array
{
    $headers = $body === '' ? $getHeaders : array_replace($getHeaders, ['Content-Length'=>(string) strlen($body)]);
    $get = pocRequest($port, 'GET', $target, $headers, $body);
    $head = pocRequest($port, 'HEAD', $target, $headers, $body);
    assertSameValue($get['status'], $head['status'], 'HEAD parity status ' . $target);
    foreach (['content-type','content-length','allow','retry-after','x-content-type-options','referrer-policy','x-frame-options','content-security-policy','permissions-policy','cross-origin-opener-policy','cache-control','host'] as $name) assertSameValue(pocHeader($get,$name),pocHeader($head,$name),'HEAD parity application header '.$name.' '.$target);
    assertSameValue('', $head['body'], 'HEAD empty body ' . $target);
    return $get;
}

function pocAssertParityResponses(array $get, array $head, string $target): void
{
    assertSameValue($get['status'], $head['status'], 'HEAD parity status ' . $target);
    foreach (['content-type','content-length','allow','retry-after','x-content-type-options','referrer-policy','x-frame-options','content-security-policy','permissions-policy','cross-origin-opener-policy','cache-control','host'] as $name) assertSameValue(pocHeader($get,$name),pocHeader($head,$name),'HEAD parity application header '.$name.' '.$target);
    assertSameValue('', $head['body'], 'HEAD empty body ' . $target);
}

function pocInheritedCleanupProbe(array $environment): array
{
    $resourceConfig = ['host'=>$environment['FMONITOR_DB_HOST'],'port'=>$environment['FMONITOR_DB_PORT'],'database'=>$environment['FMONITOR_DB_NAME'],'user'=>$environment['FMONITOR_DB_USER'],'password'=>$environment['FMONITOR_DB_PASSWORD'],'cssPath'=>$environment['FMONITOR_SHLZ_CSS_PATH']];
    $process = proc_open(['/usr/bin/env','-i','FMONITOR_TEST_RESOURCE_CONFIG='.json_encode($resourceConfig, JSON_UNESCAPED_SLASHES),PHP_BINARY,dirname(__DIR__).'/Support/real_http_entrypoint_spy.php'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__,2));
    if (!is_resource($process)) throw new TestFailure('inherited public entrypoint cleanup probe start');
    fclose($pipes[0]); $stdout=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    assertSameValue([0,''], [proc_close($process),$stderr], 'inherited public entrypoint cleanup probe process');
    $result=json_decode(trim($stdout),true); if(!is_array($result)) throw new TestFailure('inherited public entrypoint cleanup probe JSON'); return $result;
}

function pocHeader(array $response, string $name): ?string
{
    $values = $response['headers'][strtolower($name)] ?? [];
    return count($values) === 1 ? $values[0] : null;
}

function pocApplicationResponse(array $response): array
{
    unset($response['headers']['date'], $response['headers']['connection']);
    return $response;
}

function pocResponseWithoutVolatileDate(array $response): array
{
    unset($response['headers']['date']);
    return $response;
}

function pocSecurity(array $response, string $why, bool $scripted = false): void
{
    $fixed = [
        'x-content-type-options'=>'nosniff', 'referrer-policy'=>'no-referrer', 'x-frame-options'=>'DENY',
        'content-security-policy'=>$scripted
            ? "default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'"
            : "default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
        'permissions-policy'=>'camera=(), microphone=(), geolocation=()', 'cross-origin-opener-policy'=>'same-origin', 'cache-control'=>'no-store',
    ];
    foreach ($fixed as $name => $value) assertSameValue($value, pocHeader($response, $name), $why . ' ' . $name);
    foreach (['set-cookie','access-control-allow-origin','server','x-powered-by'] as $name) assertSameValue(null, pocHeader($response, $name), $why . ' forbids ' . $name);
    assertSameValue('card.example', pocHeader($response, 'host'), $why . ' exact local SAPI Host');
}

function pocError(array $response, int $status, string $body, string $why, ?string $allow = null): void
{
    assertSameValue($status, $response['status'], $why . ' status');
    assertSameValue('text/plain; charset=UTF-8', pocHeader($response, 'content-type'), $why . ' media type');
    assertSameValue((string) strlen($body), pocHeader($response, 'content-length'), $why . ' length');
    assertSameValue($body, $response['body'], $why . ' exact body');
    assertSameValue($allow, pocHeader($response, 'allow'), $why . ' Allow');
    assertSameValue($status === 503 ? '60' : null, pocHeader($response, 'retry-after'), $why . ' Retry-After');
    assertSameValue(null, pocHeader($response, 'www-authenticate'), $why . ' no authentication challenge');
    pocSecurity($response, $why);
}

function pocDocument(string $html): DOMDocument
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    assertSameValue(true, $loaded, 'success is parseable HTML');
    return $document;
}

function pocVisible(string $html): string
{
    $document = pocDocument($html);
    return preg_replace('/\s+/u', ' ', trim((string) $document->documentElement?->textContent)) ?? '';
}

function pocSuccess(array $response, array $orderedVisible, string $why): void
{
    assertSameValue(200, $response['status'], $why . ' status');
    assertSameValue('text/html; charset=UTF-8', pocHeader($response, 'content-type'), $why . ' media type');
    assertSameValue((string) strlen($response['body']), pocHeader($response, 'content-length'), $why . ' length');
    pocSecurity($response, $why, true);
    $visible = pocVisible($response['body']); $offset = 0;
    foreach ($orderedVisible as $literal) { $found = mb_strpos($visible, $literal, $offset); assertSameValue(true, $found !== false, $why . ' visible literal/order: ' . $literal); $offset = $found + mb_strlen($literal); }
    assertSameValue(1, substr_count(strtolower($response['body']), '<!doctype html>'), $why . ' one doctype');
    foreach (['<form','<input','<select','<textarea','<style','<button'] as $forbidden) assertSameValue(false, str_contains(strtolower($response['body']), $forbidden), $why . ' forbids ' . $forbidden);
    $document = pocDocument($response['body']); $xpath = new DOMXPath($document);
    assertSameValue(1, $xpath->query('//script')->length, $why . ' has exactly one external script');
    assertSameValue(1, $xpath->query("//script[@src='/pilot/assets/object-details.js' and @defer and not(@async) and normalize-space(.)='']")->length, $why . ' has exact deferred object-details script');
    assertSameValue(0, $xpath->query('//@*[starts-with(translate(name(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"on")]')->length, $why . ' forbids inline event handlers');
    assertSameValue(0, $xpath->query('//*[@href[starts-with(translate(normalize-space(.),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"javascript:")] or @src[starts-with(translate(normalize-space(.),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"javascript:")]]')->length, $why . ' forbids javascript URLs');
}

function pocStructure(array $response, string $why): void
{
    $document = pocDocument($response['body']); $xpath = new DOMXPath($document);
    foreach ([
        'html lang'=>"count(/html[@lang='ru'])", 'scoped body'=>"count(/html/body[contains(concat(' ',normalize-space(@class),' '),' shlz-scope ')])",
        'charset'=>"count(//meta[translate(@charset,'UTF-8','utf-8')='utf-8'])", 'stylesheet'=>"count(//link[@rel='stylesheet' and @href='/pilot/assets/shlz.css'])",
        'skip link'=>"count(//a[@href='#main-content'])", 'main'=>"count(//main[@id='main-content' and @tabindex='-1'])", 'one h1'=>"count(//h1)",
    ] as $label=>$query) assertSameValue(1, (int) $xpath->evaluate($query), $why . ' ' . $label);
    foreach (['Идентификация','Сроки','Распоряжение и команда','Работы','Последние события'] as $group) {
        $sections = $xpath->query("//section[./*[self::h2 or self::h3][normalize-space(.)='".$group."'] and ./dl]");
        assertSameValue(1, $sections->length, $why . ' definition-list section ' . $group);
        $definitionLists = $xpath->query('./dl', $sections->item(0));
        assertSameValue(1, $definitionLists->length, $why . ' one definition list ' . $group);
        $terms = $xpath->query('./dt', $definitionLists->item(0));
        $definitions = $xpath->query('./dd', $definitionLists->item(0));
        assertSameValue(true, $terms->length > 0, $why . ' has terms ' . $group);
        assertSameValue($terms->length, $definitions->length, $why . ' paired terms and definitions ' . $group);
    }
    $hrefs = []; foreach ($xpath->query('//*[@href]') as $node) $hrefs[] = $node->getAttribute('href'); sort($hrefs);
    assertSameValue(['#main-content','/pilot/','/pilot/assets/shlz.css'], $hrefs, $why . ' exact permitted links');
    foreach (['action','formaction','download'] as $attribute) assertSameValue(0, $xpath->query('//*[@'.$attribute.']')->length, $why . ' forbids ' . $attribute);
}

function pocCountVisible(array $response, string $literal, int $count, string $why): void
{
    assertSameValue($count, substr_count(pocVisible($response['body']), $literal), $why . ' exact cardinality ' . $literal);
}

function pocGroupVisible(array $response, string $group, array $orderedVisible, string $why): void
{
    $document = pocDocument($response['body']);
    $xpath = new DOMXPath($document);
    $sections = $xpath->query("//section[./*[self::h2 or self::h3][normalize-space(.)='" . $group . "'] and ./dl]");
    assertSameValue(1, $sections->length, $why . ' exact group');
    $visible = preg_replace('/\s+/u', ' ', trim((string) $sections->item(0)?->textContent)) ?? '';
    $offset = 0;
    foreach ($orderedVisible as $literal) {
        $found = mb_strpos($visible, $literal, $offset);
        assertSameValue(true, $found !== false, $why . ' visible literal/order: ' . $literal);
        $offset = $found + mb_strlen($literal);
    }
}

function pocGroupText(array $response, string $group, string $why): string
{
    $document = pocDocument($response['body']);
    $xpath = new DOMXPath($document);
    $literal = "'" . str_replace("'", "',\"'\",'", $group) . "'";
    $nodes = $xpath->query('//section[.//*[self::h2 or self::h3][normalize-space(.)=' . $literal . ']]');
    assertSameValue(1, $nodes?->length, $why . ' exact group cardinality');
    return preg_replace('/\s+/u', ' ', trim((string) $nodes?->item(0)?->textContent)) ?? '';
}

function pocAssertRequestResourcesReleased(array $server, mysqli $admin, string $database, string $readerUser, string $css, string $why): void
{
    $status = proc_get_status($server['process']);
    $pid = (int) ($status['pid'] ?? 0);
    assertSameValue(true, $pid > 0 && is_dir('/proc/' . $pid . '/fd'), $why . ' live public HTTP worker is observable');
    $cssDescriptors = [];
    foreach (glob('/proc/' . $pid . '/fd/*') ?: [] as $fd) {
        $target = @readlink($fd);
        if ($target === $css) $cssDescriptors[] = basename($fd);
    }
    assertSameValue([], $cssDescriptors, $why . ' releases every card CSS descriptor before response completes');
    $socketInodes = [];
    foreach (glob('/proc/' . $pid . '/fd/*') ?: [] as $fd) {
        $target = @readlink($fd);
        if (is_string($target) && preg_match('/^socket:\[(\d+)\]$/D', $target, $match) === 1) $socketInodes[$match[1]] = true;
    }
    $workerClientPorts = [];
    foreach (['/proc/net/tcp', '/proc/net/tcp6'] as $tcpTable) {
        foreach (array_slice(@file($tcpTable, FILE_IGNORE_NEW_LINES) ?: [], 1) as $line) {
            $fields = preg_split('/\s+/', trim($line));
            if (count($fields) < 10 || !isset($socketInodes[$fields[9]])) continue;
            [, $localPortHex] = array_pad(explode(':', $fields[1], 2), 2, '');
            [, $remotePortHex] = array_pad(explode(':', $fields[2], 2), 2, '');
            if (hexdec($remotePortHex) === (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306)) $workerClientPorts[] = hexdec($localPortHex);
        }
    }
    $connections = [];
    if ($workerClientPorts !== []) {
        $escapedDatabase = $admin->real_escape_string($database);
        $escapedUser = $admin->real_escape_string($readerUser);
        $hosts = implode(',', array_map(static fn(int $port): string => "'127.0.0.1:{$port}'", array_unique($workerClientPorts)));
        $connections = $admin->query("SELECT ID FROM information_schema.PROCESSLIST WHERE DB='{$escapedDatabase}' AND USER='{$escapedUser}' AND HOST IN ({$hosts}) ORDER BY ID")->fetch_all(MYSQLI_ASSOC);
    }
    assertSameValue([], $connections, $why . ' releases every card DB connection before response completes');
}

function pocSnapshot(mysqli $db): string
{
    $all = [];
    $tables = $db->query("SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
    foreach ($tables as $table) { $name = $table['TABLE_NAME']; $rows = $db->query("SELECT * FROM `{$name}`")->fetch_all(MYSQLI_ASSOC); usort($rows, static fn($a,$b) => json_encode($a) <=> json_encode($b)); $all[] = [$table, $rows]; }
    return hash('sha256', serialize($all));
}


$token = bin2hex(random_bytes(6));
$processPrefix = 'poc_';
$database = 't_poc_' . $token;
$readerUser = 'poc_' . $token;
$readerPassword = 'select-' . $token;
$userOnlyReader = 'pocu_' . $token;
$ownership=[];$ownerRoot='';$mutableRoot='';$protectedArtifactRoot='';$css='';$pilotCss='';$pocProtectedPaths=[];$pocMutableRoots=[];
    $admin = pocDb(); $db = null; $server = null; $anonymous = null; $escapeServer = null;
try {
    $ownership=TaskOwnedArtifactRoot::create('poc',$token);$ownerRoot=$ownership['root'];$mutableRoot=$ownerRoot.'/mutable';$protectedArtifactRoot=$ownerRoot.'/protected-artifact-store';$css=$mutableRoot.'/shlz.css';$pilotCss=$mutableRoot.'/pilot.css';mkdir($mutableRoot,0700);mkdir($protectedArtifactRoot,0700);file_put_contents($protectedArtifactRoot.'/sentinel','immutable-production-artifact');file_put_contents($css,file_get_contents(dirname(__DIR__,3).'/shlz-ui/packages/styles/dist/shlz.css'));file_put_contents($pilotCss,file_get_contents(dirname(__DIR__,2).'/rapid-pilot/pilot.css'));$css=(string)realpath($css);$pilotCss=(string)realpath($pilotCss);$pocProtectedPaths=[$protectedArtifactRoot,$css,$pilotCss];$pocMutableRoots=[$mutableRoot];
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");
    pocMigrate($database,$processPrefix);
    $db = pocDb($database);
    $db->query("CREATE TABLE legacy_users_roles(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(120),status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE legacy_users(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),email VARCHAR(300),role_id BIGINT UNSIGNED,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE legacy_fm_maintable(id BIGINT UNSIGNED PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),forbidden_secret VARCHAR(120)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE legacy_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,message VARCHAR(255)) ENGINE=InnoDB AUTO_INCREMENT=41");
    $db->query("CREATE TABLE legacy_ci_sessions(id VARCHAR(128) PRIMARY KEY,data BLOB NOT NULL) ENGINE=InnoDB");
    $db->query("INSERT INTO legacy_users_roles VALUES(5,'Active',1),(6,'Inactive',0)");
    $db->query("INSERT INTO legacy_users VALUES(18,'Сидоров Сергей Сергеевич','sidorov@shlz.ru',5,1),(19,'No Capability Reader','reader@shlz.ru',5,1),(20,'Inactive','inactive@shlz.ru',5,0),(21,'Inactive role','role-inactive@shlz.ru',6,1),(22,'Duplicate A','duplicate@shlz.ru',5,1),(23,'Duplicate B','duplicate@shlz.ru',5,1),(24,'Актор <script>actor-secret</script> &quot;','escape@shlz.ru',5,1)");
    $legacy = [
        [4512,'  Москва, ул. Примерная, д. 10  ',' 2 ',' 77-000123 ','2026-10-05 14:30:00','2026-12-18 09:15:00','2026-12-20','2099-01-01','FORBIDDEN-4512'],
        [4513,'Москва, ул. Вторая, д. 7','1','77-000124','2026-10-01',null,'2026-11-30','2099-01-02','FORBIDDEN-4513'],
        [4514,'Москва, ул. Третья, д. 8','3','77-000125','2026-10-02','2026-12-01',null,null,'FORBIDDEN-4514'],
        [4515,'Москва, ул. Четвёртая, д. 9','4','77-000126','2026-10-03','2026-12-02',null,null,'FORBIDDEN-4515'],
        [4516,'Москва, ул. Пятая, д. 11','5','77-000127','2026-10-04','2026-12-03',null,null,'FORBIDDEN-4516'],
        [4517,'Улица <img src=x onerror=object-secret> & "дом"','<b>7</b>','REG<&"-4517','2026-10-06','2026-12-22',null,null,'FORBIDDEN-4517'],
        [4599,'Legacy only must be hidden','9','77-HIDDEN','2026-10-01','2026-11-01',null,null,'FORBIDDEN-4599'],
        [4600,'Invalid imported','   ','77-BAD','2026-10-01','2026-11-01',null,null,'FORBIDDEN-4600'],
        [4610,'Corrupt state','10','77-C10','2026-10-01','2026-11-01',null,null,'SECRET-4610'],
        [4611,'Partial opening','11','77-C11','2026-10-01','2026-11-01',null,null,'SECRET-4611'],
        [4612,'Snapshot mismatch','12','77-C12','2026-10-01','2026-11-01',null,null,'SECRET-4612'],
        [4613,'Cancelled basis','13','77-C13','2026-10-01','2026-11-01',null,null,'SECRET-4613'],
        [4614,'Ambiguous current','14','77-C14','2026-10-01','2026-11-01',null,null,'SECRET-4614'],
        [4620,'Durable event order','20','77-C20','2026-10-01','2026-11-01',null,null,'SECRET-4620'],
        [4621,'Blank engineer name','21','77-C21','2026-10-01','2026-11-01',null,null,'SECRET-4621'],
        [4622,'Blank engineer position','22','77-C22','2026-10-01','2026-11-01',null,null,'SECRET-4622'],
        [4623,'Invalid engineer id','23','77-C23','2026-10-01','2026-11-01',null,null,'SECRET-4623'],
        [4624,'Blank installer name','24','77-C24','2026-10-01','2026-11-01',null,null,'SECRET-4624'],
        [4625,'Blank installer position','25','77-C25','2026-10-01','2026-11-01',null,null,'SECRET-4625'],
        [4626,'Invalid installer id','26','77-C26','2026-10-01','2026-11-01',null,null,'SECRET-4626'],
        [4627,'Invalid installer status','27','77-C27','2026-10-01','2026-11-01',null,null,'SECRET-4627'],
        [4628,'Blank event type','28','77-C28','2026-10-01','2026-11-01',null,null,'SECRET-4628'],
        [4629,'Invalid event timestamp','29','77-C29','2026-10-01','2026-11-01',null,null,'SECRET-4629'],
        [4630,'Invalid event actor','30','77-C30','2026-10-01','2026-11-01',null,null,'SECRET-4630'],
        [4631,'Invalid opening timestamp','31','77-C31','2026-10-01','2026-11-01',null,null,'SECRET-4631'],
        [4632,'Higher cancelled version','32','77-C32','2026-10-01','2026-11-01',null,null,'SECRET-4632'],
        [4633,'Prepared with number','33','77-C33','2026-10-01','2026-11-01',null,null,'SECRET-4633'],
        [4634,'Unnormalized registered number','34','77-C34','2026-10-01','2026-11-01',null,null,'SECRET-4634'],
        [4635,'Impossible opening calendar date','35','77-C35','2026-02-28','2026-03-31',null,null,'SECRET-4635'],
        [4636,'Impossible event calendar date','36','77-C36','2026-02-28','2026-03-31',null,null,'SECRET-4636'],
        [4637,'Valid leap calendar dates','37','77-C37','2024-02-29','2024-03-31',null,null,'SECRET-4637'],
    ];
    $statement = $db->prepare('INSERT INTO legacy_fm_maintable VALUES(?,?,?,?,?,?,?,?,?)');
    foreach ($legacy as $row) { $statement->bind_param('issssssss', ...$row); $statement->execute(); }
    $db->query("INSERT INTO legacy_logs(message) VALUES('sentinel log')"); $db->query("INSERT INTO legacy_ci_sessions VALUES('sentinel','opaque')");
    \FMonitor2\Tests\Support\LocalRbacFixture::install($db,[18=>['email'=>'sidorov@shlz.ru','permissions'=>['objects.read']],19=>['email'=>'reader@shlz.ru','permissions'=>['objects.read']],20=>['email'=>'inactive@shlz.ru','status'=>0],21=>['email'=>'role-inactive@shlz.ru','roleActive'=>0,'permissions'=>['objects.read']],24=>['email'=>'escape@shlz.ru','permissions'=>['objects.read']]],$processPrefix);\FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration::apply($db,$processPrefix);
    $db->query("INSERT INTO {$processPrefix}fm2_process_user_capabilities(user_id,capability,position_snapshot) VALUES(18,'assignment_order.prepare',NULL)");
    assertSameValue(
        [['user_id'=>'18','capability'=>'assignment_order.prepare']],
        $db->query("SELECT CAST(user_id AS CHAR) user_id,capability FROM {$processPrefix}fm2_process_user_capabilities ORDER BY user_id,capability")->fetch_all(MYSQLI_ASSOC),
        'Shared-shell fixture keeps the capable actor separate and grants the broad reader no process capability.',
    );
    $db->query("INSERT INTO {$processPrefix}fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES
        (1,4512,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (2,4513,'working','2026-10-03','2026-10-03T08:15:30+03:00',18,'2026-08-20T09:00:00+03:00','2026-10-03T08:15:30+03:00',4),
        (3,4514,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-10-02T09:00:00+03:00',2),
        (4,4515,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-10-02T15:00:00+03:00',3),
        (5,4516,'needs_assignment_change','2026-10-04','2026-10-04T08:00:00+03:00',18,'2026-08-20T09:00:00+03:00','2026-10-04T08:00:00+03:00',5),
        (6,4600,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (7,4601,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (8,4517,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-10-06T09:00:00+03:00',1),
        (10,4610,'invented_state',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (11,4611,'working','2026-10-03',NULL,18,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (12,4612,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (13,4613,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (14,4614,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (20,4620,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (21,4621,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (22,4622,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (23,4623,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (24,4624,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (25,4625,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (26,4626,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (27,4627,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (28,4628,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (29,4629,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (30,4630,'needs_assignment_order',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (31,4631,'working','2026-10-03','not-rfc3339',18,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (32,4632,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (33,4633,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (34,4634,'assignment_order_prepared',NULL,NULL,NULL,'2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),
        (35,4635,'working','2026-02-28','2026-02-30T08:00:00+03:00',18,'2026-02-01T09:00:00+03:00','2026-02-28T09:00:00+03:00',2),
        (36,4636,'needs_assignment_order',NULL,NULL,NULL,'2026-02-01T09:00:00+03:00','2026-02-28T09:00:00+03:00',1),
        (37,4637,'working','2024-02-29','2024-02-29T08:00:00+03:00',18,'2024-02-01T09:00:00+03:00','2024-02-29T09:00:00+03:00',2)");
    $orders = [
        [21,2,2,'registered','2026-10-02','19-Р',73,'Петров Пётр Петрович','Инженер строительного контроля','brigade','Москва, ул. Вторая, д. 7','1','77-000124','2026-10-01','2026-11-30'],
        [31,3,1,'prepared','2026-10-01',null,73,'Петров Пётр Петрович','Инженер строительного контроля','individual','Москва, ул. Третья, д. 8','3','77-000125','2026-10-02','2026-12-01'],
        [41,4,1,'registered','2026-10-01','12-Р',73,'Петров Пётр Петрович','Инженер строительного контроля','individual','Москва, ул. Четвёртая, д. 9','4','77-000126','2026-10-03','2026-12-02'],
        [51,5,1,'registered','2026-10-01','13-Р',73,'Петров Пётр Петрович','Инженер строительного контроля','individual','Москва, ул. Пятая, д. 11','5','77-000127','2026-10-04','2026-12-03'],
        [20,2,1,'superseded','2026-09-20','OLD-Р',73,'Старый инженер','Инженер','individual','Москва, ул. Вторая, д. 7','1','77-000124','2026-10-01','2026-11-30'],
        [81,8,1,'prepared','2026-10-06',null,73,'Инженер <svg onload=engineer-secret> & "И"','Должность <b>secret</b>','individual','Улица <img src=x onerror=object-secret> & "дом"','<b>7</b>','REG<&"-4517','2026-10-06','2026-12-22'],
        [121,12,1,'registered','2026-10-01','C12-Р',73,'Инженер','Должность','individual','DIFFERENT SNAPSHOT','12','77-C12','2026-10-01','2026-11-01'],
        [131,13,1,'cancelled','2026-10-01',null,73,'Инженер','Должность','individual','Cancelled basis','13','77-C13','2026-10-01','2026-11-01'],
        [141,14,1,'registered','2026-10-01','C14-A',73,'Инженер','Должность','individual','Ambiguous current','14','77-C14','2026-10-01','2026-11-01'],
        [142,14,2,'registered','2026-10-02','C14-B',73,'Инженер','Должность','individual','Ambiguous current','14','77-C14','2026-10-01','2026-11-01'],
        [211,21,1,'prepared','2026-10-01',null,73,'   ','Должность','individual','Blank engineer name','21','77-C21','2026-10-01','2026-11-01'],
        [221,22,1,'prepared','2026-10-01',null,73,'Инженер','   ','individual','Blank engineer position','22','77-C22','2026-10-01','2026-11-01'],
        [231,23,1,'prepared','2026-10-01',null,0,'Инженер','Должность','individual','Invalid engineer id','23','77-C23','2026-10-01','2026-11-01'],
        [241,24,1,'prepared','2026-10-01',null,73,'Инженер','Должность','individual','Blank installer name','24','77-C24','2026-10-01','2026-11-01'],
        [251,25,1,'prepared','2026-10-01',null,73,'Инженер','Должность','individual','Blank installer position','25','77-C25','2026-10-01','2026-11-01'],
        [261,26,1,'prepared','2026-10-01',null,73,'Инженер','Должность','individual','Invalid installer id','26','77-C26','2026-10-01','2026-11-01'],
        [271,27,1,'prepared','2026-10-01',null,73,'Инженер','Должность','individual','Invalid installer status','27','77-C27','2026-10-01','2026-11-01'],
        [311,31,1,'registered','2026-10-01','C31-Р',73,'Инженер','Должность','individual','Invalid opening timestamp','31','77-C31','2026-10-01','2026-11-01'],
        [321,32,1,'registered','2026-10-01','C32-Р',73,'Инженер','Должность','individual','Higher cancelled version','32','77-C32','2026-10-01','2026-11-01'],
        [322,32,2,'cancelled','2026-10-02',null,73,'Инженер новый','Должность','individual','Higher cancelled version','32','77-C32','2026-10-01','2026-11-01'],
        [331,33,1,'prepared','2026-10-01','MUST-BE-NULL',73,'Инженер','Должность','individual','Prepared with number','33','77-C33','2026-10-01','2026-11-01'],
        [341,34,1,'registered','2026-10-01','  C34-Р  ',73,'Инженер','Должность','individual','Unnormalized registered number','34','77-C34','2026-10-01','2026-11-01'],
        [351,35,1,'registered','2026-02-28','C35-Р',73,'Инженер','Должность','individual','Impossible opening calendar date','35','77-C35','2026-02-28','2026-03-31'],
        [371,37,1,'registered','2024-02-29','C37-Р',73,'Инженер','Должность','individual','Valid leap calendar dates','37','77-C37','2024-02-29','2024-03-31'],
    ];
    $order = $db->prepare("INSERT INTO {$processPrefix}fm2_assignment_orders(id,installation_case_id,version_no,kind,status,order_date,registration_number,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES(?,?,?,'initial',?,?,?,?,?,?,?,?,?,?,?,?,'2026-10-01T09:00:00+03:00',18)");
    foreach ($orders as $row) { $order->bind_param('iiisssissssssss', ...$row); $order->execute(); }
    $installer = $db->prepare("INSERT INTO {$processPrefix}fm2_order_installers VALUES(?,?,?,?,?,'2024-01-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00','2026-10-01',NULL,'add')");
    foreach ([[21,1057,'Смирнов Алексей Олегович','Монтажник','employed'],[21,1042,'Иванов Иван Иванович','Монтажник','employed'],[31,2014,'Предварительный Монтажник','Монтажник','employed'],[41,2015,'Готовый Монтажник','Монтажник','employed'],[51,2016,'Действующий Монтажник','Монтажник','employed']] as $row) { $installer->bind_param('iisss', ...$row); $installer->execute(); }
    foreach ([[81,9002,'Монтажник <script>installer-secret</script> & "Б"','Монтажник','employed'],[81,9001,'Альфа & <b>А</b>','Монтажник','employed']] as $row) { $installer->bind_param('iisss', ...$row); $installer->execute(); }
    foreach ([[241,2401,'   ','Монтажник','employed'],[251,2501,'Монтажник','   ','employed'],[261,0,'Монтажник','Должность','employed'],[271,2701,'Монтажник','Должность','invented_status'],[311,3101,'Монтажник','Должность','employed'],[321,3201,'Монтажник','Должность','employed'],[331,3301,'Монтажник','Должность','employed'],[341,3401,'Монтажник','Должность','employed'],[351,3501,'Монтажник','Должность','employed'],[371,3701,'Монтажник','Должность','employed']] as $row) { $installer->bind_param('iisss', ...$row); $installer->execute(); }
    $events = [[2,'older_hidden_event','2026-10-01T08:00:00+03:00'],[2,'assignment_order_prepared','2026-10-02T09:00:00+03:00'],[2,'assignment_order_registered','2026-10-02T15:10:00+03:00'],[2,'installation_opened','2026-10-03T08:15:30+03:00']];
    $event = $db->prepare("INSERT INTO {$processPrefix}fm2_process_events(installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES(?,?,?,18,'{\"secret\":\"EVENT-PAYLOAD-SECRET\"}')");
    foreach ($events as $row) { $event->bind_param('iss', ...$row); $event->execute(); }
    $events = [[20,'durable_oldest','2099-12-31T23:59:59+03:00'],[20,'durable_second','2026-01-01T00:00:00+03:00'],[20,'durable_third','2025-01-01T00:00:00+03:00'],[20,'durable_newest','2024-01-01T00:00:00+03:00'],[28,'   ','2026-10-01T10:00:00+03:00'],[29,'valid_event','not-rfc3339'],[36,'impossible_calendar_event','2026-02-30T08:00:00+03:00'],[37,'valid_leap_event','2024-02-29T08:30:00+03:00']];
    foreach ($events as $row) { $event->bind_param('iss', ...$row); $event->execute(); }
    $db->query("INSERT INTO {$processPrefix}fm2_process_events(installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES(30,'valid_event','2026-10-01T10:00:00+03:00',0,'{}')");

    $admin->query("CREATE USER `{$readerUser}`@`%` IDENTIFIED BY '{$readerPassword}'");
    $requiredReads = [
        'legacy_users'=>['id','name','email','role_id','status'],
        'legacy_users_roles'=>['id','status'],
        'legacy_fm_maintable'=>['id','ordadr_address','entrance','regnumber','workdatestart','workdateendadjusted','plan_finish_date'],
        $processPrefix.'fm2_installation_cases'=>['id','legacy_installation_object_id','process_state','actual_start_date','opened_at','opened_by_user_id'],
        $processPrefix.'fm2_assignment_orders'=>['id','installation_case_id','version_no','kind','status','previous_assignment_order_id','order_date','registration_number','registration_source','control_engineer_user_id','control_engineer_fio_snapshot','control_engineer_position_snapshot','organization_form','object_address_snapshot','entrance_snapshot','object_registration_number_snapshot','planned_start_date_snapshot','planned_finish_date_snapshot','prepared_at'],
        $processPrefix.'fm2_order_installers'=>['assignment_order_id','installer_tab_id','fio_snapshot','position_snapshot','employment_status_snapshot'],
        $processPrefix.'fm2_process_events'=>['id','installation_case_id','event_type','occurred_at','actor_user_id','payload_json'],
        $processPrefix.'fm2_process_user_capabilities'=>['user_id','capability','position_snapshot'],
        $processPrefix.'fm2_migration_classification_provenance'=>['legacy_object_id','category','output_kind','output_id'],
    ];
    foreach ($requiredReads as $table => $columns) {
        $quotedColumns = implode(',', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $admin->query("GRANT SELECT ({$quotedColumns}) ON `{$database}`.`{$table}` TO `{$readerUser}`@`%`");
    }
    foreach(\FMonitor2\Tests\Support\LocalRbacFixture::tables($processPrefix)as$table)$admin->query("GRANT SELECT ON `{$database}`.`{$table}` TO `{$readerUser}`@`%`");
    $admin->query("CREATE USER `{$userOnlyReader}`@`%` IDENTIFIED BY '{$readerPassword}'");
    $admin->query("GRANT SELECT ON `{$database}`.`legacy_users` TO `{$userOnlyReader}`@`%`");
    $admin->query("GRANT SELECT ON `{$database}`.`legacy_users_roles` TO `{$userOnlyReader}`@`%`");
    $privilegeRows = $admin->query("SELECT TABLE_NAME,COLUMN_NAME,PRIVILEGE_TYPE FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE=\"'{$readerUser}'@'%'\" AND TABLE_SCHEMA='{$database}' ORDER BY TABLE_NAME,COLUMN_NAME")->fetch_all(MYSQLI_ASSOC);
    $actualReads = [];
    foreach ($privilegeRows as $privilegeRow) {
        assertSameValue('SELECT', $privilegeRow['PRIVILEGE_TYPE'], 'application principal column privilege is SELECT');
        $actualReads[$privilegeRow['TABLE_NAME']][] = $privilegeRow['COLUMN_NAME'];
    }
    foreach ($actualReads as &$columns) sort($columns); unset($columns);
    foreach ($requiredReads as &$columns) sort($columns); unset($columns);
    ksort($actualReads); ksort($requiredReads);
    assertSameValue($requiredReads, $actualReads, 'application principal reads only exact required tables and columns');
    $readerProbe = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', $readerUser, $readerPassword, $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $readerProbe->set_charset('utf8mb4');
    assertSameValue('4512', (string) $readerProbe->query('SELECT id FROM legacy_fm_maintable WHERE id=4512')->fetch_row()[0], 'SELECT-only principal can read an approved column');
    try { $readerProbe->query("UPDATE {$processPrefix}fm2_installation_cases SET process_state='working' WHERE id=1"); throw new TestFailure('SELECT-only principal unexpectedly wrote process state'); } catch (mysqli_sql_exception) {}
    try { $readerProbe->query('SELECT forbidden_secret FROM legacy_fm_maintable WHERE id=4512'); throw new TestFailure('SELECT-only principal unexpectedly read forbidden legacy column'); } catch (mysqli_sql_exception) {}
    try { $readerProbe->query('SELECT message FROM legacy_logs LIMIT 1'); throw new TestFailure('SELECT-only principal unexpectedly read unrelated table'); } catch (mysqli_sql_exception) {}
    $readerProbe->close();
    $environment = ['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$database,'FMONITOR_DB_USER'=>$readerUser,'FMONITOR_DB_PASSWORD'=>$readerPassword,'FMONITOR_LEGACY_TABLE_PREFIX'=>'legacy_','FMONITOR_PROCESS_TABLE_PREFIX'=>$processPrefix,'FMONITOR_SHLZ_CSS_PATH'=>$css,'FMONITOR_PILOT_CSS_PATH'=>$pilotCss,'REMOTE_USER'=>'reader@shlz.ru','FMONITOR_AUTH_USER_ID'=>'19'];
    $before = pocSnapshot($db); $server = pocStart($environment);

    $a = pocRequest($server['port'], 'GET', '/pilot/objects/4512');
    pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, 'Example A GET request scope');
    $aHead = pocRequest($server['port'], 'HEAD', '/pilot/objects/4512');
    pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, 'Example A HEAD request scope');
    pocAssertParityResponses($a, $aHead, '/pilot/objects/4512');
    $aQuery = pocRequest($server['port'], 'GET', '/pilot/objects/4512?tab=overview');
    $ignoredBody='ignored-body-data';
    $aBody = pocRequest($server['port'], 'GET', '/pilot/objects/4512', ['Content-Length'=>(string)strlen($ignoredBody)], $ignoredBody);
    assertSameValue(pocApplicationResponse($a), pocApplicationResponse($aQuery), 'ignored query is byte-identical to canonical card GET representation and application headers');
    assertSameValue(pocApplicationResponse($a), pocApplicationResponse($aBody), 'supplied GET body is unread and byte-identical to canonical card GET representation and application headers');
    $aHeadBody = pocRequest($server['port'], 'HEAD', '/pilot/objects/4512', ['Content-Length'=>(string)strlen($ignoredBody)], $ignoredBody);
    assertSameValue(pocApplicationResponse($aHead), pocApplicationResponse($aHeadBody), 'supplied HEAD body is unread and byte-identical to canonical card HEAD application response');
    pocSuccess($a, ['Объект монтажа № 4512','Требуется распоряжение','77-000123','Москва, ул. Примерная, д. 10','Подъезд 2','Плановое начало 2026-10-05','Плановое окончание 2026-12-18','Распоряжение ещё не сформировано','Подтверждённая команда ещё не сформирована','Работы ещё не открыты','Событий пока нет'], 'Example A broad reader without capability');
    pocStructure($a, 'Example A required DOM');
    pocGroupVisible($a,'Распоряжение и команда',['Распоряжение ещё не сформировано','Подтверждённая команда ещё не сформирована'],'Example A exact empty current-basis/team consequence');
    pocGroupVisible($a,'Работы',['Работы ещё не открыты'],'Example A exact closed checklist consequence');
    foreach (['FORBIDDEN-4512','2099-01-01','assignment_order.prepare'] as $secret) assertSameValue(false, str_contains($a['body'], $secret), 'Example A excludes forbidden source/capability ' . $secret);

    $b = pocRequest($server['port'], 'GET', '/pilot/objects/4513');
    pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, 'Example B GET request scope');
    $bHead = pocRequest($server['port'], 'HEAD', '/pilot/objects/4513');
    pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, 'Example B HEAD request scope');
    pocAssertParityResponses($b, $bHead, '/pilot/objects/4513');
    pocSuccess($b, ['Объект монтажа № 4513','В работе','77-000124','Москва, ул. Вторая, д. 7','Подъезд 1','Плановое начало 2026-10-01','Плановое окончание 2026-11-30','Зарегистрировано в 1С ДО','Распоряжение № 19-Р от 2026-10-02 · версия 2','Петров Пётр Петрович','1042','Иванов Иван Иванович','1057','Смирнов Алексей Олегович','Бригадная','Чек-лист: Доступен','installation_opened','2026-10-03T08:15:30+03:00','assignment_order_registered','assignment_order_prepared'], 'Example B opened case');
    foreach (['EVENT-PAYLOAD-SECRET','older_hidden_event','FORBIDDEN-4513','OLD-Р','Старый инженер'] as $secret) assertSameValue(false, str_contains($b['body'], $secret), 'Example B excludes historical/forbidden ' . $secret);
    foreach (['1042','Иванов Иван Иванович','1057','Смирнов Алексей Олегович','installation_opened','assignment_order_registered','assignment_order_prepared'] as $once) pocCountVisible($b,$once,1,'Example B people/events');
    pocStructure($b, 'Example B required DOM');
    pocGroupVisible($b, 'Работы', ['Фактическое начало 2026-10-03','Открыто: 2026-10-03T08:15:30+03:00','Открыл пользователь: 18','Чек-лист: Доступен'], 'Example B exact opening audit in Work group');
    foreach (['Фактическое начало 2026-10-03','Открыто: 2026-10-03T08:15:30+03:00','Открыл пользователь: 18'] as $openingFact) pocCountVisible($b,$openingFact,1,'Example B opening fact belongs to exactly one semantic group');
    $team = pocGroupText($b, 'Распоряжение и команда', 'Example B current version-2 team snapshots');
    foreach (['Распоряжение № 19-Р от 2026-10-02 · версия 2','Петров Пётр Петрович','Инженер строительного контроля','1042','Иванов Иван Иванович','1057','Смирнов Алексей Олегович','Бригадная'] as $literal) assertSameValue(1, substr_count($team, $literal), 'Example B exact current team value cardinality ' . $literal);
    assertSameValue(2, substr_count($team, 'Монтажник'), 'Example B one persisted position for each of two installers');
    assertSameValue(2, substr_count($team, 'employed'), 'Example B one persisted snapshot status for each of two installers');
    foreach (['OLD-Р','Старый инженер'] as $historical) assertSameValue(false, str_contains($team, $historical), 'Example B historical version-1 value cannot leak into current team ' . $historical);
    $eventGroup = pocGroupText($b, 'Последние события', 'Example B exact newest-three event tuples');
    $eventTuples = [
        ['installation_opened','2026-10-03T08:15:30+03:00','18'],
        ['assignment_order_registered','2026-10-02T15:10:00+03:00','18'],
        ['assignment_order_prepared','2026-10-02T09:00:00+03:00','18'],
    ];
    $offset = -1;
    foreach ($eventTuples as $tuple) foreach ($tuple as $field) { $position = strpos($eventGroup, $field, $offset + 1); assertSameValue(true, $position !== false && $position > $offset, 'Example B complete event tuple fields newest-first ' . implode(' / ', $tuple)); $offset = $position; }
    foreach (['installation_opened','assignment_order_registered','assignment_order_prepared','2026-10-03T08:15:30+03:00','2026-10-02T15:10:00+03:00','2026-10-02T09:00:00+03:00'] as $field) assertSameValue(1, substr_count($eventGroup, $field), 'Example B event field cardinality ' . $field);
    assertSameValue(3, substr_count($eventGroup, '18'), 'Example B actor ID occurs once in each of exactly three event rows');
    assertSameValue(1, substr_count(pocVisible($b['body']), 'В работе'), 'Example B main status appears exactly once');
    assertSameValue(1, substr_count(pocVisible($b['body']), 'Зарегистрировано в 1С ДО'), 'Example B document status appears exactly once and only in its semantic group');
    $spoofed = pocParity($server['port'], '/pilot/objects/4512', ['X-Remote-User'=>'spoof@example.test','Remote-User'=>'spoof2@example.test']);
    pocSuccess($spoofed, ['No Capability Reader','Объект монтажа № 4512'], 'spoof headers ignored on otherwise successful card');
    foreach (['spoof@example.test','spoof2@example.test'] as $spoofPrincipal) assertSameValue(false, str_contains(pocVisible($spoofed['body']), $spoofPrincipal), 'spoof principal absent from successful card');
    foreach ([
        4514=>['status'=>'Распоряжение подготовлено','team'=>['Ожидается номер 1С ДО','Петров Пётр Петрович','Инженер строительного контроля','2014','Предварительный Монтажник'],'work'=>['Работы ещё не открыты']],
        4515=>['status'=>'Готов к открытию','team'=>['Распоряжение № 12-Р от 2026-10-01 · версия 1','Зарегистрировано в 1С ДО','Петров Пётр Петрович','2015','Готовый Монтажник'],'work'=>['Работы ещё не открыты']],
        4516=>['status'=>'Требуется изменение','team'=>['Распоряжение № 13-Р от 2026-10-01 · версия 1','Зарегистрировано в 1С ДО','Петров Пётр Петрович','2016','Действующий Монтажник'],'work'=>['Фактическое начало 2026-10-04','Открыто: 2026-10-04T08:00:00+03:00','Чек-лист: Доступен']],
    ] as $id=>$expected) {
        $state=pocRequest($server['port'],'GET','/pilot/objects/'.$id);
        pocSuccess($state,[$expected['status']],'state '.$id);
        pocGroupVisible($state,'Распоряжение и команда',$expected['team'],'state '.$id.' exact current-basis/team consequence');
        pocGroupVisible($state,'Работы',$expected['work'],'state '.$id.' exact checklist/opening consequence');
        if ($id === 4514) assertSameValue(false,str_contains(pocGroupText($state,'Распоряжение и команда','prepared preliminary team'),'Зарегистрировано в 1С ДО'),'prepared state has no registered basis');
        if ($id === 4515) assertSameValue(false,str_contains(pocGroupText($state,'Работы','ready closed checklist'),'Чек-лист: Доступен'),'ready state keeps checklist closed');
    }

    $escapeServer = pocStart(array_replace($environment, ['REMOTE_USER'=>'escape@shlz.ru']));
    $escaped = pocParity($escapeServer['port'], '/pilot/objects/4517');
    pocSuccess($escaped,['Актор <script>actor-secret</script> &quot;','REG<&"-4517','Улица <img src=x onerror=object-secret> & "дом"','Подъезд <b>7</b>','Инженер <svg onload=engineer-secret> & "И"','9001','Альфа & <b>А</b>','9002','Монтажник <script>installer-secret</script> & "Б"'],'adversarial values remain exact visible text');
    foreach (['<script>actor-secret</script>','<img src=x onerror=object-secret>','<svg onload=engineer-secret>','<script>installer-secret</script>','<b>А</b>'] as $rawMarkup) assertSameValue(false,str_contains($escaped['body'],$rawMarkup),'adversarial markup escaped '.$rawMarkup);

    foreach (['/pilot/objects/0','/pilot/objects/01','/pilot/objects/-1','/pilot/objects/+1','/pilot/objects/9223372036854775808','/pilot/objects/4512/','/pilot/objects//4512','/pilot/objects/4512/extra','/pilot/objects/%34%35%31%32','/pilot/objects/4512%2fextra','/pilot/objects/%204512','/pilot/objects/4512%20','/pilot/objects/%D9%A1','/pilot/objects/4512%5Cextra','/pilot/objects/./4512','/pilot/objects/4512/..','/pilot/objects/4512%00','/pilot/objects/%FF'] as $path) pocError(pocParity($server['port'],$path),404,"Not found.\n",'invalid route '.$path);
    $opaqueBody='opaque-request-body';
    foreach (['POST','PUT','PATCH','DELETE','OPTIONS','TRACE','CONNECT'] as $method) pocError(pocRequest($server['port'],$method,'/pilot/objects/4512',['Content-Length'=>(string)strlen($opaqueBody)],$opaqueBody),405,"Method not allowed.\n",'method '.$method.' exact catch-all/body-not-read','GET, HEAD');
    $availability = [];
    foreach ([4999=>'unknown',4599=>'legacy non-imported',4600=>'invalid approved data',4601=>'dangling imported',9000001=>'padded unknown A',9000002=>'padded unknown B',9000003=>'padded unknown C'] as $id=>$label) { $response=pocParity($server['port'],'/pilot/objects/'.$id); pocError($response,404,"Not found.\n",$label); pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, $label . ' failure request scope'); $availability[]=serialize(pocApplicationResponse($response)); }
    assertSameValue(1,count(array_unique($availability)),'padded availability population has indistinguishable bytes');
    for($sample=0;$sample<3;$sample++) assertSameValue($availability[0],serialize(pocApplicationResponse(pocRequest($server['port'],'GET','/pilot/objects/4999'))),'repeated unknown nondisclosure sample '.$sample);
    foreach ([4610=>'unknown process state',4611=>'opening gate mismatch',4612=>'immutable snapshot mismatch',4613=>'cancelled current basis',4614=>'multiple current registered versions'] as $id=>$label) { pocError(pocParity($server['port'],'/pilot/objects/'.$id),503,"Service unavailable.\n",$label); pocAssertRequestResourcesReleased($server, $admin, $database, $readerUser, $css, $label . ' failure request scope'); }
    $durableEvents = pocRequest($server['port'],'GET','/pilot/objects/4620');
    pocSuccess($durableEvents,['durable_newest','2024-01-01T00:00:00+03:00','durable_third','2025-01-01T00:00:00+03:00','durable_second','2026-01-01T00:00:00+03:00'],'durable append order is newest event id first despite timestamps');
    assertSameValue(false,str_contains($durableEvents['body'],'durable_oldest'),'durable event limit excludes oldest id rather than newest timestamp');
    foreach ([4621=>'blank engineer name',4622=>'blank engineer position',4623=>'nonpositive engineer id',4624=>'blank installer name',4625=>'blank installer position',4626=>'nonpositive installer id',4627=>'unexpected installer snapshot status',4628=>'blank event type',4629=>'invalid event RFC3339 timestamp',4630=>'nonpositive event actor id',4631=>'invalid opening RFC3339 timestamp',4632=>'higher cancelled version cannot fall back to older registered basis',4633=>'prepared order must have null registration number',4634=>'registered number must already be normalized'] as $id=>$label) pocError(pocParity($server['port'],'/pilot/objects/'.$id),503,"Service unavailable.\n",$label);
    $impossibleCalendarResponses = [];
    foreach ([4635=>'impossible calendar opening RFC3339 timestamp',4636=>'impossible calendar event RFC3339 timestamp'] as $id=>$label) $impossibleCalendarResponses[$label] = pocParity($server['port'],'/pilot/objects/'.$id);
    $validLeap = pocParity($server['port'],'/pilot/objects/4637');
    pocSuccess($validLeap,['Объект монтажа № 4637','В работе','Фактическое начало 2024-02-29','Открыто: 2024-02-29T08:00:00+03:00','valid_leap_event','2024-02-29T08:30:00+03:00'],'valid leap-day opening and event timestamps remain successful');
    assertSameValue([503,503],array_column(array_values($impossibleCalendarResponses),'status'),'both impossible calendar RFC3339 projections fail closed');
    foreach ($impossibleCalendarResponses as $label=>$response) pocError($response,503,"Service unavailable.\n",$label);
    foreach (['Фактическое начало 2026-10-03','Открыто: 2026-10-03T08:15:30+03:00','Открыл пользователь: 18'] as $openingFact) pocCountVisible($b, $openingFact, 1, 'Example B opening facts occur only in Work group');
    $invalidPrefix = pocStart(array_replace($environment,['FMONITOR_LEGACY_TABLE_PREFIX'=>'legacy_;DROP_TABLE_']));
    try { pocError(pocParity($invalidPrefix['port'],'/pilot/objects/4512'),503,"Service unavailable.\n",'invalid legacy table prefix fails closed before identifier use'); } finally { pocStop($invalidPrefix); }
    $repeatA=pocRequest($server['port'],'GET','/pilot/objects/4513'); $repeatB=pocRequest($server['port'],'GET','/pilot/objects/4513'); assertSameValue(pocResponseWithoutVolatileDate($repeatA),pocResponseWithoutVolatileDate($repeatB),'repeated committed reads deterministic except volatile SAPI Date');
    try { pocConcurrentGets($server['port'],'/pilot/objects/4513',static fn():int=>file_put_contents($protectedArtifactRoot.'/sentinel','forbidden-concurrent-write',LOCK_EX)); throw new TestFailure('Both concurrent raw HTTP reads must execute inside the filesystem guard.'); } catch (TestFailure $failure) { assertSameValue('Protected HTTP read-only path changed.',$failure->getMessage(),'concurrent raw HTTP guard sensitivity has exact redacted verdict'); } finally { file_put_contents($protectedArtifactRoot.'/sentinel','immutable-production-artifact',LOCK_EX); }
    $parallel=pocConcurrentGets($server['port'],'/pilot/objects/4513'); assertSameValue(pocResponseWithoutVolatileDate($parallel[0]),pocResponseWithoutVolatileDate($parallel[1]),'concurrent raw HTTP reads deterministic except volatile SAPI Date');
    assertSameValue($before, pocSnapshot($db), 'GET HEAD 404 and 405 preserve every DB row and AUTO_INCREMENT');

    $cleanupProbe=pocInheritedCleanupProbe($environment);
    foreach (['unknown','method','unexpected','closeFailure','closeReporterThrow'] as $case) assertSameValue(1,$cleanupProbe[$case]['closeCount']??null,'inherited entrypoint closes exactly once '.$case);
    assertSameValue(['correlation','close','report'],$cleanupProbe['closeFailure']['calls'],'throwing close still follows exact close/report sequence without retry');
    assertSameValue(['correlation','close','report'],$cleanupProbe['closeReporterThrow']['calls'],'throwing close and reporter are each attempted without retry');

    pocStop($server); $server = null; $anonymous = pocStart(array_diff_key($environment, ['REMOTE_USER'=>true]));
    pocError(pocParity($anonymous['port'],'/pilot/objects/4512'),401,"Authentication required.\n",'missing identity precedes CSS DB and object reads');
    pocStop($anonymous); $anonymous = null;
    foreach (['inactive@shlz.ru','role-inactive@shlz.ru','duplicate@shlz.ru'] as $principal) { $forbidden = pocStart(array_replace($environment, ['REMOTE_USER'=>$principal])); try { pocError(pocParity($forbidden['port'],'/pilot/objects/4512'),403,"Access denied.\n",'forbidden exact identity '.$principal); } finally { pocStop($forbidden); } }
    $malformed = pocStart(array_replace($environment, ['REMOTE_USER'=>' reader@shlz.ru ']));
    try { pocError(pocRequest($malformed['port'],'GET','/pilot/objects/4512'),401,"Authentication required.\n",'malformed identity'); } finally { pocStop($malformed); }
    $brokenCss = array_replace($environment, ['FMONITOR_SHLZ_CSS_PATH'=>dirname($css).'/missing-shlz.css']);
    $cssFailure = pocStart($brokenCss);
    try { pocError(pocRequest($cssFailure['port'],'GET','/pilot/objects/4512'),503,"Service unavailable.\n",'valid identity evaluates CSS before user/object DB'); } finally { pocStop($cssFailure); }
    $cssAnonymous = pocStart(array_diff_key($brokenCss, ['REMOTE_USER'=>true]));
    try { pocError(pocRequest($cssAnonymous['port'],'GET','/pilot/objects/4512'),401,"Authentication required.\n",'missing identity precedes broken CSS'); } finally { pocStop($cssAnonymous); }
    $brokenDb = array_replace($environment,['FMONITOR_DB_PASSWORD'=>'deliberately-wrong-secret']);
    $dbFailure = pocStart($brokenDb); try { $failure=pocParity($dbFailure['port'],'/pilot/objects/4512'); pocError($failure,503,"Service unavailable.\n",'user lookup DB failure redacted'); foreach ([$readerUser,$database,'deliberately-wrong-secret','4512','mysqli','legacy_users'] as $secret) assertSameValue(false,str_contains($failure['body'],$secret),'503 redacts '.$secret); } finally { pocStop($dbFailure); }
    $objectDbFailureEnvironment=array_replace($environment,['FMONITOR_DB_USER'=>$userOnlyReader]); $objectDbFailure=pocStart($objectDbFailureEnvironment); try { pocError(pocParity($objectDbFailure['port'],'/pilot/objects/4512'),503,"Service unavailable.\n",'object lookup DB failure after successful user resolution'); } finally { pocStop($objectDbFailure); }
    $dbAnonymous = pocStart(array_diff_key($brokenDb,['REMOTE_USER'=>true])); try { pocError(pocParity($dbAnonymous['port'],'/pilot/objects/4512'),401,"Authentication required.\n",'identity precedes broken DB'); } finally { pocStop($dbAnonymous); }
    $invalidRouteBrokenEverything = pocStart(array_replace($brokenDb,['FMONITOR_SHLZ_CSS_PATH'=>dirname($css).'/also-missing.css','REMOTE_USER'=>' malformed '])); try { pocError(pocParity($invalidRouteBrokenEverything['port'],'/pilot/objects/%FF'),404,"Not found.\n",'route grammar precedes identity CSS config and DB'); } finally { pocStop($invalidRouteBrokenEverything); }
    assertSameValue($before, pocSnapshot($db), 'success HEAD 404 and 403 are observationally read-only');
    echo "PASS: PILOT-OBJECT-CARD-001 public HTTP card\n";
} finally {
    pocStop($server); pocStop($anonymous); pocStop($escapeServer);
    if ($db instanceof mysqli) $db->close();
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->query("DROP USER IF EXISTS `{$readerUser}`@`%`");
    $admin->query("DROP USER IF EXISTS `{$userOnlyReader}`@`%`"); $admin->close();
    if($ownership!==[])TaskOwnedArtifactRoot::cleanup($ownership,'poc',$token);
}
