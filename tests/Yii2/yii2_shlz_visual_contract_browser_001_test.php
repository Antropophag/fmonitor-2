<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001 A-G: real authenticated Yii/Chromium representative seam.
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/InstallerDirectoryFixture.php';

$fixture = null;
$process = null;
$config = null;
try {
    $fixture = new InstallerDirectoryFixture(dirname(__DIR__, 2));
    $fixture->open();
    $http = $fixture->http;
    $before = $http->facts();
    $config = $http->artifacts . '/shlz-visual-contract.json';
    $result = $http->artifacts . '/shlz-visual-contract-result.json';
    file_put_contents($config, json_encode([
        'origin' => 'http://127.0.0.1:' . $http->server['port'],
        'email' => $http->emails[18],
        'password' => $http->password,
        'artifacts' => $http->artifacts,
        'result' => $result,
        'playwright' => getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE') ?: dirname($http->root) . '/shlz-ui/node_modules/playwright',
    ], JSON_THROW_ON_ERROR));
    chmod($config, 0600);
    $log = $http->artifacts . '/shlz-visual-contract-browser.log';
    $process = proc_open([
        getenv('FMONITOR_TEST_NODE_BINARY') ?: 'node',
        dirname(__DIR__) . '/Support/yii2_shlz_visual_contract_browser.cjs',
        $config,
    ], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $http->root);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE browser');
    $deadline = microtime(true) + 100;
    do {
        $state = proc_get_status($process);
        if (!$state['running']) break;
        usleep(20000);
    } while (microtime(true) < $deadline);
    if ($state['running']) throw new TestFailure('SETUP_FAILURE browser timeout ' . $http->artifacts);
    $exit = $state['exitcode'];
    proc_close($process);
    $process = null;
    assertSameValue(0, $exit, 'INTENDED_RED shared Yii visual browser contract; ' . file_get_contents($log) . ' evidence ' . $http->artifacts);
    $observed = json_decode((string) file_get_contents($result), true, flags: JSON_THROW_ON_ERROR);
    assertSameValue([320, 360, 390, 768, 800, 1024, 1280, 1366, 1440, 1536, 1920], array_keys($observed['viewports']), 'owner device matrix and stress widths captured');
    foreach ($observed['screenshots'] as $width => $shot) {
        assertSameValue(true, is_file($shot['path']) && hash_file('sha256', $shot['path']) === $shot['sha256'], 'reviewable screenshot ' . $width);
    }
    assertSameValue($before, $http->facts(), 'visual reads preserve all fixture facts');
    echo 'PASS: YII2-SHLZ-VISUAL-CONTRACT-001 browser; artifacts ' . $http->artifacts . "\n";
} finally {
    if (is_resource($process)) {
        proc_terminate($process, 9);
        proc_close($process);
    }
    if ($config !== null && is_file($config)) unlink($config);
    if ($fixture instanceof InstallerDirectoryFixture) $fixture->close();
}
