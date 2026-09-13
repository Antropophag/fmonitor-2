<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$composeFile = $root . '/deploy/runtime/compose.yaml';
assertSameValue(true, is_file($composeFile), 'INTENTIONAL_RED: production Compose definition exists');
exec('docker info >/dev/null 2>&1', $ignored, $dockerStatus);
if ($dockerStatus !== 0) {
    throw new TestFailure('SETUP_FAILURE: Docker daemon unavailable for production runtime smoke');
}

$token = substr(bin2hex(random_bytes(6)), 0, 12);
$project = 'fm2rt' . $token;
$port = random_int(20000, 40000);
$runtimePassword = 'runtime_' . $token;
$migrationPassword = 'migration_' . $token;
$runtimeImage = 'fmonitor2-runtime:' . $project;
$processEnvironment = getenv();
if (!is_array($processEnvironment)) {
    throw new TestFailure('SETUP_FAILURE: process environment unavailable');
}
$environment = array_replace($processEnvironment, [
    'COMPOSE_PROJECT_NAME' => $project,
    'FMONITOR_HTTP_PORT' => (string) $port,
    'FMONITOR_RUNTIME_IMAGE' => $runtimeImage,
    'FMONITOR_DB_NAME' => 'fmonitor_runtime',
    'FMONITOR_DB_USER' => 'fmonitor_runtime',
    'FMONITOR_DB_PASSWORD' => $runtimePassword,
    'FMONITOR_MIGRATION_DB_USER' => 'root',
    'FMONITOR_MIGRATION_DB_PASSWORD' => $migrationPassword,
    'FMONITOR_PROCESS_TABLE_PREFIX' => 'runtime_',
    'FMONITOR_LEGACY_TABLE_PREFIX' => 'runtime_',
    'FMONITOR_SESSION_INSTANCE' => 'production',
    'FMONITOR_YII_COOKIE_VALIDATION_KEY' => 'compose-test-cookie-key-' . $token,
    'FMONITOR_YII_IDENTITY_KEY' => 'compose-test-identity-key-' . $token,
    'FMONITOR_TRUSTED_REQUEST_HOST' => '127.0.0.1:' . $port,
    'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'http',
]);
$base = ['docker', 'compose', '--progress', 'quiet', '--project-name', $project, '--file', $composeFile];

/** @return array{exit:int,stdout:string,stderr:string} */
function runtimeComposeCommand(array $command, array $environment, string $cwd, int $timeoutSeconds = 180): array
{
    $pipes = [];
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, $environment);
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: cannot start ' . implode(' ', $command));
    }
    foreach ([1, 2] as $descriptor) {
        stream_set_blocking($pipes[$descriptor], false);
    }
    $stdout = '';
    $stderr = '';
    $deadline = microtime(true) + $timeoutSeconds;
    do {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) >= $deadline) {
            proc_terminate($process, 15);
            usleep(200000);
            proc_terminate($process, 9);
            throw new TestFailure('SETUP_FAILURE: command timeout: ' . implode(' ', $command));
        }
        usleep(50000);
    } while (true);
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function assertRuntimeCompose(array $result, string $operation): void
{
    assertSameValue(0, $result['exit'], "{$operation} stderr=" . trim($result['stderr']) . ' stdout=' . trim($result['stdout']));
}

/** @return array{status:int,body:string,headers:list<string>} */
function runtimeHttp(int $port, string $path, ?string $host = null, string $method = 'GET', ?string $body = null, ?string $cookie = null): array
{
    $headers = ['Connection: close'];
    if ($host !== null) {
        $headers[] = 'Host: ' . $host;
    }
    if ($cookie !== null) {
        $headers[] = 'Cookie: ' . $cookie;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 8, 'method' => $method, 'content' => $body ?? '', 'header' => implode("\r\n", $headers)]]);
    $body = file_get_contents("http://127.0.0.1:{$port}{$path}", false, $context);
    $responseHeaders = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
    preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $match);
    return ['status' => (int) ($match[1] ?? 0), 'body' => is_string($body) ? $body : '', 'headers' => $responseHeaders];
}

try {
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'config', '--quiet'], $environment, $root), 'production Compose config');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'build'], $environment, $root, 300), 'production runtime exact-source image build');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'up', '--detach', '--wait', 'db'], $environment, $root), 'database startup');
    $grantSql = "CREATE USER 'fmonitor_runtime'@'%' IDENTIFIED BY '{$runtimePassword}'; GRANT SELECT,INSERT,UPDATE,DELETE ON fmonitor_runtime.* TO 'fmonitor_runtime'@'%'; FLUSH PRIVILEGES;";
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'exec', '-T', 'db', 'mariadb', '-uroot', '-p' . $migrationPassword, '-e', $grantSql], $environment, $root), 'DML-only runtime principal provisioning');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', 'prepare'], $environment, $root), 'runtime secret/storage preparation');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--entrypoint', 'sh', 'prepare', '-c', 'for p in /home/fmonitor/.local/state/fmonitor2 /home/fmonitor/.local/state/fmonitor2/artifacts /home/fmonitor/.local/state/fmonitor2/log /run/fmonitor-secrets; do test "$(stat -c %u:%g $p)" = 10001:10001 && test "$(stat -c %a $p)" = 700 || exit 1; done; for p in /run/fmonitor-secrets/database-password /home/fmonitor/.local/state/fmonitor2/log/original-safe.jsonl; do test "$(stat -c %u:%g $p)" = 10001:10001 && test "$(stat -c %a $p)" = 600 || exit 1; done'], $environment, $root), 'all prepared production storage ownership and modes');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--user', '0', '--entrypoint', 'chown', 'prepare', '0:0', '/run/fmonitor-secrets/database-password'], $environment, $root), 'wrong-owner fixture');
    $wrongOwner = runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', 'prepare'], $environment, $root);
    assertSameValue([70, "{\"ok\":false,\"reason\":\"RUNTIME_STORAGE_INVALID\"}\n", ''], [$wrongOwner['exit'], $wrongOwner['stdout'], trim($wrongOwner['stderr'])], 'prepare rejects a credential owned by another identity');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--user', '0', '--entrypoint', 'sh', 'prepare', '-c', 'test "$(stat -c %u:%g /run/fmonitor-secrets/database-password)" = 0:0 && test "$(cat /run/fmonitor-secrets/database-password)" = "$FMONITOR_DB_PASSWORD"'], $environment, $root), 'wrong-owner failure preserves credential owner and bytes');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--user', '0', '--entrypoint', 'chown', 'prepare', '10001:10001', '/run/fmonitor-secrets/database-password'], $environment, $root), 'wrong-owner fixture restoration');
    $schemaCountBefore = runtimeComposeCommand([...$base, 'exec', '-T', 'db', 'mariadb', '-N', '-uroot', '-p' . $migrationPassword, '-e', "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='fmonitor_runtime'"], $environment, $root);
    assertRuntimeCompose($schemaCountBefore, 'schema-missing fixture count');
    $missingSchema = runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--entrypoint', 'php', 'prepare', 'bin/fmonitor2-runtime-check.php'], $environment, $root);
    assertSameValue([70, "{\"ok\":false,\"reason\":\"SCHEMA_NOT_READY\"}\n", ''], [$missingSchema['exit'], $missingSchema['stdout'], trim($missingSchema['stderr'])], 'readiness fails closed on missing schema without repair');
    $schemaCountAfter = runtimeComposeCommand([...$base, 'exec', '-T', 'db', 'mariadb', '-N', '-uroot', '-p' . $migrationPassword, '-e', "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='fmonitor_runtime'"], $environment, $root);
    assertRuntimeCompose($schemaCountAfter, 'schema-missing post-check count');
    assertSameValue(trim($schemaCountBefore['stdout']), trim($schemaCountAfter['stdout']), 'schema-missing readiness creates no tables');
    $ddlDenied = runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', '--entrypoint', 'php', 'prepare', '-r', '$db=new mysqli(getenv("FMONITOR_DB_HOST"),getenv("FMONITOR_DB_USER"),getenv("FMONITOR_DB_PASSWORD"),getenv("FMONITOR_DB_NAME"),(int)getenv("FMONITOR_DB_PORT"));try{$db->query("CREATE TABLE forbidden_runtime_ddl(id INT)");exit(1);}catch(mysqli_sql_exception){echo "DDL_DENIED\n";}'], $environment, $root);
    assertSameValue([0, "DDL_DENIED\n", ''], [$ddlDenied['exit'], $ddlDenied['stdout'], $ddlDenied['stderr']], 'runtime principal cannot execute DDL');
    assertRuntimeCompose(runtimeComposeCommand([...$base, '--profile', 'deployment', 'run', '--rm', 'migrate'], $environment, $root), 'separate canonical migration');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'up', '--detach', '--wait', 'php', 'web'], $environment, $root), 'nginx and PHP-FPM startup');

    assertSameValue(200, runtimeHttp($port, '/health/live')['status'], 'nginx reaches PHP-FPM liveness endpoint');
    assertSameValue(200, runtimeHttp($port, '/health/ready')['status'], 'readiness confirms DB, schema and writable storage');
    $login = runtimeHttp($port, '/pilot/login');
    assertSameValue(200, $login['status'], 'Yii runtime serves the login page through nginx and FPM');
    assertSameValue(true, str_contains($login['body'], 'csrf'), 'login HTML contains the real CSRF field');
    $setCookie = implode("\n", array_values(array_filter($login['headers'], static fn (string $header): bool => str_starts_with(strtolower($header), 'set-cookie:'))));
    assertSameValue(1, preg_match('/Set-Cookie:\s*([^;]+)/i', $setCookie, $cookieMatch), 'login publishes an opaque session cookie');
    $cookie = $cookieMatch[1];
    $continued = runtimeHttp($port, '/pilot/login', null, 'GET', null, $cookie);
    assertSameValue(200, $continued['status'], 'login session cookie remains readable on the next request');
    assertSameValue(400, runtimeHttp($port, '/pilot/login', null, 'POST', http_build_query(['email' => 'nobody@shlz.ru', '_csrf' => str_repeat('0', 64)]), $cookie)['status'], 'Yii login rejects a wrong CSRF token inside a valid session');
    $asset = runtimeHttp($port, '/pilot/assets/pilot.css');
    assertSameValue(200, $asset['status'], 'composite router/static server preserves pilot assets');
    assertSameValue(true, strlen($asset['body']) > 100, 'pilot stylesheet is nonempty');
    assertSameValue(404, runtimeHttp($port, '/pilot/definitely-not-a-runtime-route')['status'], 'unknown pilot route remains 404');
    assertSameValue(400, runtimeHttp($port, '/pilot/login', 'untrusted.example.test')['status'], 'untrusted Host fails closed');

    assertRuntimeCompose(runtimeComposeCommand([...$base, 'exec', '-T', 'php', 'sh', '-c', 'test "$(id -u)" = 10001 && touch /home/fmonitor/.local/state/fmonitor2/runtime-restart-sentinel'], $environment, $root), 'runtime UID and persistent state write');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'restart', 'php', 'web'], $environment, $root), 'runtime process restart');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'exec', '-T', 'php', 'test', '-f', '/home/fmonitor/.local/state/fmonitor2/runtime-restart-sentinel'], $environment, $root), 'state survives process restart');
    // Compose restart returns before nginx necessarily accepts HTTP connections.
    $restartDeadline = microtime(true) + 30;
    do {
        $restartedReadiness = @runtimeHttp($port, '/health/ready');
        if ($restartedReadiness['status'] === 200) break;
        usleep(100000);
    } while (microtime(true) < $restartDeadline);
    assertSameValue(200, $restartedReadiness['status'], 'readiness recovers after process restart without migration/bootstrap');

    $nginxFixture=tempnam(sys_get_temp_dir(),'fm2-nginx-drain-');if(!is_string($nginxFixture))throw new TestFailure('SETUP_FAILURE: nginx drain fixture');
    $nginxSource=file_get_contents($root.'/deploy/runtime/nginx.conf');$fixtureLocation="        location = /__runtime_nginx_drain {\n            include /etc/nginx/fastcgi_params;\n            fastcgi_param SCRIPT_FILENAME /tmp/runtime-nginx-drain.php;\n            fastcgi_param SCRIPT_NAME /__runtime_nginx_drain;\n            fastcgi_param HTTP_HOST \$http_host;\n            fastcgi_pass php:9000;\n        }\n\n";$nginxFixtureSource=str_replace(['pid /tmp/nginx.pid;','listen 8080;','        location / {'],['pid /tmp/nginx-drain.pid;','listen 8081;',$fixtureLocation.'        location / {'],(string)$nginxSource);file_put_contents($nginxFixture,$nginxFixtureSource,LOCK_EX);chmod($nginxFixture,0600);
    $nginxDrainBytes='<?php file_put_contents("/home/fmonitor/.local/state/fmonitor2/nginx-drain-entered","entered",LOCK_EX); $started=hrtime(true);$deadline=$started+2000000000;do{usleep(50000);}while(hrtime(true)<$deadline);echo "NGINX_DRAIN_COMPLETE\n";';$nginxDrainInstaller='file_put_contents("/tmp/runtime-nginx-drain.php",base64_decode("'.base64_encode($nginxDrainBytes).'"));';
    assertRuntimeCompose(runtimeComposeCommand([...$base,'exec','-T','php','php','-r',$nginxDrainInstaller],$environment,$root),'test-only nginx drain handler install');assertRuntimeCompose(runtimeComposeCommand(['docker','cp',$nginxFixture,$project.'-web-1:/tmp/nginx-drain.conf'],$environment,$root),'test-only nginx config copy');assertRuntimeCompose(runtimeComposeCommand([...$base,'exec','-T','--user','0','web','chown','10001:10001','/tmp/nginx-drain.conf'],$environment,$root),'test-only nginx config ownership');assertRuntimeCompose(runtimeComposeCommand([...$base,'exec','-T','web','nginx','-t','-c','/tmp/nginx-drain.conf'],$environment,$root),'test-only nginx config syntax');
    $fixturePipes=[];$fixtureNginx=proc_open([...$base,'exec','-T','web','nginx','-c','/tmp/nginx-drain.conf','-g','daemon off;'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$fixturePipes,$root,$environment);if(!is_resource($fixtureNginx))throw new TestFailure('SETUP_FAILURE: fixture nginx start');$fixtureDeadline=microtime(true)+5;do{$fixtureReady=runtimeComposeCommand([...$base,'exec','-T','web','test','-f','/tmp/nginx-drain.pid'],$environment,$root,5);if($fixtureReady['exit']===0)break;usleep(50000);}while(microtime(true)<$fixtureDeadline);assertSameValue(0,$fixtureReady['exit'],'fixture nginx pid readiness');
    $nginxRequestPipes=[];$nginxRequest=proc_open([...$base,'exec','-T','web','curl','--fail','--silent','--show-error','--header','Host: '.$environment['FMONITOR_TRUSTED_REQUEST_HOST'],'http://127.0.0.1:8081/__runtime_nginx_drain'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$nginxRequestPipes,$root,$environment);if(!is_resource($nginxRequest))throw new TestFailure('SETUP_FAILURE: nginx active request');$nginxEnteredDeadline=microtime(true)+5;do{$nginxEntered=runtimeComposeCommand([...$base,'exec','-T','php','test','-f','/home/fmonitor/.local/state/fmonitor2/nginx-drain-entered'],$environment,$root,5);if($nginxEntered['exit']===0)break;usleep(50000);}while(microtime(true)<$nginxEnteredDeadline);assertSameValue(0,$nginxEntered['exit'],'nginx request reached FPM handler before stop');$nginxStopStarted=hrtime(true);assertRuntimeCompose(runtimeComposeCommand([...$base,'exec','-T','web','sh','-c','kill -QUIT "$(cat /tmp/nginx-drain.pid)"'],$environment,$root),'fixture nginx graceful signal');$newTraffic=runtimeComposeCommand([...$base,'exec','-T','web','curl','--max-time','1','--silent','http://127.0.0.1:8081/health/live'],$environment,$root,5);assertSameValue([true,true],[$newTraffic['exit']!==0,proc_get_status($nginxRequest)['running']],'nginx rejects new traffic while active upstream response is still draining');
    $nginxBody=stream_get_contents($nginxRequestPipes[1]);$nginxRequestError=stream_get_contents($nginxRequestPipes[2]);fclose($nginxRequestPipes[1]);fclose($nginxRequestPipes[2]);$nginxRequestExit=proc_close($nginxRequest);foreach([1,2]as$fd){stream_get_contents($fixturePipes[$fd]);fclose($fixturePipes[$fd]);}$fixtureExit=proc_close($fixtureNginx);assertSameValue([0,"NGINX_DRAIN_COMPLETE\n",'',0,true],[$nginxRequestExit,$nginxBody,$nginxRequestError,$fixtureExit,hrtime(true)-$nginxStopStarted<60_000_000_000],'nginx graceful stop completes active FPM response within sixty seconds');$fixtureGone=runtimeComposeCommand([...$base,'exec','-T','web','test','!','-f','/tmp/nginx-drain.pid'],$environment,$root);assertRuntimeCompose($fixtureGone,'fixture nginx pid removed after graceful stop');

    $drainBytes = '<?php file_put_contents("/home/fmonitor/.local/state/fmonitor2/drain-entered", "entered", LOCK_EX); $started=hrtime(true); $deadline=$started+1500000000; do { usleep(50000); } while (hrtime(true)<$deadline); echo "DRAIN_COMPLETE ".(int)((hrtime(true)-$started)/1000000)."ms\n";';
    $drainInstaller = 'file_put_contents("/tmp/runtime-drain.php", base64_decode("' . base64_encode($drainBytes) . '"));';
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'exec', '-T', 'php', 'php', '-r', $drainInstaller], $environment, $root), 'test-only slow FPM script installation');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'exec', '-T', 'php', 'php', '-l', '/tmp/runtime-drain.php'], $environment, $root), 'test-only slow FPM script syntax');
    $activePipes = [];
    $active = proc_open([...$base, 'exec', '-T', 'web', 'sh', '-c', 'SCRIPT_FILENAME=/tmp/runtime-drain.php SCRIPT_NAME=/runtime-drain REQUEST_METHOD=GET REQUEST_URI=/runtime-drain SERVER_PROTOCOL=HTTP/1.1 HTTP_HOST="$FMONITOR_TRUSTED_REQUEST_HOST" cgi-fcgi -bind -connect php:9000'], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $activePipes, $root, $environment);
    if (!is_resource($active)) {
        throw new TestFailure('SETUP_FAILURE: active FPM request start');
    }
    $enteredDeadline = microtime(true) + 5;
    do {
        $entered = runtimeComposeCommand([...$base, 'exec', '-T', 'php', 'test', '-f', '/home/fmonitor/.local/state/fmonitor2/drain-entered'], $environment, $root, 5);
        if ($entered['exit'] === 0) {
            break;
        }
        usleep(50000);
    } while (microtime(true) < $enteredDeadline);
    assertSameValue(0, $entered['exit'], 'active request entered the test-only FPM handler before SIGQUIT');
    $stopStarted = hrtime(true);
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'stop', '--timeout', '60', 'php'], $environment, $root, 65), 'configured graceful FPM stop');
    assertSameValue(true, hrtime(true) - $stopStarted < 60_000_000_000, 'active request drains inside the configured sixty-second grace period');
    $activeOut = stream_get_contents($activePipes[1]);
    $activeErr = stream_get_contents($activePipes[2]);
    fclose($activePipes[1]);
    fclose($activePipes[2]);
    $activeExit = proc_close($active);
    preg_match('/DRAIN_COMPLETE ([0-9]+)ms/', $activeOut, $drainMatch);
    assertSameValue([0, true, ''], [$activeExit, isset($drainMatch[1]) && (int) $drainMatch[1] >= 1300, $activeErr], 'SIGQUIT drains and returns the complete active FPM response after sustained work');
    $stopped = runtimeComposeCommand([...$base, 'ps', '--status', 'running', '--services'], $environment, $root);
    assertRuntimeCompose($stopped, 'post-drain process inventory');
    assertSameValue(false, in_array('php', preg_split('/\s+/', trim($stopped['stdout'])) ?: [], true), 'gracefully stopped FPM has no running container child');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'up', '--detach', '--wait', 'php', 'web'], $environment, $root), 'FPM restart after graceful drain');

    assertRuntimeCompose(runtimeComposeCommand([...$base, 'stop', 'db'], $environment, $root), 'database outage fixture');
    assertSameValue(200, runtimeHttp($port, '/health/live')['status'], 'process remains live during DB outage');
    assertSameValue(503, runtimeHttp($port, '/health/ready')['status'], 'readiness reports DB outage without configuration disclosure');
    assertRuntimeCompose(runtimeComposeCommand([...$base, 'start', 'db'], $environment, $root), 'database restart');
    $deadline = microtime(true) + 20;
    do {
        $ready = runtimeHttp($port, '/health/ready');
        if ($ready['status'] === 200) {
            break;
        }
        usleep(200000);
    } while (microtime(true) < $deadline);
    assertSameValue(200, $ready['status'], 'runtime reconnects after DB restart with data volumes intact');

    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 real nginx/FPM Compose lifecycle\n";
} finally {
    runtimeComposeCommand([...$base, '--profile', 'deployment', 'down', '--volumes', '--remove-orphans'], $environment, $root, 60);
    runtimeComposeCommand(['docker', 'image', 'rm', $runtimeImage], $environment, $root, 60);
    if(isset($nginxFixture)&&is_string($nginxFixture)&&is_file($nginxFixture))unlink($nginxFixture);
}
