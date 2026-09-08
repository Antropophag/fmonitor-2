<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

final class ShlzManifestCaptureProbe
{
    public static function run(string $repository): array
    {
        $repository = realpath($repository);
        if ($repository === false || str_contains($repository, ',')) throw new \TestFailure('SETUP_FAILURE: invalid source mount');
        $inspect = self::command(['docker','image','inspect','fmonitor2-php-test:latest','--format','{{.Id}}']);
        $image = trim($inspect['out']);
        if ($inspect['exit'] !== 0 || preg_match('/^sha256:[0-9a-f]{64}$/D', $image) !== 1) throw new \TestFailure('SETUP_FAILURE: run make test-tools before the CSS capture probe');
        $token = bin2hex(random_bytes(12));
        $cid = sys_get_temp_dir().'/fm2-css-cid-'.$token;
        $command = [
            'docker','run','--rm','--init','--network','none','--read-only',
            '--cidfile',$cid,'--label','fmonitor2.css-capture='.$token,
            '--tmpfs','/tmp:rw,mode=1777,size=64m',
            '--mount','type=bind,src='.$repository.',dst=/workspace/fmonitor-2,readonly',
            '--workdir','/workspace/fmonitor-2','--entrypoint','php',$image,
            'tests/Support/shlz_manifest_capture_witness.php','/workspace/fmonitor-2',
        ];
        try {
            $result = self::command($command, 90);
            if ($result['exit'] !== 0) throw new \TestFailure('SETUP_FAILURE: CSS capture probe: '.substr(str_replace(["\r","\n"],' ',$result['err']),0,300));
            $value = json_decode($result['out'], true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($value) || !is_array($value['witness'] ?? null) || !is_string($value['wire'] ?? null)) throw new \TestFailure('SETUP_FAILURE: CSS probe protocol');
            $w = $value['witness'];
            foreach (['rootOpenBeforePayload','serverStopped','oldDescriptorHeld'] as $key) if (($w[$key] ?? null) !== true) throw new \TestFailure('SETUP_FAILURE: missing capture witness '.$key);
            foreach (['Inode','Size','Mode'] as $field) {
                if (!is_int($w['old'.$field] ?? null) || !is_int($w['new'.$field] ?? null) || $w['old'.$field] === $w['new'.$field]) throw new \TestFailure('SETUP_FAILURE: unchanged witnessed '.$field);
            }
            return $value;
        } finally {
            if (is_file($cid) && !is_link($cid)) {
                $id = trim((string) file_get_contents($cid));
                if (preg_match('/^[0-9a-f]{64}$/D', $id) === 1) {
                    $owned = self::command(['docker','inspect','--format','{{index .Config.Labels "fmonitor2.css-capture"}}',$id]);
                    if ($owned['exit'] === 0 && trim($owned['out']) === $token) {
                        $removed = self::command(['docker','rm','--force',$id]);
                        if ($removed['exit'] !== 0) throw new \TestFailure('SETUP_FAILURE: owned CSS probe cleanup failed');
                    } elseif ($owned['exit'] === 0) {
                        throw new \TestFailure('SETUP_FAILURE: CSS probe ownership mismatch');
                    }
                }
                unlink($cid);
            }
        }
    }

    private static function command(array $command, int $seconds = 15): array
    {
        $process = proc_open($command, [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        if (!is_resource($process)) throw new \TestFailure('SETUP_FAILURE: process did not start');
        foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
        $out = ''; $err = ''; $deadline = microtime(true) + $seconds; $exit = -1;
        try {
            do {
                $out .= (string) stream_get_contents($pipes[1]);
                $err .= (string) stream_get_contents($pipes[2]);
                if (strlen($out) + strlen($err) > 10 * 1024 * 1024) throw new \TestFailure('SETUP_FAILURE: probe output exceeds bound');
                $status = proc_get_status($process);
                if (!$status['running']) { $exit = $status['exitcode']; break; }
                if (microtime(true) >= $deadline) throw new \TestFailure('SETUP_FAILURE: bounded probe timed out');
                usleep(1000);
            } while (true);
            $out .= (string) stream_get_contents($pipes[1]); $err .= (string) stream_get_contents($pipes[2]);
        } finally {
            if (proc_get_status($process)['running'] ?? false) proc_terminate($process, 9);
            foreach ($pipes as $pipe) fclose($pipe);
            $closed = proc_close($process);
            if ($exit < 0) $exit = $closed;
        }
        return ['exit'=>$exit,'out'=>$out,'err'=>$err];
    }
}
