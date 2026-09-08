<?php
declare(strict_types=1);

// Runs only in the owned Linux test image. The production source is mounted read-only.
function witnessFail(string $message): never { throw new RuntimeException('SETUP_FAILURE: '.$message); }
function witnessRemove(string $path): void
{
    if (!is_dir($path)) return;
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    rmdir($path);
}
function witnessFiles(int $pid, string $entry, string $payload): array
{
    $rootFd = null; $payloadOpen = false;
    foreach (glob("/proc/$pid/fd/*") ?: [] as $fd) {
        $target = @readlink($fd);
        if ($target === $entry) $rootFd = $fd;
        if ($target === $payload) $payloadOpen = true;
    }
    return [$rootFd, $payloadOpen];
}
function witnessState(int $pid): string
{
    $status = @file_get_contents("/proc/$pid/status");
    return is_string($status) && preg_match('/^State:\s+([A-Z])/m', $status, $match) === 1 ? $match[1] : '';
}
function witnessGraph(string $root): string
{
    if (!mkdir($root, 0700)) witnessFail('create graph');
    $imports = '';
    for ($i = 0; $i < 240; $i++) {
        $name = sprintf('member-%03d.css', $i);
        $imports .= '@import "'.$name."\";\n";
        if (file_put_contents("$root/$name", str_repeat(".member-$i{}\n", 64)) === false) witnessFail('write member');
        chmod("$root/$name", 0644);
    }
    if (file_put_contents("$root/payload.css", str_repeat('x', 6 * 1024 * 1024)) === false) witnessFail('write payload');
    $entry = "$root/shlz.css";
    if (file_put_contents($entry, $imports."@import \"payload.css\";\n") === false) witnessFail('write root');
    chmod($entry, 0644); chmod("$root/payload.css", 0644);
    return $entry;
}
function witnessAttempt(string $repository, string $directory, int $attempt): ?array
{
    $entry = witnessGraph($directory); $payload = "$directory/payload.css";
    $reservation = stream_socket_server('tcp://127.0.0.1:0', $error, $message);
    if ($reservation === false) witnessFail('reserve loopback port');
    $address = (string) stream_socket_get_name($reservation, false); fclose($reservation);
    $port = (int) substr($address, strrpos($address, ':') + 1);
    $process = null; $pipes = []; $socket = null; $stopped = false; $pid = 0;
    try {
        $command = ['/usr/bin/nice', '-n', '10', PHP_BINARY, '-d', 'expose_php=0', '-S', "127.0.0.1:$port", "$repository/public/router.php"];
        $process = proc_open($command, [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $repository, ['FMONITOR_SHLZ_CSS_PATH'=>$entry]);
        if (!is_resource($process)) witnessFail('start owned PHP server');
        $pid = (int) proc_get_status($process)['pid'];
        foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
        $deadline = microtime(true) + 3;
        do {
            $probe = @stream_socket_client("tcp://127.0.0.1:$port", $error, $message, .05);
            if ($probe !== false) { fclose($probe); break; }
            if (!(proc_get_status($process)['running'] ?? false)) witnessFail('PHP server exited during startup');
            usleep(1000);
        } while (microtime(true) < $deadline);
        if ($probe === false) witnessFail('PHP server did not listen');
        $socket = stream_socket_client("tcp://127.0.0.1:$port", $error, $message, 3);
        if ($socket === false) witnessFail('connect asset request');
        fwrite($socket, "GET /pilot/assets/payload.css HTTP/1.1\r\nHost: assets.example\r\nConnection: close\r\n\r\n");
        stream_socket_shutdown($socket, STREAM_SHUT_WR);
        stream_set_timeout($socket, 5);
        $deadline = microtime(true) + 2;
        $heldFd = null;
        do {
            [$fd, $lateOpen] = witnessFiles($pid, $entry, $payload);
            if ($fd !== null && !$lateOpen) {
                if (!(proc_get_status($process)['running'] ?? false) || !posix_kill($pid, SIGSTOP)) witnessFail('stop owned PHP server');
                $stopped = true;
                $stopDeadline = microtime(true) + .5;
                while (witnessState($pid) !== 'T' && microtime(true) < $stopDeadline) usleep(100);
                if (witnessState($pid) !== 'T') witnessFail('server did not enter stopped state');
                [$heldFd, $lateOpen] = witnessFiles($pid, $entry, $payload);
                if ($heldFd !== null && !$lateOpen) break;
                return null; // Missed phase: no assertion about an unwitnessed response.
            }
            $read = [$socket]; $write = []; $except = [];
            if (stream_select($read, $write, $except, 0, 0) > 0) return null;
            if (!(proc_get_status($process)['running'] ?? false)) witnessFail('server exited before capture');
            usleep(100);
        } while (microtime(true) < $deadline);
        if ($heldFd === null || !$stopped) return null;
        clearstatcache(true);
        $old = stat($heldFd); $pathBefore = lstat($entry);
        if ($old === false || $pathBefore === false || $old['dev'] !== $pathBefore['dev'] || $old['ino'] !== $pathBefore['ino']) witnessFail('captured root identity mismatch');
        $replacement = (string) file_get_contents($entry)."\n/* persistent witnessed replacement */\n";
        if (file_put_contents($entry.'.next', $replacement) === false || !chmod($entry.'.next', 0600) || !rename($entry.'.next', $entry)) witnessFail('persistent replacement');
        clearstatcache(true);
        $new = lstat($entry); $held = stat($heldFd);
        if ($new === false || $held === false || $new['ino'] === $old['ino'] || $new['size'] === $old['size'] || $new['mode'] === $old['mode'] || $held['ino'] !== $old['ino'] || $held['dev'] !== $old['dev']) witnessFail('replacement did not preserve the old held descriptor and change the pathname');
        if (!posix_kill($pid, SIGCONT)) witnessFail('resume owned PHP server');
        $stopped = false;
        $wire = '';
        while (!feof($socket)) {
            $chunk = fread($socket, 65536);
            if ($chunk === false || (stream_get_meta_data($socket)['timed_out'] ?? false)) witnessFail('asset response timeout');
            $wire .= $chunk;
            if (strlen($wire) > 9 * 1024 * 1024) witnessFail('asset response exceeded bound');
        }
        return ['witness'=>['attempt'=>$attempt,'rootOpenBeforePayload'=>true,'serverStopped'=>true,'oldDescriptorHeld'=>true,'oldInode'=>$old['ino'],'newInode'=>$new['ino'],'oldSize'=>$old['size'],'newSize'=>$new['size'],'oldMode'=>$old['mode'],'newMode'=>$new['mode']], 'wire'=>$wire];
    } finally {
        if ($stopped && $pid > 0) @posix_kill($pid, SIGCONT);
        if (is_resource($socket)) fclose($socket);
        if (is_resource($process)) {
            if (proc_get_status($process)['running'] ?? false) proc_terminate($process);
            $until = microtime(true) + 1;
            while ((proc_get_status($process)['running'] ?? false) && microtime(true) < $until) usleep(1000);
            if (proc_get_status($process)['running'] ?? false) proc_terminate($process, SIGKILL);
            foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
            proc_close($process);
        }
        witnessRemove($directory);
    }
}
$repository = realpath($argv[1] ?? '');
$base = sys_get_temp_dir().'/fm2-css-capture-'.bin2hex(random_bytes(12));
$exitCode = 0;
try {
    if (PHP_OS_FAMILY !== 'Linux' || !is_dir('/proc/self/fd') || !function_exists('posix_kill') || !is_executable('/usr/bin/nice') || $repository === false || !is_file("$repository/public/router.php")) witnessFail('Linux/PHP/source preconditions');
    if (!mkdir($base, 0700)) witnessFail('create owned root');
    $result = null;
    for ($attempt = 1; $attempt <= 12; $attempt++) {
        $result = witnessAttempt($repository, "$base/attempt-$attempt", $attempt);
        if ($result !== null) break;
    }
    if ($result === null) witnessFail('could not observe root descriptor before payload capture');
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $error) {
    fwrite(STDERR, substr(str_replace(["\r","\n"], ' ', $error->getMessage()), 0, 300)."\n");
    $exitCode = 2;
} finally { witnessRemove($base); }
exit($exitCode);
