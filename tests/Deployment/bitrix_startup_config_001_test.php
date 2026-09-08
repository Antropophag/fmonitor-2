<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Workforce\WorkerConfiguration;

// Specification: BITRIX-STARTUP-CONFIG-001 v0.1.

function bscRun(array $argv, ?string $cwd = null, ?array $environment = null): array
{
    $pipes = [];
    $process = proc_open($argv, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $cwd, $environment);
    if (!is_resource($process)) {
        throw new TestFailure('command must start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function bscWrite(string $path, string $contents, int $mode = 0600): void
{
    if (file_put_contents($path, $contents) !== strlen($contents) || !chmod($path, $mode)) {
        throw new RuntimeException('fixture write failed');
    }
}

function bscRemoveTree(string $path): void
{
    if (!is_dir($path)) {
        @unlink($path);
        return;
    }
    foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
        bscRemoveTree($path . '/' . $entry);
    }
    @rmdir($path);
}

$root = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/fm2-bitrix-startup-' . bin2hex(random_bytes(6));
if (!mkdir($tmp, 0700)) {
    throw new RuntimeException('fixture setup failed');
}

try {
    $input = $tmp . '/input.env';
    $outputDir = $tmp . '/private';
    $output = $outputDir . '/bitrix.json';
    $cli = $root . '/bin/fmonitor2-prepare-bitrix-config.php';
    $marker = 'MARKER_TOKEN_42';
    $validInputs = [
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[72,71]\n",
        "# comment\nIGNORED=value\nFMONITOR_BITRIX_WEBHOOK_URL='https://example.invalid/rest/7/{$marker}/'\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[72,71]'\n",
        "\nFMONITOR_BITRIX_WEBHOOK_URL=\"https://example.invalid/rest/7/{$marker}/\"\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=\"[72,71]\"\nOTHER_ENV=literal\n",
    ];
    foreach ($validInputs as $index => $contents) {
        bscWrite($input, $contents);
        $result = bscRun([PHP_BINARY, $cli, $input, $output], $root);
        assertSameValue(0, $result['exit'], "valid env form {$index} succeeds");
        assertSameValue(false, str_contains($result['stdout'] . $result['stderr'], $marker), 'successful output does not leak token');
        $parsed = WorkerConfiguration::fromFile($output);
        assertSameValue(['https://example.invalid', 7, [71, 72]], [$parsed['origin'], $parsed['webhookUserId'], $parsed['departmentIds']], 'output reuses worker validation and canonical departments');
        clearstatcache(true, $output);
        assertSameValue(0600, fileperms($output) & 0777, 'private file mode');
        assertSameValue(0700, fileperms($outputDir) & 0777, 'private directory mode');
    }
    $beforeRepeat = file_get_contents($output);
    $repeat = bscRun([PHP_BINARY, $cli, $input, $output], $root);
    assertSameValue([0, $beforeRepeat], [$repeat['exit'], file_get_contents($output)], 'repeat is successful and equivalent');

    $invalidInputs = [
        '',
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\n",
        "FMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/8/OTHER/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL='https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=http://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=not-json\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[\"\\u0037\\u0031\"]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[0]\n",
        "FMONITOR_BITRIX_WEBHOOK_URL=https://example.invalid/rest/7/{$marker}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71,71]\n",
    ];
    foreach ($invalidInputs as $index => $contents) {
        $stale = '{"baseUrl":"https://stale.invalid/rest/9/STALE_SECRET/","departments":[99]}';
        bscWrite($output, $stale);
        bscWrite($input, $contents);
        $result = bscRun([PHP_BINARY, $cli, $input, $output], $root);
        assertSameValue(true, $result['exit'] !== 0, "invalid env {$index} fails");
        assertSameValue($stale, file_get_contents($output), "invalid env {$index} preserves stale file byte-for-byte");
        $diagnostic = $result['stdout'] . $result['stderr'];
        assertSameValue(false, str_contains($diagnostic, $marker) || str_contains($diagnostic, 'STALE_SECRET') || str_contains($diagnostic, 'https://'), "invalid env {$index} diagnostic is secret-safe");
        assertSameValue(true, str_contains($diagnostic, 'FMONITOR_BITRIX_') || str_contains($diagnostic, '.env'), "invalid env {$index} diagnostic identifies remediation");
    }
    $missing = bscRun([PHP_BINARY, $cli, $tmp . '/absent.env', $output], $root);
    assertSameValue(true, $missing['exit'] !== 0, 'absent env path fails');
    assertSameValue(false, str_contains($missing['stdout'] . $missing['stderr'], $marker) || str_contains($missing['stdout'] . $missing['stderr'], 'https://'), 'absent env diagnostic is secret-safe');

    chmod($outputDir, 0777);
    bscWrite($input, $validInputs[0]);
    $remode = bscRun([PHP_BINARY, $cli, $input, $output], $root);
    clearstatcache(true, $outputDir);
    assertSameValue([0, 0700], [$remode['exit'], fileperms($outputDir) & 0777], 'pre-existing output directory is made private');

    $makeRoot = $tmp . '/checkout';
    if (!mkdir($makeRoot . '/fake-bin', 0700, true)) {
        throw new RuntimeException('make fixture setup failed');
    }
    copy($root . '/Makefile', $makeRoot . '/Makefile');
    bscWrite($makeRoot . '/.env', $validInputs[0]);
    $dockerLog = $makeRoot . '/docker.log';
    bscWrite($makeRoot . '/fake-bin/docker', "#!/bin/sh\nprintf '%s\\n' \"\$*\" >> \"\$BSC_DOCKER_LOG\"\ncase \"\$*\" in *fmonitor2-prepare-bitrix-config.php*) test \"\${BSC_FAIL_PREP:-0}\" = 1 && exit 86;; esac\nexit 0\n", 0700);
    bscWrite($makeRoot . '/fake-bin/php', "#!/bin/sh\necho HOST_PHP_CALLED >&2\nexit 97\n", 0700);
    $environment = $_ENV + [
        'PATH' => $makeRoot . '/fake-bin:/usr/bin:/bin',
        'BSC_DOCKER_LOG' => $dockerLog,
    ];
    $make = bscRun(['/usr/bin/make', '--no-print-directory', 'up'], $makeRoot, $environment);
    assertSameValue(0, $make['exit'], 'isolated make up succeeds through Docker only');
    assertSameValue(false, str_contains($make['stdout'] . $make['stderr'], 'HOST_PHP_CALLED'), 'make does not invoke host PHP');
    $dockerCommands = file_get_contents($dockerLog) ?: '';
    assertSameValue(false, str_contains($make['stdout'] . $make['stderr'] . $dockerCommands, $marker), 'make output and Docker argv do not leak token');
    assertSameValue(true, str_contains($dockerCommands, 'build') && str_contains($dockerCommands, 'fmonitor2-pilot'), 'make builds the preparation image without parsing .env through Compose first');
    assertSameValue(true, str_contains($dockerCommands, 'fmonitor2-prepare-bitrix-config.php'), 'make prepares Bitrix config in a container');
    assertSameValue(true, str_contains($dockerCommands, 'compose up --detach --wait'), 'make starts the ordinary stack after preparation');
    assertSameValue(true, strpos($dockerCommands, 'fmonitor2-prepare-bitrix-config.php') < strpos($dockerCommands, 'compose up --detach --wait'), 'configuration succeeds before Compose startup');
    assertSameValue(false, str_contains(file_get_contents($makeRoot . '/Makefile') ?: '', '../fmonitor') || str_contains(file_get_contents($makeRoot . '/Makefile') ?: '', 'export-legacy-bitrix-secret.php'), 'Make has no legacy configuration path');

    if (!is_dir($makeRoot . '/.local') && !mkdir($makeRoot . '/.local', 0700)) {
        throw new RuntimeException('private fixture setup failed');
    }
    $staleMakeOutput = $makeRoot . '/.local/bitrix-workforce.json';
    $staleMakeBytes = '{"baseUrl":"https://stale.invalid/rest/9/STALE_SECRET/","departments":[99]}';
    bscWrite($staleMakeOutput, $staleMakeBytes);
    bscWrite($dockerLog, '');
    $failedEnvironment = $environment + ['BSC_FAIL_PREP' => '1'];
    $failedMake = bscRun(['/usr/bin/make', '--no-print-directory', 'up'], $makeRoot, $failedEnvironment);
    assertSameValue(true, $failedMake['exit'] !== 0, 'make up propagates preparation failure');
    $failedDockerCommands = file_get_contents($dockerLog) ?: '';
    assertSameValue(false, str_contains($failedDockerCommands, 'compose up'), 'make does not start services after preparation failure');
    assertSameValue($staleMakeBytes, file_get_contents($staleMakeOutput), 'failed make preparation preserves stale private file');
    assertSameValue(false, str_contains($failedMake['stdout'] . $failedMake['stderr'] . $failedDockerCommands, $marker) || str_contains($failedMake['stdout'] . $failedMake['stderr'] . $failedDockerCommands, 'STALE_SECRET'), 'failed make output and Docker argv are secret-safe');

    $dockerignore = file_get_contents($root . '/.dockerignore') ?: '';
    assertSameValue(true, preg_match('/^\.env(?:\.\*)?$/m', $dockerignore) === 1, '.env files are excluded from Docker build context');
    assertSameValue(true, str_contains($dockerignore, '!.env.example'), 'safe example remains available to build context rules');

    echo "BITRIX-STARTUP-CONFIG-001 tests passed.\n";
} finally {
    bscRemoveTree($tmp);
}
