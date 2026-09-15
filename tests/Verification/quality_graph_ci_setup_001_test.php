<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
/** Drain both child streams concurrently; verbose Docker builds must not deadlock. */
function qcsRun(array $command, string $cwd, array $env = [], float $timeoutSeconds = 900): array
{
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, array_replace(getenv(), $env));
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: child');
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = [1 => '', 2 => ''];
    $deadline = hrtime(true) + (int) ($timeoutSeconds * 1_000_000_000);
    $exit = null;
    try {
        while (true) {
            $status = proc_get_status($process);
            if (!$status['running'] && $exit === null) {
                $exit = $status['exitcode'];
            }
            $read = array_filter($pipes, static fn ($pipe) => !feof($pipe));
            if ($read === [] && !$status['running']) {
                break;
            }
            if (hrtime(true) >= $deadline) {
                throw new TestFailure('SETUP_FAILURE: child deadline exceeded');
            }
            if ($read === []) {
                usleep(10_000);
                continue;
            }
            $write = $except = null;
            if (stream_select($read, $write, $except, 0, 100_000) === false) {
                throw new TestFailure('SETUP_FAILURE: child stream selection');
            }
            foreach ($read as $key => $pipe) {
                $chunk = fread($pipe, 65536);
                if ($chunk === false) {
                    throw new TestFailure('SETUP_FAILURE: child stream read');
                }
                $output[$key] .= $chunk;
            }
        }
    } finally {
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
        }
        $closedExit = proc_close($process);
    }
    return ['exit' => $exit ?? $closedExit, 'out' => $output[1], 'err' => $output[2]];
}
function qcsRemove(string$p):void{$i=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($i as$f)$f->isDir()&&!$f->isLink()?rmdir($f->getPathname()):unlink($f->getPathname());rmdir($p);}
$root=dirname(__DIR__,2);$tmp=sys_get_temp_dir().'/qcs-'.bin2hex(random_bytes(12));mkdir($tmp.'/tools/verification',0700,true);foreach(['InstallationProcess','AssignmentOrderComposition','Verification','Otiz','Runtime','Jobs']as$d)mkdir($tmp.'/tests/'.$d,0700,true);copy($root.'/tools/verification/run.sh',$tmp.'/tools/verification/run.sh');copy($root.'/tools/verification/inventory.py',$tmp.'/tools/verification/inventory.py');file_put_contents($tmp.'/tests/InstallationProcess/unit_sample_test.php',"<?php echo 'unit';");file_put_contents($tmp.'/tests/InstallationProcess/db_sample_test.php',"<?php /* FMONITOR_TEST_DB */");$bin=$tmp.'/bin';mkdir($bin,0700);foreach(['dirname','find','sort','php','python3','bash']as$n){$p=trim((string)shell_exec('command -v '.escapeshellarg($n)));if($p!=='')symlink($p,$bin.'/'.$n);}
// VERIFICATION-INVENTORY-001 supersedes the source-scanning rg prerequisite.
file_put_contents($tmp.'/tools/verification/suites.tsv',
    "db\tphp\ttests/InstallationProcess/db_sample_test.php\tintegration\n".
    "unit\tphp\ttests/InstallationProcess/unit_sample_test.php\tunit\n");
$tag=null;
try {
    $launcher = $root.'/tools/delivery/run-in-profile';
    assertSameValue(true, is_file($launcher),
        'INTENDED_RED DELIVERY-PROFILE-110-A launcher is missing');
    $missingCommand = qcsRun([$launcher, 'governance'], $root);
    assertSameValue(true, $missingCommand['exit'] !== 0,
        'DP110A-02 empty command must be rejected');
    $rejectedMarker = $tmp.'/unknown-profile-command-ran';
    $unknownProfile = qcsRun([$launcher, 'unknown-profile', 'sh', '-c',
        'printf rejected > "$1"', 'rejected-command', $rejectedMarker], $root);
    assertSameValue(true, $unknownProfile['exit'] !== 0,
        'DP110A-01 unknown profile must be rejected');
    assertSameValue(false, file_exists($rejectedMarker),
        'DP110A-01 unknown profile must not launch its command');

    $pinValues = [];
    foreach (file($root.'/tools/delivery/dependencies.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] !== '#') {
            [$name, $value] = explode('=', $line, 2);
            $pinValues[$name] = $value;
        }
    }
    $recipe = file_get_contents($root.'/tools/delivery/Dockerfile.focused-checks');
    foreach (['PHP_IMAGE_DIGEST', 'NODE_BOOKWORM_IMAGE_DIGEST', 'PYTHON_IMAGE_DIGEST',
              'COMPOSER_IMAGE_DIGEST', 'UV_IMAGE_DIGEST'] as $digestPin) {
        assertSameValue(1, preg_match('/^sha256:[0-9a-f]{64}$/D', $pinValues[$digestPin] ?? ''),
            "DP110A-03 {$digestPin} is an immutable registry digest");
        assertSameValue(true, str_contains($recipe, '@${'.$digestPin.'}'),
            "DP110A-03 {$digestPin} is consumed by a FROM input");
    }

    foreach (['unit', 'db'] as $suite) {
        $r=qcsRun(['bash','tools/verification/run.sh','list',$suite],$tmp,['PATH'=>$bin]);
        assertSameValue([0,"php\ttests/InstallationProcess/{$suite}_sample_test.php\n",''],
            array_values($r),'explicit inventory remains complete without rg: '.$suite);
    }
    unlink($tmp.'/tools/verification/suites.tsv');
    $r=qcsRun(['bash','tools/verification/run.sh','list','unit'],$tmp,['PATH'=>$bin]);
    assertSameValue([1,'',"SETUP_FAILURE: missing verification catalog: tools/verification/suites.tsv\n"],
        array_values($r),'missing inventory fails before any partial list');
$tag='fmonitor2-php-test:qcs-'.bin2hex(random_bytes(8));$absent=qcsRun(['docker','image','inspect',$tag],$root);assertSameValue(true,$absent['exit']!==0,'unique test image tag absent before public build');$r=qcsRun(['make','test-tools','TEST_TOOL_IMAGE='.$tag],$root);assertSameValue(0,$r['exit'],'RED_ASSERTION: public make test-tools owns isolated image '.$r['err']);$head=trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD'));$i=qcsRun(['docker','image','inspect',$tag,'--format','{{.Id}}|{{index .Config.Labels "org.opencontainers.image.revision"}}'],$root);assertSameValue(1,preg_match('/^(sha256:[0-9a-f]{64})\|'.preg_quote($head,'/').'\n$/D',$i['out'],$im),'test image ID/source');$imageId=$im[1];$p=qcsRun(['docker','run','--rm','--network','none','--entrypoint','sh',$imageId,'-c','test "$(id -u)" = 0 && php -r \'exit(PHP_VERSION_ID>=80500&&PHP_VERSION_ID<80600&&extension_loaded("mysqli")&&extension_loaded("pcntl")?0:1);\' && command -v setpriv >/dev/null && test "$(setpriv --reuid=65534 --regid=65534 --clear-groups id -u)" = 65534'],$root);assertSameValue([0,'',''],array_values($p),'immutable test image preconditions');qcsRun(['docker','image','rm',$tag],$root);

    foreach (['governance', 'integration', 'browser'] as $profile) {
        $command = ['sh', '-c', <<<'SH'
set -eu
test -f /.dockerenv
test "$FMONITOR_PROFILE" = "$1"
test "$FMONITOR_IMAGE_DIGEST" != ""
set -a
. tools/delivery/dependencies.env
set +a
php -r '$v=[];foreach(file("tools/delivery/dependencies.env", FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){if($l[0]!=="#"){$p=explode("=",$l,2);$v[$p[0]]=$p[1];}} exit(PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION===$v["PHP_VERSION"]?0:1);'
php -r 'foreach(explode(",",getenv("PHP_EXTENSIONS")) as $e) if(!extension_loaded($e)) exit(1);'
python3 -c 'import pathlib,platform; p=dict(x.split("=",1) for x in pathlib.Path("tools/delivery/dependencies.env").read_text().splitlines() if x and not x.startswith("#")); assert platform.python_version()==p["PYTHON_VERSION"]'
node -e 'const fs=require("fs"),p=Object.fromEntries(fs.readFileSync("tools/delivery/dependencies.env","utf8").split("\n").filter(x=>x&&!x.startsWith("#")).map(x=>x.split(/=(.*)/s).slice(0,2))); if(process.versions.node!==p.NODE_VERSION)process.exit(1)'
test "$(composer --version --no-ansi | awk '{print $3}')" = "$COMPOSER_VERSION"
test "$(uv --version | awk '{print $2}')" = "$UV_VERSION"
test "$(npm --version)" = "$NPM_VERSION"
composer check-platform-reqs --no-dev --no-interaction >/dev/null
test -f "$FMONITOR_COMPOSER_VENDOR/composer/installed.json"
php -r '$lock=json_decode(file_get_contents("composer.lock"),true);$installed=json_decode(file_get_contents(getenv("FMONITOR_COMPOSER_VENDOR")."/composer/installed.json"),true);$installed=$installed["packages"]??$installed;$actual=[];foreach($installed as $p)$actual[$p["name"]]=$p["version"];$expected=[];foreach(array_merge($lock["packages"],$lock["packages-dev"]??[]) as $p)$expected[$p["name"]]=$p["version"];ksort($actual);ksort($expected);exit($actual===$expected?0:1);'
test -x "$FMONITOR_PYTHON_ENV/bin/python"
UV_PROJECT_ENVIRONMENT="$FMONITOR_PYTHON_ENV" uv sync --frozen --offline --no-install-project --check >/dev/null
if test "$1" = browser; then
  node -e 'const fs=require("fs"),path=require("path"),root=process.env.FMONITOR_SHLZ_UI_ROOT,lock=JSON.parse(fs.readFileSync(path.join(root,"package-lock.json"))),expected=lock.packages["node_modules/playwright"].version,actual=require("playwright/package.json").version,browsers=require("playwright-core/browsers.json").browsers,chromium=browsers.find(x=>x.name==="chromium"),executable=require("playwright").chromium.executablePath();if(actual!==expected||!chromium||!chromium.revision||!fs.existsSync(executable))process.exit(1)'
fi
printf 'argv=%s image=%s\n' "$2" "$FMONITOR_IMAGE_DIGEST"
SH, 'profile-probe', $profile, 'value with spaces'];
        $probe = qcsRun([$launcher, $profile, ...$command], $root);
        assertSameValue(0, $probe['exit'],
            "INTENDED_RED DP110A-03 {$profile} locked runtime/dependency contract");
        assertSameValue(1, preg_match('/RUN_IN_PROFILE_RESULT (\{[^\n]+\})\n/D', $probe['err'], $record),
            "DP110A-04 {$profile} compact result");
        $evidence = json_decode($record[1], true, flags: JSON_THROW_ON_ERROR);
        assertSameValue(['argv', 'duration_seconds', 'exit_code', 'git_sha', 'image_digest', 'profile'],
            array_keys($evidence), "DP110A-04 {$profile} evidence fields");
        assertSameValue($profile, $evidence['profile'], "DP110A-01 {$profile} identity");
        assertSameValue($head, $evidence['git_sha'], "DP110A-04 {$profile} git SHA");
        assertSameValue($command, $evidence['argv'], "DP110A-04 {$profile} exact argv");
        assertSameValue(true, is_int($evidence['duration_seconds']) || is_float($evidence['duration_seconds']),
            "DP110A-04 {$profile} numeric duration");
        assertSameValue(true, $evidence['duration_seconds'] >= 0,
            "DP110A-04 {$profile} non-negative duration");
        assertSameValue(1, preg_match('/^sha256:[0-9a-f]{64}$/D', $evidence['image_digest']),
            "DP110A-03 {$profile} immutable digest");
        $reportedImage = qcsRun(['docker', 'image', 'inspect', $evidence['image_digest'],
            '--format', '{{.Id}}'], $root);
        assertSameValue([0, $evidence['image_digest']."\n", ''], array_values($reportedImage),
            "DP110A-03 {$profile} reported image exists");
        assertSameValue("argv=value with spaces image={$evidence['image_digest']}\n", $probe['out'],
            "DP110A-02/03 {$profile} argv and executing image identity");
        assertSameValue(0, $evidence['exit_code'], "DP110A-04 {$profile} recorded exit");
    }

    $portProbe = stream_socket_server('tcp://127.0.0.1:0', $socketError, $socketMessage);
    assertSameValue(true, is_resource($portProbe),
        "SETUP_FAILURE: reserve isolated test DB port: {$socketError} {$socketMessage}");
    $probeAddress = stream_socket_get_name($portProbe, false);
    fclose($portProbe);
    assertSameValue(1, preg_match('/:(\d+)$/D', (string) $probeAddress, $portMatch),
        'DPN110-03 isolated external lifecycle port');
    $composeEnv = [
        'COMPOSE_PROJECT_NAME' => 'qcsnet'.bin2hex(random_bytes(6)),
        'FMONITOR_TEST_DB_PORT' => $portMatch[1],
    ];
    $probePath = $root.'/.local/qcs-db-probe-'.bin2hex(random_bytes(6)).'.php';
    if (!is_dir(dirname($probePath))) {
        mkdir(dirname($probePath), 0700, true);
    }
    file_put_contents($probePath, <<<'PHP'
<?php
$host=getenv('FMONITOR_TEST_DB_HOST');$port=getenv('FMONITOR_TEST_DB_PORT');
if($host!=='test-db'||$port!=='3306'||gethostbyname('test-db')==='test-db')exit(20);
$db=@new mysqli($host,'fmonitor2_test','fmonitor2_test_local','fmonitor2_test',(int)$port);
if($db->connect_errno!==0||$db->query('SELECT 1')->fetch_row()!==['1'])exit(21);
PHP);
    $composeOwned = false;
    $declaredNetwork = null;
    $serviceContainer = null;
    try {
        $network = qcsRun(['docker', 'compose', '-f', 'compose.test.yaml', 'config', '--format', 'json'], $root, $composeEnv);
        assertSameValue(0, $network['exit'], 'DPN110-01 canonical Compose config is readable');
        $config = json_decode($network['out'], true, flags: JSON_THROW_ON_ERROR);
        $networkName = $config['networks']['default']['name'] ?? null;
        assertSameValue(true, is_string($networkName) && $networkName !== '',
            'DPN110-01 canonical Compose declares the actual default network name');
        assertSameValue(false, str_contains($networkName, 'fmonitor2-test'),
            'DPN110-01 randomized canonical name rejects hard-coded fmonitor2-test_default');
        $governanceNoNetwork = qcsRun([$launcher, 'governance', 'sh', '-c',
            'test -z "${FMONITOR_TEST_DB_HOST:-}${FMONITOR_TEST_DB_PORT:-}"'], $root, $composeEnv);
        assertSameValue(0, $governanceNoNetwork['exit'],
            'DPN110-03 governance has no test-network or DB-route dependency');
        $integrationNoNetwork = qcsRun([$launcher, 'integration', 'sh', '-c',
            'test -z "${FMONITOR_TEST_DB_HOST:-}${FMONITOR_TEST_DB_PORT:-}"'], $root, $composeEnv);
        assertSameValue(0, $integrationNoNetwork['exit'],
            'DPN110-03 missing network is not repaired and command still owns its availability result');
        $missingNetwork = qcsRun(['docker', 'network', 'inspect', $networkName], $root);
        assertSameValue(true, $missingNetwork['exit'] !== 0,
            'DPN110-03 launcher does not create a missing canonical network');

        $create = qcsRun(['docker', 'compose', '-f', 'compose.test.yaml', 'create', 'test-db'], $root, $composeEnv);
        assertSameValue(0, $create['exit'], 'DPN110-03 external lifecycle creates stopped test service: '.$create['err']);
        $composeOwned = true;
        $networkId = qcsRun(['docker', 'network', 'inspect', $networkName, '--format', '{{.Id}}'], $root);
        $serviceId = qcsRun(['docker', 'compose', '-f', 'compose.test.yaml', 'ps', '-aq', 'test-db'], $root, $composeEnv);
        assertSameValue(true, $networkId['exit'] === 0 && trim($networkId['out']) !== '',
            'DPN110-03 external lifecycle owns an existing network');
        assertSameValue(true, $serviceId['exit'] === 0 && trim($serviceId['out']) !== '',
            'DPN110-03 external lifecycle owns a stopped test-db');
        $stoppedProbe = qcsRun([$launcher, 'integration', 'php', substr($probePath, strlen($root) + 1)], $root, $composeEnv);
        $stoppedAfter = qcsRun(['docker', 'inspect', trim($serviceId['out']), '--format', '{{.State.Running}}'], $root);
        assertSameValue(true, $stoppedProbe['exit'] !== 0,
            'DPN110-03 launcher does not hide unavailable stopped test-db');
        assertSameValue([0, "false\n", ''], array_values($stoppedAfter),
            'DPN110-03 launcher does not start an externally created stopped service');

        $up = qcsRun(['make', 'test-env-up'], $root, $composeEnv);
        assertSameValue(0, $up['exit'], 'DPN110-03 existing Make/Compose lifecycle starts test-db: '.$up['err']);
        $relativeProbe = substr($probePath, strlen($root) + 1);

        $realDocker = trim((string) shell_exec('command -v docker'));
        $declaredNetwork = 'qcs-declared-'.bin2hex(random_bytes(6));
        $createdNetwork = qcsRun([$realDocker, 'network', 'create', $declaredNetwork], $root);
        assertSameValue(0, $createdNetwork['exit'], 'DPN110-01 sensitivity network exists');
        $serviceContainer = trim($serviceId['out']);
        $connectedAlias = qcsRun([$realDocker, 'network', 'connect', '--alias', 'test-db',
            $declaredNetwork, $serviceContainer], $root);
        assertSameValue(0, $connectedAlias['exit'], 'DPN110-01 test-db joins declared sensitivity network');
        $disconnectedDefault = qcsRun([$realDocker, 'network', 'disconnect', $networkName, $serviceContainer], $root);
        assertSameValue(0, $disconnectedDefault['exit'], 'DPN110-01 derived default route is unavailable during sensitivity probe');
        $dockerSpy = $bin.'/docker';
        file_put_contents($dockerSpy, <<<'SH'
#!/bin/sh
is_config=0
for arg in "$@"; do test "$arg" = config && is_config=1; done
if test "$1" = compose && test "$is_config" = 1; then
  "$REAL_DOCKER" "$@" | python3 -c 'import json,os,sys;p=json.load(sys.stdin);p["networks"]["default"]["name"]=os.environ["QCS_DECLARED_NETWORK"];json.dump(p,sys.stdout)'
else
  exec "$REAL_DOCKER" "$@"
fi
SH);
        chmod($dockerSpy, 0700);
        $spyEnv = array_merge($composeEnv, [
            'PATH' => $bin.':'.getenv('PATH'),
            'REAL_DOCKER' => $realDocker,
            'QCS_DECLARED_NETWORK' => $declaredNetwork,
        ]);
        $declaredProbe = qcsRun([$launcher, 'integration', 'php', $relativeProbe], $root, $spyEnv);
        assertSameValue(0, $declaredProbe['exit'],
            'INTENDED_RED DPN110-01 launcher consumes declared Compose JSON network instead of deriving project_default: '.$declaredProbe['err']);
        $restoredDefault = qcsRun([$realDocker, 'network', 'connect', '--alias', 'test-db',
            $networkName, $serviceContainer], $root);
        assertSameValue(0, $restoredDefault['exit'], 'DPN110-01 fixture restores canonical default network');

        $integrationDb = qcsRun([$launcher, 'integration', 'php', $relativeProbe], $root, $composeEnv);
        $browserDb = qcsRun([$launcher, 'browser', 'php', $relativeProbe], $root, $composeEnv);
        $networkAfter = qcsRun(['docker', 'network', 'inspect', $networkName, '--format', '{{.Id}}'], $root);
        $serviceAfter = qcsRun(['docker', 'compose', '-f', 'compose.test.yaml', 'ps', '-q', 'test-db'], $root, $composeEnv);
        assertSameValue(0, $integrationDb['exit'],
            'INTENDED_RED DPN110-01/02 integration resolves test-db and executes mysqli SELECT 1: '.$integrationDb['err']);
        assertSameValue(0, $browserDb['exit'],
            'INTENDED_RED DPN110-01/02 browser resolves test-db and executes mysqli SELECT 1: '.$browserDb['err']);
        assertSameValue(trim($networkId['out']), trim($networkAfter['out']),
            'DPN110-03 launcher does not replace or create the Compose network');
        assertSameValue(trim($serviceId['out']), trim($serviceAfter['out']),
            'DPN110-03 launcher does not replace or start test-db');
    } finally {
        $cleanupFailures = [];
        if (is_string($declaredNetwork)) {
            if (is_string($serviceContainer) && $serviceContainer !== '') {
                $disconnect = qcsRun(['docker', 'network', 'disconnect', '--force', $declaredNetwork, $serviceContainer], $root);
                if ($disconnect['exit'] !== 0) {
                    $cleanupFailures[] = 'declared-network-disconnect';
                }
            }
            $removeNetwork = qcsRun(['docker', 'network', 'rm', $declaredNetwork], $root);
            if ($removeNetwork['exit'] !== 0) {
                $cleanupFailures[] = 'declared-network-remove';
            }
        }
        if ($composeOwned) {
            $down = qcsRun(['make', 'test-env-down'], $root, $composeEnv);
            if ($down['exit'] !== 0) {
                $cleanupFailures[] = 'make-test-env-down';
            }
        }
        @unlink($probePath);
        assertSameValue([], $cleanupFailures,
            'DPN110-03 finally cleans every test-owned Docker resource after success or failure');
    }

    $childFailure = qcsRun([$launcher, 'governance', 'sh', '-c', 'exit 23'], $root);
    assertSameValue(23, $childFailure['exit'], 'DP110A-02 child exit code is returned');
    assertSameValue(1, preg_match('/RUN_IN_PROFILE_RESULT (\{[^\n]+\})\n/D',
        $childFailure['err'], $failedRecord), 'DP110A-04 failure result');
    $failedEvidence = json_decode($failedRecord[1], true, flags: JSON_THROW_ON_ERROR);
    assertSameValue(23, $failedEvidence['exit_code'], 'DP110A-04 failed exit is recorded');
    assertSameValue(['sh', '-c', 'exit 23'], $failedEvidence['argv'],
        'DP110A-04 failed argv is recorded');
}finally{if(is_string($tag)){$owned=qcsRun(['docker','image','inspect',$tag,'--format','{{index .Config.Labels "org.opencontainers.image.revision"}}'],$root);if($owned['exit']===0&&trim($owned['out'])===trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD')))qcsRun(['docker','image','rm',$tag],$root);}qcsRemove($tmp);}echo"QUALITY-GRAPH-CI-SETUP-001 PASSED\n";
