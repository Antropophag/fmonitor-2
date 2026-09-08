<?php
declare(strict_types=1);
namespace FMonitor2\demo;

/** Read-only process ownership probe for Linux and macOS demo launchers. */
final class PilotDemoProcess
{
    public static function isLauncher(int $pid): bool
    {
        $command = @file_get_contents('/proc/' . $pid . '/cmdline');
        if (!is_string($command) && PHP_OS_FAMILY === 'Darwin') {
            $process = proc_open(['/bin/ps', '-p', (string)$pid, '-o', 'command='],
                [0=>['file','/dev/null','r'], 1=>['pipe','w'], 2=>['file','/dev/null','a']], $pipes);
            if (!is_resource($process)) return false;
            $command = stream_get_contents($pipes[1]); fclose($pipes[1]); proc_close($process);
        }
        return is_string($command) && preg_match('~(?:^|[ /])fmonitor2-pilot-demo\.php(?:[ \x00]|$)~D', trim($command)) === 1;
    }
}
