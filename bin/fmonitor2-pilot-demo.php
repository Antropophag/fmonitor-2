<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;
use FMonitor2\PilotHttp\NativePhpFclosePrimitive;
use FMonitor2\PilotHttp\NativePhpStreamCloser;
use FMonitor2\PilotHttp\PhpCssDescriptorOpener;
use FMonitor2\PilotHttp\ShlzCssManifest;

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'FMonitor2\\InstallationProcess\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = dirname(__DIR__) . '/app/InstallationProcess/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require_once $path;
});

const DEMO_NOW = '2026-08-29T12:00:00+03:00';

/** @param array<string,mixed> $value */
function demoFinish(array $value, int $code): never
{
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    exit($code);
}

function demoFailure(string $reason = 'STARTUP_FAILED', int $code = 70): never
{
    demoFinish(['ok' => false, 'reason' => $reason], $code);
}

function demoPrefix(string $fingerprint, int $generation, string $kind): string
{
    return ($kind === 'process' ? 'fm2d_' : 'fm2l_') . $fingerprint . '_g' . $generation . '_';
}

function demoWriteJson(string $path, array $value): void
{
    $temporary = $path . '.new-' . bin2hex(random_bytes(6));
    $bytes = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($temporary, $bytes, LOCK_EX) === false || !chmod($temporary, 0600) || !rename($temporary, $path)) throw new RuntimeException();
}

function demoReadJson(string $path): ?array
{
    if (!is_file($path) || is_link($path)) return null;
    $value = json_decode((string) file_get_contents($path), true);
    return is_array($value) ? $value : null;
}

function demoMkdir(string $path, int $mode = 0700): void
{
    if (!is_dir($path) && !mkdir($path, $mode, true)) throw new RuntimeException();
    if (is_link($path) || !is_dir($path)) throw new RuntimeException();
}

function demoRemoveTree(string $root): void
{
    if (!is_dir($root) || is_link($root)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if ($item->isLink() || $item->isFile()) unlink($path); else rmdir($path);
    }
    rmdir($root);
}

function demoConnect(array $config): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = @new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['dbPort']);
    if (!$db->set_charset('utf8mb4')) throw new RuntimeException();
    return $db;
}

function demoTables(mysqli $db, string $prefix): array
{
    $like = $db->real_escape_string(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix)) . '%';
    $result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' ESCAPE '\\\\' ORDER BY TABLE_NAME");
    return array_column($result->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
}

function demoDatabaseMarker(mysqli $db, string $table): ?string
{
    $statement=$db->prepare('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');
    $statement->bind_param('s',$table);$statement->execute();$row=$statement->get_result()->fetch_assoc();
    return is_array($row)?(string)$row['TABLE_COMMENT']:null;
}

function demoMarkerValue(string $fingerprint,int $generation,string $nonce):string
{
    return "fmonitor2-demo:{$fingerprint}:{$generation}:{$nonce}";
}

/** @return array<string,string> */
function demoShlzGraph(mixed $entry,string $repo):array
{
    if(!is_string($entry)||!str_ends_with($entry,'/packages/styles/dist/shlz.css')
        ||!str_starts_with($entry,dirname($repo).'/shlz-ui/'))throw new RuntimeException();
    $opener=new PhpCssDescriptorOpener(new NativePhpStreamCloser(new NativePhpFclosePrimitive()));
    $manifest=new ShlzCssManifest($entry,$opener);$members=[];
    try{
        foreach($manifest->relativePaths()as$relative){$asset=$manifest->asset($relative);if($asset===null)throw new RuntimeException();$members[$relative]=$asset->readBytes();}
    }finally{$manifest->close();}
    if(!isset($members['shlz.css']))throw new RuntimeException();
    return $members;
}

function demoProvision(array $config, int $generation): array
{
    $directory = $config['root'] . '/generations/' . $generation;
    $process = demoPrefix($config['fingerprint'], $generation, 'process');
    $legacy = demoPrefix($config['fingerprint'], $generation, 'legacy');
    if (is_dir($directory)) {
        $existing = demoReadJson($directory . '/owner.json');
        if ($existing !== null && ($existing['fingerprint'] ?? null) !== $config['fingerprint']) throw new RuntimeException();
        throw new RuntimeException();
    }
    $db = demoConnect($config);
    try {
        if (demoTables($db, $process) !== [] || demoTables($db, $legacy) !== []) throw new RuntimeException();
        demoMkdir($directory . '/artifacts', 0755);
        $nonce=bin2hex(random_bytes(16));
        demoWriteJson($directory . '/owner.json', ['fingerprint'=>$config['fingerprint'], 'generation'=>$generation, 'nonce'=>$nonce, 'processPrefix'=>$process, 'legacyPrefix'=>$legacy]);

        $db->query("CREATE TABLE `{$legacy}fm_maintable` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40),responsstroicontrol VARCHAR(80)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE `{$legacy}users_roles` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE `{$legacy}users` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,email VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("INSERT INTO `{$legacy}users_roles` VALUES(5,'ФКР',1),(8,'Строительный контроль',1)");
        $db->query("INSERT INTO `{$legacy}users` VALUES(18,'Сидоров Сергей Сергеевич','sidorov@shlz.ru',5,1),(73,'Анна Волкова','volkova@shlz.ru',8,1)");
        $db->query("INSERT INTO `{$legacy}fm_maintable` VALUES(4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-20',NULL,NULL,NULL,'73'),(4999,'Москва, ул. Непилотная, д. 1','1','77-000999','2026-09-30','2026-12-01',NULL,NULL,NULL,'73')");
        foreach ([ProductionProcessSchemaMigration::class, WorkforceCatalogSchemaMigration::class, ProcessUserCapabilitiesSchemaMigration::class, ProcessCommandCapabilitiesSchemaMigration::class] as $migration) {
            $result = $migration::apply($db, $process);
            if (isset($result['reason'])) throw new RuntimeException();
        }
        $marker=$db->real_escape_string(demoMarkerValue($config['fingerprint'],$generation,$nonce));
        $db->query("ALTER TABLE `{$process}fm2_installation_cases` COMMENT='{$marker}'");
        $db->query("ALTER TABLE `{$legacy}fm_maintable` COMMENT='{$marker}'");
        $db->query("INSERT INTO `{$process}fm2_process_user_capabilities` VALUES(18,'assignment_order.prepare',NULL),(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(73,'construction_control_engineer','Инженер строительного контроля')");
        $db->query("INSERT INTO `{$process}fm2_workforce_catalog` VALUES(1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-27T18:15:00+03:00'),(2088,'Петров Пётр Петрович','Электромеханик по лифтам','employed','2025-01-10',NULL,'one_c_zup_via_bitrix','2026-08-27T18:15:00+03:00')");
        $import = (new PilotCaseImporter($db, $process, $legacy))->import([4512], DEMO_NOW);
        if (($import['imported'] ?? null) !== [4512]) throw new RuntimeException();
        demoWriteJson($directory . '/ready.json', ['fingerprint'=>$config['fingerprint'], 'generation'=>$generation, 'schemaVersion'=>4, 'objectId'=>4512]);
        return ['generation'=>$generation, 'processPrefix'=>$process, 'legacyPrefix'=>$legacy, 'artifactRoot'=>$directory . '/artifacts'];
    } catch (Throwable $error) {
        throw $error;
    } finally {
        try { $db->close(); } catch (Throwable) {}
    }
}

function demoGeneration(array $config, int $generation): ?array
{
    $directory = $config['root'] . '/generations/' . $generation;
    $owner = demoReadJson($directory . '/owner.json');
    $ready = demoReadJson($directory . '/ready.json');
    if (($owner['fingerprint'] ?? null) !== $config['fingerprint'] || ($ready['fingerprint'] ?? null) !== $config['fingerprint'] || ($ready['schemaVersion'] ?? null) !== 4) return null;
    if (($owner['generation'] ?? null) !== $generation || ($ready['generation'] ?? null) !== $generation
        || ($owner['processPrefix'] ?? null) !== demoPrefix($config['fingerprint'], $generation, 'process')
        || ($owner['legacyPrefix'] ?? null) !== demoPrefix($config['fingerprint'], $generation, 'legacy')
        || !is_string($owner['nonce'] ?? null) || preg_match('/^[0-9a-f]{32}$/D',$owner['nonce'])!==1
        || !is_dir($directory . '/artifacts') || is_link($directory . '/artifacts')) return null;
    $artifactInfo=lstat($directory . '/artifacts');
    if($artifactInfo===false||($artifactInfo['mode']&0777)!==0755||($artifactInfo['uid']??null)!==posix_geteuid())return null;
    $db = demoConnect($config);
    try {
        $expected = ['fm2_assignment_orders','fm2_installation_cases','fm2_order_artifacts','fm2_order_installers','fm2_process_events','fm2_process_tasks','fm2_process_user_capabilities','fm2_workforce_catalog'];
        $tables = demoTables($db, (string) $owner['processPrefix']);
        if ($tables !== array_map(static fn(string $suffix): string => $owner['processPrefix'] . $suffix, $expected)) return null;
        if (count(demoTables($db, (string) $owner['legacyPrefix'])) !== 3) return null;
        $marker=demoMarkerValue($config['fingerprint'],$generation,$owner['nonce']);
        if(demoDatabaseMarker($db,$owner['processPrefix'].'fm2_installation_cases')!==$marker
            ||demoDatabaseMarker($db,$owner['legacyPrefix'].'fm_maintable')!==$marker)return null;
        $legacyRows=$db->query("SELECT id,regnumber,ordadr_address,entrance,workdatestart,workdateendadjusted,responsstroicontrol FROM `{$owner['legacyPrefix']}fm_maintable` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        if($legacyRows!==[
            ['id'=>'4512','regnumber'=>'77-000123','ordadr_address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','workdatestart'=>'2026-10-05','workdateendadjusted'=>'2026-12-20','responsstroicontrol'=>'73'],
            ['id'=>'4999','regnumber'=>'77-000999','ordadr_address'=>'Москва, ул. Непилотная, д. 1','entrance'=>'1','workdatestart'=>'2026-09-30','workdateendadjusted'=>'2026-12-01','responsstroicontrol'=>'73'],
        ])return null;
        $people=$db->query("SELECT id,name,email,role_id,status FROM `{$owner['legacyPrefix']}users` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        if($people!==[
            ['id'=>'18','name'=>'Сидоров Сергей Сергеевич','email'=>'sidorov@shlz.ru','role_id'=>'5','status'=>'1'],
            ['id'=>'73','name'=>'Анна Волкова','email'=>'volkova@shlz.ru','role_id'=>'8','status'=>'1'],
        ])return null;
        $workforce=$db->query("SELECT installer_tab_id,fio,employment_status,workforce_source_updated_at FROM `{$owner['processPrefix']}fm2_workforce_catalog` ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC);
        if($workforce!==[
            ['installer_tab_id'=>'1042','fio'=>'Иванов Иван Иванович','employment_status'=>'employed','workforce_source_updated_at'=>'2026-08-27T18:15:00+03:00'],
            ['installer_tab_id'=>'2088','fio'=>'Петров Пётр Петрович','employment_status'=>'employed','workforce_source_updated_at'=>'2026-08-27T18:15:00+03:00'],
        ])return null;
    } finally { $db->close(); }
    return ['generation'=>$generation, 'processPrefix'=>$owner['processPrefix'], 'legacyPrefix'=>$owner['legacyPrefix'], 'artifactRoot'=>$directory . '/artifacts'];
}

function demoRunning(array $config): bool
{
    $pidData = demoReadJson($config['root'] . '/server.json');
    $pid = (int) ($pidData['pid'] ?? 0);
    if (($pidData['fingerprint'] ?? null) !== $config['fingerprint'] || $pid < 2) return false;
    if (!@posix_kill($pid, 0)) { @unlink($config['root'] . '/server.json'); return false; }
    $command = @file_get_contents('/proc/' . $pid . '/cmdline');
    return is_string($command) && str_contains($command, 'fmonitor2-pilot-demo.php');
}

function demoHttp(int $port, string $path,string $method='GET'): array
{
    $socket = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, .5);
    if ($socket === false) return [0, ''];
    fwrite($socket, "{$method} {$path} HTTP/1.1\r\nHost: 127.0.0.1:{$port}\r\nConnection: close\r\n\r\n");
    stream_set_timeout($socket, 2); $raw = stream_get_contents($socket); fclose($socket);
    if (!is_string($raw) || preg_match('/^HTTP\/1\.[01] (\d{3})/', $raw, $match) !== 1) return [0, '', []];
    [$head,$body]=array_pad(explode("\r\n\r\n",$raw,2),2,'');$headers=[];
    foreach(array_slice(explode("\r\n",$head),1)as$line)if(str_contains($line,':')){[$name,$value]=explode(':',$line,2);$headers[strtolower($name)]=trim($value);}
    return [(int) $match[1],$body,$headers];
}

function demoServe(array $config, array $generation, bool $initialSmoke, bool $activate): never
{
    if (demoRunning($config)) demoFailure('ALREADY_RUNNING', 73);
    $probe = @stream_socket_server("tcp://127.0.0.1:{$config['port']}", $errno, $error);
    if ($probe === false) demoFailure('STARTUP_FAILED', 71);
    fclose($probe);
    $environment = array_merge($_ENV, [
        'FMONITOR_DB_HOST'=>$config['host'], 'FMONITOR_DB_PORT'=>(string)$config['dbPort'], 'FMONITOR_DB_NAME'=>$config['database'],
        'FMONITOR_DB_USER'=>$config['user'], 'FMONITOR_DB_PASSWORD'=>$config['password'], 'FMONITOR_PROCESS_TABLE_PREFIX'=>$generation['processPrefix'],
        'FMONITOR_LEGACY_TABLE_PREFIX'=>$generation['legacyPrefix'], 'FMONITOR_ARTIFACT_STORAGE_ROOT'=>$generation['artifactRoot'],
        'FMONITOR_SHLZ_CSS_PATH'=>$config['shlz'], 'FMONITOR_PILOT_CSS_PATH'=>$config['pilotCss'], 'FMONITOR_NOW'=>DEMO_NOW,
        'FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:' . $config['port'], 'REMOTE_USER'=>'sidorov@shlz.ru',
        'FMONITOR_DEMO_LOOPBACK'=>'1','FMONITOR_DEMO_LOOPBACK_NONCE'=>bin2hex(random_bytes(16)),
    ]);
    $pipes = [];
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $config['port'], $config['repo'] . '/public/router.php'], [0=>['file','/dev/null','r'],1=>['file','/dev/null','a'],2=>['file','/dev/null','a']], $pipes, $config['repo'], $environment);
    if (!is_resource($server)) demoFailure();
    $ok = false; $deadline = microtime(true) + 5;
    do {
        usleep(50000);
        [$queueStatus, $queue] = demoHttp($config['port'], '/pilot/objects');
        [$cardStatus, $card] = demoHttp($config['port'], '/pilot/objects/4512');
        [$formStatus, $form] = demoHttp($config['port'], '/pilot/objects/4512/assignment-order/prepare');
        [$foreignStatus] = demoHttp($config['port'], '/pilot/objects/4999');
        [$pilotStatus,$pilotBytes,$pilotHeaders]=demoHttp($config['port'],'/pilot/assets/pilot.css');
        [$repeatStatus,$repeatQueue]=demoHttp($config['port'],'/pilot/objects');
        $graphOk=true;
        foreach(array_reverse($config['shlzMembers'],true)as$relative=>$expectedBytes){
            $route='/pilot/assets/'.$relative;[$assetStatus,$assetBytes,$assetHeaders]=demoHttp($config['port'],$route);
            $length=(string)strlen($expectedBytes);
            if($assetStatus!==200||$assetBytes!==$expectedBytes||($assetHeaders['content-type']??null)!=='text/css; charset=UTF-8'||($assetHeaders['content-length']??null)!==$length){$graphOk=false;break;}
        }
        $rootBytes=$config['shlzMembers']['shlz.css'];[$headStatus,$headBytes,$headHeaders]=demoHttp($config['port'],'/pilot/assets/shlz.css','HEAD');
        $graphOk=$graphOk&&$headStatus===200&&$headBytes===''&&($headHeaders['content-type']??null)==='text/css; charset=UTF-8'&&($headHeaders['content-length']??null)===(string)strlen($rootBytes);
        [$unknownAssetStatus]=demoHttp($config['port'],'/pilot/assets/not-in-official-graph.css');
        $graphOk=$graphOk&&$unknownAssetStatus===404;
        $ok = $queueStatus === 200 && substr_count($queue, '/pilot/objects/4512') === 1
            && str_contains($queue, '/pilot/assets/shlz.css') && str_contains($queue, '/pilot/assets/pilot.css')
            && strpos($queue,'/pilot/assets/shlz.css')<strpos($queue,'/pilot/assets/pilot.css')
            && $repeatStatus===200&&$repeatQueue===$queue
            && $graphOk
            && $pilotStatus===200&&($pilotHeaders['content-type']??null)==='text/css; charset=UTF-8'&&hash('sha256',$pilotBytes)===hash_file('sha256',$config['pilotCss'])
            && $cardStatus === 200 && str_contains($card,'77-000123') && str_contains($card,'Москва, ул. Примерная, д. 10')
            && str_contains($card,'2026-10-05') && str_contains($card,'2026-12-20')
            && (!$initialSmoke || (str_contains($card, 'Требуется распоряжение') && str_contains($card, '/pilot/objects/4512/assignment-order/prepare')
                && $formStatus === 200 && str_contains($form,'name="installerTabIds[]"') && str_contains($form,'value="1042"')
                && str_contains($form,'value="2088"')
                && str_contains($form,'name="controlEngineerUserId"') && preg_match('/<option[^>]*value="73"[^>]*selected/D',$form)===1
                && preg_match('/<(?:button|input)[^>]*type="submit"(?![^>]*disabled)[^>]*>/D',$form)===1))
            && $foreignStatus === 404;
    } while (!$ok && microtime(true) < $deadline && proc_get_status($server)['running']);
    if (!$ok) {
        $shlzSmokeFailure=$queueStatus===200&&!$graphOk;
        proc_terminate($server);proc_close($server);
        if($shlzSmokeFailure)demoFailure('SHLZ_ASSETS_UNAVAILABLE',78);
        demoFailure();
    }
    try{
        if($activate)demoWriteJson($config['root'] . '/active.json', ['fingerprint'=>$config['fingerprint'], 'generation'=>$generation['generation'], 'processPrefix'=>$generation['processPrefix'], 'legacyPrefix'=>$generation['legacyPrefix'], 'port'=>$config['port'], 'state'=>'ready']);
        demoWriteJson($config['root'] . '/server.json', ['fingerprint'=>$config['fingerprint'], 'pid'=>getmypid(), 'port'=>$config['port']]);
    }catch(Throwable $error){proc_terminate($server);proc_close($server);throw $error;}
    echo "FMonitor 2.0 pilot: http://127.0.0.1:{$config['port']}/pilot/objects\n";
    echo "User: sidorov@shlz.ru · business time: " . DEMO_NOW . "\n";
    echo "Stop: Ctrl+C · reset: php bin/fmonitor2-pilot-demo.php reset\n";
    flush();
    $stop = false;
    if (function_exists('pcntl_async_signals')) { pcntl_async_signals(true); pcntl_signal(SIGTERM, static function () use (&$stop): void { $stop = true; }); pcntl_signal(SIGINT, static function () use (&$stop): void { $stop = true; }); }
    while (!$stop && proc_get_status($server)['running']) usleep(100000);
    proc_terminate($server); proc_close($server); @unlink($config['root'] . '/server.json');
    exit(0);
}

$arguments = array_slice($argv, 1);
if (count($arguments) > 1 || ($arguments !== [] && !in_array($arguments[0], ['start','reset','status','cleanup'], true))) demoFailure('CONFIGURATION_INVALID', 64);
$verb = $arguments[0] ?? 'start';
$portText = getenv('FMONITOR_DEMO_PORT') === false ? '8092' : getenv('FMONITOR_DEMO_PORT');
if (!is_string($portText) || preg_match('/^(?:[1-9][0-9]{3,4})$/D', $portText) !== 1 || (int)$portText < 1024 || (int)$portText > 65535) demoFailure('CONFIGURATION_INVALID', 64);
$repo = realpath(dirname(__DIR__)); $home = getenv('HOME');
$shlz = realpath(dirname((string)$repo) . '/shlz-ui/packages/styles/dist/shlz.css');
$pilotCss = realpath((string)$repo . '/rapid-pilot/pilot.css');
if (!is_string($repo) || !is_string($home) || $home === '' || !is_dir($home)) demoFailure('CONFIGURATION_INVALID', 64);
$fingerprint = substr(hash('sha256', $repo), 0, 8);
$dbPortText = getenv('FMONITOR_DEMO_DB_PORT') === false ? '23306' : getenv('FMONITOR_DEMO_DB_PORT');
$config = [
    'repo'=>$repo, 'fingerprint'=>$fingerprint, 'root'=>$home . '/.local/state/fmonitor2/pilot-demo/' . $fingerprint, 'port'=>(int)$portText,
    'host'=>getenv('FMONITOR_DEMO_DB_HOST') === false ? '127.0.0.1' : getenv('FMONITOR_DEMO_DB_HOST'),
    'dbPort'=>is_string($dbPortText) ? (int)$dbPortText : 0,
    'database'=>getenv('FMONITOR_DEMO_DB_NAME') === false ? 'fmonitor2_demo' : getenv('FMONITOR_DEMO_DB_NAME'),
    'user'=>getenv('FMONITOR_DEMO_DB_USER') === false ? 'fmonitor2_demo' : getenv('FMONITOR_DEMO_DB_USER'),
    'password'=>getenv('FMONITOR_DEMO_DB_PASSWORD') === false ? 'fmonitor2_demo_local' : getenv('FMONITOR_DEMO_DB_PASSWORD'),
    'shlz'=>$shlz, 'pilotCss'=>$pilotCss,
];
if (!is_string($dbPortText) || preg_match('/^[1-9][0-9]*$/D', $dbPortText) !== 1
    || !is_string($config['host']) || $config['host'] === '' || !is_string($config['database']) || $config['database'] === ''
    || !is_string($config['user']) || $config['user'] === '' || !is_string($config['password']) || $config['dbPort'] < 1 || $config['dbPort'] > 65535) demoFailure('CONFIGURATION_INVALID', 64);
try {
    demoMkdir($config['root'] . '/generations');
    $manifest = demoReadJson($config['root'] . '/active.json');
    if ($verb === 'status') {
        $generationNumber = (int)($manifest['generation'] ?? 0); $generation = $generationNumber > 0 ? demoGeneration($config, $generationNumber) : null;
        demoFinish(['ok'=>true, 'running'=>demoRunning($config), 'url'=>'http://127.0.0.1:' . $config['port'] . '/pilot/objects', 'generation'=>$generation? $generationNumber : null, 'state'=>$generation ? 'ready' : ($manifest === null ? 'absent' : 'incomplete')], 0);
    }
    if($verb!=='cleanup'){
        try{$config['shlzMembers']=demoShlzGraph($shlz,$repo);}catch(Throwable){demoFailure('SHLZ_ASSETS_UNAVAILABLE',78);}
    }
    if (!is_string($pilotCss) || !is_file($pilotCss)) demoFailure();
    if (demoRunning($config)) demoFailure('ALREADY_RUNNING', 73);
    if ($verb === 'cleanup') {
        $removed = 0;
        foreach (glob($config['root'] . '/generations/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $owner = demoReadJson($directory . '/owner.json');
            $number = filter_var(basename($directory), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if ($number === false || ($owner['fingerprint'] ?? null) !== $fingerprint || ($owner['generation'] ?? null) !== $number
                || ($owner['processPrefix'] ?? null) !== demoPrefix($fingerprint, $number, 'process')
                || ($owner['legacyPrefix'] ?? null) !== demoPrefix($fingerprint, $number, 'legacy')) continue;
            $db = demoConnect($config);
            $marker=is_string($owner['nonce']??null)?demoMarkerValue($fingerprint,$number,$owner['nonce']):'';
            if($marker===''||demoDatabaseMarker($db,$owner['processPrefix'].'fm2_installation_cases')!==$marker
                ||demoDatabaseMarker($db,$owner['legacyPrefix'].'fm_maintable')!==$marker){$db->close();continue;}
            $db->query('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ([(string)$owner['processPrefix'], (string)$owner['legacyPrefix']] as $prefix) foreach (demoTables($db, $prefix) as $table) $db->query("DROP TABLE `{$table}`");
            } finally { $db->query('SET FOREIGN_KEY_CHECKS=1'); }
            $db->close(); demoRemoveTree($directory); $removed++;
        }
        @unlink($config['root'] . '/active.json');
        echo "Удалено поколений: {$removed}." . ($removed > 0 ? " Восстановление bootstrap-ом невозможно." : '') . "\n"; exit(0);
    }
    if ($verb === 'start' && $manifest !== null) {
        $generation = demoGeneration($config, (int)($manifest['generation'] ?? 0));
        if ($generation === null) demoFailure();
        demoServe($config, $generation, false, false);
    }
    $portProbe=@stream_socket_server("tcp://127.0.0.1:{$config['port']}",$portErrorNumber,$portError);
    if($portProbe===false)demoFailure('STARTUP_FAILED',71);
    fclose($portProbe);
    $activeNumber = (int)($manifest['generation'] ?? 0);
    $next = $activeNumber + 1;
    $nextDirectory = $config['root'] . '/generations/' . $next;
    if (is_dir($nextDirectory)) {
        $candidateOwner=demoReadJson($nextDirectory . '/owner.json');
        if($candidateOwner!==null&&(($candidateOwner['fingerprint']??null)!==$fingerprint
            ||($candidateOwner['generation']??null)!==$next
            ||($candidateOwner['processPrefix']??null)!==demoPrefix($fingerprint,$next,'process')
            ||($candidateOwner['legacyPrefix']??null)!==demoPrefix($fingerprint,$next,'legacy')))throw new RuntimeException();
        $used = [];
        foreach (glob($config['root'] . '/generations/*', GLOB_ONLYDIR) ?: [] as $directory) if (preg_match('/\/([1-9][0-9]*)$/D', $directory, $m) === 1) $used[] = (int)$m[1];
        $next = max([0, ...$used, $activeNumber]) + 1;
    }
    $generation = demoProvision($config, $next);
    demoServe($config, $generation, true, true);
} catch (Throwable) {
    demoFailure();
}
