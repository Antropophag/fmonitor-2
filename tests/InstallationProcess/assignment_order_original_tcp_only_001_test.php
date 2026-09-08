<?php

declare(strict_types=1);
// Specification: ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.7 section14.
require dirname(__DIR__).'/bootstrap.php';
$base = realpath('/tmp');
if ($base === false) throw new TestFailure('Canonical temporary base unavailable');
$root = $base.'/aoou-tcp-'.bin2hex(random_bytes(8));
$socket = $root.'/s'; $password = $root.'/p';
$owned = []; $listener = null; $process = null; $pipes = [];
$identity = static function(string $path): array {
    clearstatcache(true, $path); $s = lstat($path);
    if ($s === false) throw new TestFailure('Owned fixture identity unavailable');
    return [$s['dev'], $s['ino'], $s['mode']];
};
try {
    if (!mkdir($root, 0700) || realpath($root) !== $root) throw new TestFailure('Owned temporary root unavailable');
    $owned[$root] = $identity($root);
    $file = fopen($password, 'x');
    if ($file === false) throw new TestFailure('Owned password fixture unavailable');
    try { fwrite($file, "synthetic-only\n"); } finally { fclose($file); }
    chmod($password, 0600); $owned[$password] = $identity($password);
    $listener = stream_socket_server('unix://'.$socket, $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
    if ($listener === false) throw new TestFailure('Owned listener unavailable');
    $owned[$socket] = $identity($socket); stream_set_blocking($listener, false);
    $failures = [];
    foreach (['direct' => 'localhost', 'lower' => 'localhost', 'upper' => 'LOCALHOST', 'mixed' => 'LocalHost'] as $case => $host) {
        $process = proc_open([PHP_BINARY, '-d', 'mysqli.default_socket='.$socket,
            dirname(__DIR__).'/Support/assignment_order_original_tcp_only_child.php',
            $case === 'direct' ? 'direct' : 'factory', $socket, $host, $password],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new TestFailure('Owned child unavailable');
        foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
        $out = $err = ''; $accepts = 0; $deadline = hrtime(true) + 5_000_000_000;
        do {
            $read = [$listener];
            foreach ($pipes as $pipe) if (!feof($pipe)) $read[] = $pipe;
            $write = $except = [];
            if (stream_select($read, $write, $except, 0, 100_000) === false) throw new TestFailure('Probe select failed');
            foreach ($read as $ready) {
                if ($ready === $listener) {
                    $client = stream_socket_accept($listener, 0);
                    if ($client === false) throw new TestFailure('Probe accept failed');
                    ++$accepts; fclose($client); // No greeting, no authentication payload requested.
                } else {
                    $bytes = stream_get_contents($ready, 1024);
                    if ($bytes === false) throw new TestFailure('Child output unavailable');
                    if ($ready === $pipes[1]) $out .= $bytes; else $err .= $bytes;
                }
            }
            if (strlen($out) + strlen($err) > 2048) throw new TestFailure('Child output exceeded bound');
            $status = proc_get_status($process);
            if (hrtime(true) >= $deadline) throw new TestFailure('Probe exceeded five-second bound');
        } while ($status['running'] || !feof($pipes[1]) || !feof($pipes[2]));
        $exit = $status['exitcode'];
        foreach ($pipes as $pipe) fclose($pipe); $pipes = [];
        $closed = proc_close($process); $process = null;
        if ($exit === -1) $exit = $closed;
        assertSameValue([0, '', "SETTING_OK\n".($case === 'direct' ? "CONNECT_FAILED\n" : "UNAVAILABLE\n")],
            [$exit, $err, $out], $case.' exact child protocol');
        $expected = $case === 'direct' ? 1 : 0;
        fwrite(STDOUT, $case.' expected='.$expected.' accepts='.$accepts."\n");
        if ($accepts !== $expected) $failures[] = $case;
        if ($case === 'direct') assertSameValue(1, $accepts, 'Direct control proves listener sensitivity');
    }
    assertSameValue([], $failures, 'TCP-only factory never contacts ambient Unix socket');
    fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_TCP_ONLY_OK\n");
} finally {
    if (is_resource($process)) {
        if (proc_get_status($process)['running']) proc_terminate($process, 9);
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        proc_close($process);
    }
    if (is_resource($listener)) fclose($listener);
    foreach (array_reverse($owned, true) as $path => $expected) {
        if ($identity($path) !== $expected) throw new TestFailure('Fixture cleanup identity changed');
        if ($path === $root) rmdir($path); else unlink($path);
    }
}
