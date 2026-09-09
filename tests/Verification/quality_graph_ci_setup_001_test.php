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
}finally{if(is_string($tag)){$owned=qcsRun(['docker','image','inspect',$tag,'--format','{{index .Config.Labels "org.opencontainers.image.revision"}}'],$root);if($owned['exit']===0&&trim($owned['out'])===trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD')))qcsRun(['docker','image','rm',$tag],$root);}qcsRemove($tmp);}echo"QUALITY-GRAPH-CI-SETUP-001 PASSED\n";
