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
$root=dirname(__DIR__,2);$tmp=sys_get_temp_dir().'/qcs-'.bin2hex(random_bytes(12));mkdir($tmp.'/tools/verification',0700,true);foreach(['InstallationProcess','AssignmentOrderComposition','Verification','Otiz','Runtime','Jobs']as$d)mkdir($tmp.'/tests/'.$d,0700,true);copy($root.'/tools/verification/run.sh',$tmp.'/tools/verification/run.sh');file_put_contents($tmp.'/tests/InstallationProcess/unit_sample_test.php',"<?php echo 'unit';");file_put_contents($tmp.'/tests/InstallationProcess/db_sample_test.php',"<?php /* FMONITOR_TEST_DB */");$bin=$tmp.'/bin';mkdir($bin,0700);foreach(['dirname','find','sort','php','python3','bash']as$n){$p=trim((string)shell_exec('command -v '.escapeshellarg($n)));if($p!=='')symlink($p,$bin.'/'.$n);}
// VERIFICATION-INVENTORY-001 supersedes the source-scanning rg prerequisite.
file_put_contents($tmp.'/tools/verification/suites.tsv',
    "unit\tphp\ttests/InstallationProcess/unit_sample_test.php\n".
    "db\tphp\ttests/InstallationProcess/db_sample_test.php\n");
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

    $childFailure = qcsRun([$launcher, 'governance', 'sh', '-c', 'exit 23'], $root);
    assertSameValue(23, $childFailure['exit'], 'DP110A-02 child exit code is returned');
    assertSameValue(1, preg_match('/RUN_IN_PROFILE_RESULT (\{[^\n]+\})\n/D',
        $childFailure['err'], $failedRecord), 'DP110A-04 failure result');
    $failedEvidence = json_decode($failedRecord[1], true, flags: JSON_THROW_ON_ERROR);
    assertSameValue(23, $failedEvidence['exit_code'], 'DP110A-04 failed exit is recorded');
    assertSameValue(['sh', '-c', 'exit 23'], $failedEvidence['argv'],
        'DP110A-04 failed argv is recorded');
}finally{if(is_string($tag)){$owned=qcsRun(['docker','image','inspect',$tag,'--format','{{index .Config.Labels "org.opencontainers.image.revision"}}'],$root);if($owned['exit']===0&&trim($owned['out'])===trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD')))qcsRun(['docker','image','rm',$tag],$root);}qcsRemove($tmp);}echo"QUALITY-GRAPH-CI-SETUP-001 PASSED\n";
