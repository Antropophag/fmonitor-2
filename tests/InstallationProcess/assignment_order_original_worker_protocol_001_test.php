<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v52 worker FD/framing protocol.
if (!class_exists(AssignmentOrderOriginalVerificationWorkerBootstrap::class)) {
    throw new TestFailure('INTENDED_RED: approved worker protocol seam is absent.');
}

$entry = dirname(__DIR__) . '/Support/assignment_order_original_worker_entry.php';
$token = bin2hex(random_bytes(8));
$root = sys_get_temp_dir() . '/aoou-worker-protocol-' . $token;
$config = $root . '/config.json';
mkdir($root, 0700);
file_put_contents($config, "{}\n");
chmod($config, 0600);
register_shutdown_function(static function () use ($root, $config): void {
    foreach ([$root . '/fifo-fd', $config] as $file) if (is_file($file)) @unlink($file);
    if (is_dir($root . '/directory-fd')) @rmdir($root . '/directory-fd');
    if (is_dir($root)) @rmdir($root);
});

/** @return array{exit:int,stdout:string,stderr:string,commandPeer:string,barrier:string,result:string} */
$invoke = static function (array $arguments, array $descriptorOverrides = []) use ($entry, $config): array {
    $pairs = [];
    $pipes = [];
    $process = null;
    try {
        for ($index = 0; $index < 4; ++$index) {
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if ($pair === false) {
                throw new TestFailure('Protocol socketpair construction failed.');
            }
            $pairs[] = $pair;
        }
        $descriptors = [
            0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            3 => $pairs[0][1], 4 => $pairs[1][1], 5 => $pairs[2][1], 6 => $pairs[3][1],
        ];
        foreach ($descriptorOverrides as $fd => $descriptor) {
            $descriptors[$fd] = $descriptor;
        }
        $process = proc_open([PHP_BINARY, $entry, $config, ...$arguments], $descriptors, $pipes, dirname(__DIR__, 2));
        if (!is_resource($process)) {
            throw new TestFailure('Protocol worker construction failed.');
        }
        foreach ($pairs as $pair) {
            fclose($pair[1]);
        }
        stream_set_blocking($pairs[0][0], false);
        stream_set_blocking($pairs[2][0], false);
        stream_set_blocking($pairs[3][0], false);
        $deadline = microtime(true) + 3.0;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        if ($status['running']) {
            proc_terminate($process, 9);
            throw new TestFailure('Protocol worker exceeded bounded deadline.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        $commandPeer = stream_get_contents($pairs[0][0]);
        $barrier = stream_get_contents($pairs[2][0]);
        $result = stream_get_contents($pairs[3][0]);
        foreach ($pipes as $pipe) fclose($pipe);
        $pipes = [];
        foreach ($pairs as $pair) fclose($pair[0]);
        $pairs = [];
        $exit = $status['exitcode'];
        proc_close($process);
        $process = null;
        return compact('exit', 'stdout', 'stderr', 'commandPeer', 'barrier', 'result');
    } finally {
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        foreach ($pairs as $pair) foreach ($pair as $endpoint) if (is_resource($endpoint)) fclose($endpoint);
        if (is_resource($process)) {
            if (proc_get_status($process)['running']) proc_terminate($process, 9);
            proc_close($process);
        }
    }
};

$exactFailure = static function (array $out, string $label): void {
    assertSameValue(
        [70, '', "ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n", '', '', ''],
        [$out['exit'], $out['stdout'], $out['stderr'], $out['commandPeer'], $out['barrier'], $out['result']],
        $label . ' has exact pre-secret/no-result/no-barrier channels.',
    );
};

foreach ([
    ['-1', '4', '5', '6'], ['2', '4', '5', '6'], ['65536', '4', '5', '6'],
    ['3', '3', '5', '6'],
] as $case) {
    $exactFailure($invoke($case), 'Invalid FD vector ' . json_encode($case, JSON_THROW_ON_ERROR));
}
$exactFailure($invoke(['99', '4', '5', '6']), 'Closed command FD');
$exactFailure($invoke(['3', '4', '5', '6'], [3 => ['file', $config, 'r']]), 'Regular-file command FD');
$exactFailure($invoke(['3', '4', '5', '6'], [3 => ['file', '/dev/null', 'r+']]), 'Device command FD');
$directory = $root . '/directory-fd';
mkdir($directory, 0700);
$exactFailure($invoke(['3', '4', '5', '6'], [3 => ['file', $directory, 'r']]), 'Directory command FD');
$fifo = $root . '/fifo-fd';
if (!function_exists('posix_mkfifo') || !posix_mkfifo($fifo, 0600)) {
    throw new TestFailure('FIFO fixture construction failed.');
}
$exactFailure($invoke(['3', '4', '5', '6'], [3 => ['file', $fifo, 'r+']]), 'FIFO command FD');

// Passing the same inherited endpoint under two different descriptor numbers
// proves identity validation is `(dev,ino)`, not merely integer distinctness.
$alias = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
if ($alias === false) throw new TestFailure('Alias socketpair construction failed.');
try {
    $exactFailure($invoke(['3', '4', '5', '6'], [3 => $alias[1], 4 => $alias[1]]), 'Aliased socket identity');
} finally {
    foreach ($alias as $endpoint) if (is_resource($endpoint)) fclose($endpoint);
}

unlink($config);
unlink($fifo);
rmdir($directory);
rmdir($root);
fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_WORKER_PROTOCOL_OK\n");
