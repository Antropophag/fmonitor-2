<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 v0.1, Gate 2 RED. */

function cipuConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1', 'PORT'=>'23306', 'NAME'=>'fmonitor2_test', 'USER'=>'fmonitor2_test', 'PASSWORD'=>'fmonitor2_test_local'] as $suffix => $default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = $verify !== false && $verify !== '' ? $verify : ($test !== false && $test !== '' ? $test : $default);
    }
    return $config;
}

function cipuDb(array $config): mysqli
{
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int) $config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $exception) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage());
    }
}

function cipuRun(string $root, array $environment): array
{
    $process = proc_open(
        [PHP_BINARY, 'rapid-pilot/verify-checklist-photo-upload.php'],
        [0=>['pipe', 'r'], 1=>['pipe', 'w'], 2=>['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: photo-upload verifier process did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['status'=>proc_close($process), 'stdout'=>$stdout, 'stderr'=>$stderr];
}

function cipuSafeRunToken(string $token): string
{
    if (preg_match('/\\A[a-f0-9]{12}\\z/D', $token) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe photo verifier run token');
    }
    return $token;
}

function cipuOwnedTables(mysqli $db, string $token, array &$discovered): array
{
    $prefix = 'pu_' . cipuSafeRunToken($token) . '_';
    $escapedPrefix = str_replace(['\\\\', '_', '%'], ['\\\\\\\\', '\\_', '\\%'], $prefix);
    $statement = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' ORDER BY TABLE_NAME");
    $statement->bind_param('s', $escapedPrefix);
    $statement->execute();
    $result = $statement->get_result();
    $tables = array_column($result->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    foreach ($tables as $table) {
        if (!is_string($table) || !str_starts_with($table, $prefix) || preg_match('/\\Apu_[a-f0-9]{12}_[A-Za-z0-9_]+\\z/D', $table) !== 1) {
            throw new TestFailure('REGRESSION_FAILURE: unsafe owned photo verifier table name discovered');
        }
        $discovered[$table] = true;
    }
    return $tables;
}

function cipuDropDiscoveredTables(mysqli $db, array $discovered): void
{
    foreach (array_keys($discovered) as $table) {
        if (!is_string($table) || preg_match('/\\Apu_[a-f0-9]{12}_[A-Za-z0-9_]+\\z/D', $table) !== 1) {
            continue;
        }
        $db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }
}

function cipuTableState(mysqli $db, string $table): array
{
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    return [
        'definition'=>$db->query("SHOW CREATE TABLE $quoted")->fetch_row()[1],
        'rows'=>$db->query("SELECT decoy_key,decoy_value FROM $quoted ORDER BY decoy_key")->fetch_all(MYSQLI_ASSOC),
    ];
}

function cipuTableNames(mysqli $db): array
{
    $tables = array_column(
        $db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC),
        'TABLE_NAME',
    );
    return array_values(array_filter(
        $tables,
        static fn (mixed $table): bool => !is_string($table)
            || (
                preg_match('/\\Apu_[a-f0-9]{12}_[A-Za-z0-9_]+\\z/D', $table) !== 1
                && preg_match('/\\Acharacterize_photo_upload_decoy_[a-f0-9]{16}\\z/D', $table) !== 1
            ),
    ));
}

function cipuArtifactState(string $root): array
{
    if (!is_dir($root)) {
        return [];
    }
    $state = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        $relative = substr($entry->getPathname(), strlen($root) + 1);
        $state[$relative] = $entry->isDir()
            ? ['type'=>'directory']
            : ['type'=>'file', 'sha256'=>hash_file('sha256', $entry->getPathname()), 'size'=>$entry->getSize()];
    }
    ksort($state);
    return $state;
}

function cipuOwnedArtifactState(string $artifactRoot, string $token): array
{
    return cipuArtifactState($artifactRoot . '/photo-verify-' . cipuSafeRunToken($token));
}

function cipuRemoveTree(string $root): void
{
    if (is_link($root) || is_file($root)) {
        unlink($root);
        return;
    }
    if (!is_dir($root)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($root);
}


function cipuRemoveOwnedArtifacts(string $artifactRoot, array $tokens): void
{
    foreach ($tokens as $token) {
        cipuRemoveTree($artifactRoot . '/photo-verify-' . cipuSafeRunToken($token));
    }
}

$root = dirname(__DIR__, 2);
$config = cipuConfig();
$artifactParent = $root . '/.local/test-artifacts';
$artifactRoot = $artifactParent . '/characterize-photo-upload-' . bin2hex(random_bytes(8));
$decoyTable = 'characterize_photo_upload_decoy_' . bin2hex(random_bytes(8));
$foreignToken = bin2hex(random_bytes(6));
$foreignTable = 'pu_' . $foreignToken . '_foreign_decoy';
$foreignArtifactRoot = $artifactRoot . '/photo-verify-' . $foreignToken;
$runTokens = [
    'first'=>bin2hex(random_bytes(6)),
    'second'=>bin2hex(random_bytes(6)),
    'unavailable'=>bin2hex(random_bytes(6)),
    'sql-collision'=>bin2hex(random_bytes(6)),
    'storage-collision'=>bin2hex(random_bytes(6)),
    'unsafe-tmp'=>bin2hex(random_bytes(6)),
    'unsafe-dotdot'=>bin2hex(random_bytes(6)),
    'unsafe-symlink'=>bin2hex(random_bytes(6)),
];
$db = null;
$decoyCreated = false;
$foreignCreated = false;
$discoveredTables = [];
$pathSafetySymlink = null;
$failureMessage = null;
$exitStatus = 0;

try {
    if (!is_dir($artifactRoot) && !mkdir($artifactRoot, 0700, true) && !is_dir($artifactRoot)) {
        throw new TestFailure("SETUP_FAILURE: cannot create private artifact root $artifactRoot");
    }
    $decoyBytes = "CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001\0ambient\xffdecoy";
    if (file_put_contents($artifactRoot . '/ambient-decoy.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create ambient filesystem decoy');
    }
    if (!mkdir($foreignArtifactRoot, 0700) || file_put_contents($foreignArtifactRoot . '/foreign.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create foreign photo verifier filesystem decoy');
    }

    $approvedPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    $approvedPng = base64_decode($approvedPngBase64, true);
    assertSameValue(true, is_string($approvedPng), 'Approved PNG fixture must be valid strict base64');
    assertSameValue(68, strlen($approvedPng), 'Approved PNG fixture must retain its independently specified byte size');
    assertSameValue('image/png', (new finfo(FILEINFO_MIME_TYPE))->buffer($approvedPng), 'Approved PNG fixture must retain its independently specified MIME');
    assertSameValue('section-3-evidence.png', basename('section-3-evidence.png'), 'Approved PNG fixture must retain its independently specified original filename');
    assertSameValue('431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460', hash('sha256', $approvedPng), 'Approved PNG fixture must retain its independently specified SHA-256');

    $db = cipuDb($config);
    $db->query("CREATE TABLE `$decoyTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $decoyCreated = true;
    $statement = $db->prepare("INSERT INTO `$decoyTable`(decoy_key,decoy_value) VALUES(?,?)");
    $decoyKey = 'ambient-state';
    $statement->bind_param('ss', $decoyKey, $decoyBytes);
    $statement->execute();
    $db->query("CREATE TABLE `$foreignTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $foreignCreated = true;
    $foreignStatement = $db->prepare("INSERT INTO `$foreignTable`(decoy_key,decoy_value) VALUES(?,?)");
    $foreignStatement->bind_param('ss', $decoyKey, $decoyBytes);
    $foreignStatement->execute();
    $beforeDb = cipuTableState($db, $decoyTable);
    $beforeForeignDb = cipuTableState($db, $foreignTable);
    $beforeArtifacts = cipuArtifactState($artifactRoot);
    $beforeForeignArtifacts = cipuArtifactState($foreignArtifactRoot);
    foreach ($runTokens as $token) {
        assertSameValue([], cipuOwnedTables($db, $token, $discoveredTables), 'SETUP_FAILURE: owned photo verifier SQL namespace must be clean before use');
        assertSameValue([], cipuOwnedArtifactState($artifactRoot, $token), 'SETUP_FAILURE: owned photo verifier filesystem namespace must be clean before use');
    }

    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    foreach ($config as $suffix => $value) {
        $environment["FMONITOR_VERIFY_DB_$suffix"] = (string) $value;
    }
    $environment['FMONITOR_PHOTO_VERIFY_ARTIFACT_ROOT'] = $artifactRoot;

    $invalidRunTokens = [
        'missing'=>null,
        'short-11-lowerhex'=>'0123456789a',
        'uppercase-12-hex'=>'0123456789AB',
        'separator-nonhex'=>'01234-6789ag',
    ];
    foreach ($invalidRunTokens as $probeName => $rawToken) {
        $invalidEnvironment = $environment;
        unset($invalidEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN']);
        if (is_string($rawToken)) {
            $invalidEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $rawToken;
        }
        $beforeInvalidTables = cipuTableNames($db);
        $beforeInvalidArtifacts = cipuArtifactState($artifactRoot);
        $invalid = cipuRun($root, $invalidEnvironment);
        $invalidEvidence = json_encode(
            ['probe'=>$probeName, 'token'=>$rawToken, 'result'=>$invalid],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        assertSameValue(2, $invalid['status'], "Invalid run-token probe must use setup exit 2; evidence=$invalidEvidence");
        assertSameValue('', $invalid['stdout'], "Invalid run-token probe must keep stdout empty; evidence=$invalidEvidence");
        assertSameValue(
            "SETUP_FAILURE: photo verifier run token is invalid\n",
            $invalid['stderr'],
            "Invalid run-token probe must emit the exact stable SETUP_FAILURE; evidence=$invalidEvidence",
        );
        assertSameValue($beforeInvalidTables, cipuTableNames($db), "Invalid run-token probe must not add or remove SQL tables; probe=$probeName");
        assertSameValue($beforeDb, cipuTableState($db, $decoyTable), "Invalid run-token probe must preserve the ambient SQL decoy; probe=$probeName");
        assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), "Invalid run-token probe must preserve the foreign photo verifier SQL namespace; probe=$probeName");
        assertSameValue($beforeInvalidArtifacts, cipuArtifactState($artifactRoot), "Invalid run-token probe must not mutate storage; probe=$probeName");
        assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), "Invalid run-token probe must preserve the foreign photo verifier filesystem namespace; probe=$probeName");
    }

    $sqlCollisionToken = $runTokens['sql-collision'];
    $sqlCollisionTable = 'pu_' . $sqlCollisionToken . '_collision_decoy';
    $db->query("CREATE TABLE `$sqlCollisionTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $sqlCollisionStatement = $db->prepare("INSERT INTO `$sqlCollisionTable`(decoy_key,decoy_value) VALUES(?,?)");
    $sqlCollisionStatement->bind_param('ss', $decoyKey, $decoyBytes);
    $sqlCollisionStatement->execute();
    $discoveredTables[$sqlCollisionTable] = true;
    $beforeSqlCollision = cipuTableState($db, $sqlCollisionTable);
    $sqlCollisionEnvironment = $environment;
    $sqlCollisionEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $sqlCollisionToken;
    $sqlCollision = cipuRun($root, $sqlCollisionEnvironment);
    $sqlCollisionEvidence = json_encode($sqlCollision, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(2, $sqlCollision['status'], "Occupied SQL namespace must use setup exit 2; evidence=$sqlCollisionEvidence");
    assertSameValue('', $sqlCollision['stdout'], "Occupied SQL namespace must not emit a success transcript; evidence=$sqlCollisionEvidence");
    assertSameValue("SETUP_FAILURE: photo verifier owned namespace is occupied\n", $sqlCollision['stderr'], "Occupied SQL namespace must emit the exact stable SETUP_FAILURE; evidence=$sqlCollisionEvidence");
    assertSameValue([$sqlCollisionTable], cipuOwnedTables($db, $sqlCollisionToken, $discoveredTables), 'SQL collision probe must preserve only its pre-existing exact owned table');
    assertSameValue($beforeSqlCollision, cipuTableState($db, $sqlCollisionTable), 'SQL collision probe must preserve its pre-existing definition and row byte-for-byte');
    assertSameValue([], cipuOwnedArtifactState($artifactRoot, $sqlCollisionToken), 'SQL collision probe must fail before creating its owned storage child');
    assertSameValue($beforeDb, cipuTableState($db, $decoyTable), 'SQL collision probe must preserve the ambient SQL decoy');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'SQL collision probe must preserve the foreign valid-token SQL namespace');
    assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), 'SQL collision probe must preserve the foreign valid-token storage namespace');
    $db->query("DROP TABLE `$sqlCollisionTable`");
    assertSameValue([], cipuOwnedTables($db, $sqlCollisionToken, $discoveredTables), 'SQL collision fixture cleanup must restore its exact owned SQL namespace to the pre-collision baseline');
    assertSameValue($beforeDb, cipuTableState($db, $decoyTable), 'SQL collision fixture cleanup must preserve the ambient SQL baseline');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'SQL collision fixture cleanup must preserve the foreign valid-token SQL baseline');
    assertSameValue($beforeArtifacts, cipuArtifactState($artifactRoot), 'SQL collision fixture cleanup must restore the complete artifact baseline before later probes');

    $storageCollisionToken = $runTokens['storage-collision'];
    $storageCollisionRoot = $artifactRoot . '/photo-verify-' . $storageCollisionToken;
    if (!mkdir($storageCollisionRoot, 0700) || file_put_contents($storageCollisionRoot . '/collision.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create exact owned storage collision fixture');
    }
    $beforeStorageCollision = cipuArtifactState($storageCollisionRoot);
    $storageCollisionEnvironment = $environment;
    $storageCollisionEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $storageCollisionToken;
    $storageCollision = cipuRun($root, $storageCollisionEnvironment);
    $storageCollisionEvidence = json_encode($storageCollision, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(2, $storageCollision['status'], "Occupied storage namespace must use setup exit 2; evidence=$storageCollisionEvidence");
    assertSameValue('', $storageCollision['stdout'], "Occupied storage namespace must not emit a success transcript; evidence=$storageCollisionEvidence");
    assertSameValue("SETUP_FAILURE: photo verifier owned namespace is occupied\n", $storageCollision['stderr'], "Occupied storage namespace must emit the exact stable SETUP_FAILURE; evidence=$storageCollisionEvidence");
    assertSameValue([], cipuOwnedTables($db, $storageCollisionToken, $discoveredTables), 'Storage collision probe must fail before creating any owned SQL table');
    assertSameValue($beforeStorageCollision, cipuArtifactState($storageCollisionRoot), 'Storage collision probe must preserve its pre-existing directory and file byte-for-byte');
    assertSameValue($beforeDb, cipuTableState($db, $decoyTable), 'Storage collision probe must preserve the ambient SQL decoy');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'Storage collision probe must preserve the foreign valid-token SQL namespace');
    assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), 'Storage collision probe must preserve the foreign valid-token storage namespace');
    cipuRemoveTree($storageCollisionRoot);
    assertSameValue([], cipuOwnedArtifactState($artifactRoot, $storageCollisionToken), 'Storage collision fixture cleanup must restore its exact owned filesystem namespace to the pre-collision baseline');
    assertSameValue([], cipuOwnedTables($db, $storageCollisionToken, $discoveredTables), 'Storage collision fixture cleanup must preserve the pre-collision SQL baseline');
    assertSameValue($beforeDb, cipuTableState($db, $decoyTable), 'Storage collision fixture cleanup must preserve the ambient SQL baseline');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'Storage collision fixture cleanup must preserve the foreign valid-token SQL baseline');
    assertSameValue($beforeArtifacts, cipuArtifactState($artifactRoot), 'Storage collision fixture cleanup must restore the complete artifact baseline before later probes');

    $pathSafetySymlink = $artifactParent . '/characterize-photo-upload-symlink-' . bin2hex(random_bytes(8));
    if (!symlink($artifactRoot, $pathSafetySymlink)) {
        throw new TestFailure('SETUP_FAILURE: cannot create repository-local artifact-root symlink probe');
    }
    $unsafeRoots = [
        'forbidden-tmp-root'=>['root'=>'/tmp', 'token'=>$runTokens['unsafe-tmp']],
        'noncanonical-dot-dot-root'=>['root'=>$root . '/.local/test-artifacts/../test-artifacts/' . basename($artifactRoot), 'token'=>$runTokens['unsafe-dotdot']],
        'repository-local-symlink-root'=>['root'=>$pathSafetySymlink, 'token'=>$runTokens['unsafe-symlink']],
    ];
    foreach ($unsafeRoots as $probeName => $probe) {
        $unsafeEnvironment = $environment;
        $unsafeEnvironment['FMONITOR_PHOTO_VERIFY_ARTIFACT_ROOT'] = $probe['root'];
        $unsafeEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $probe['token'];
        $unsafe = cipuRun($root, $unsafeEnvironment);
        $unsafeEvidence = json_encode(
            ['probe'=>$probeName, 'result'=>$unsafe],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        assertSameValue(2, $unsafe['status'], "Unsafe artifact-root probe must use setup exit 2 before touching SQL or storage; evidence=$unsafeEvidence");
        assertSameValue('', $unsafe['stdout'], "Unsafe artifact-root probe must not emit a success transcript; evidence=$unsafeEvidence");
        assertSameValue(
            "SETUP_FAILURE: supplied photo artifact root is unsafe\n",
            $unsafe['stderr'],
            "Unsafe artifact-root probe must emit exactly one stable SETUP_FAILURE line; evidence=$unsafeEvidence",
        );
        assertSameValue([], cipuOwnedTables($db, $probe['token'], $discoveredTables), "Unsafe artifact-root probe must be rejected before creating owned SQL tables; probe=$probeName");
        assertSameValue($beforeDb, cipuTableState($db, $decoyTable), "Unsafe artifact-root probe must preserve the ambient SQL decoy; probe=$probeName");
        assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), "Unsafe artifact-root probe must preserve the foreign photo verifier SQL namespace; probe=$probeName");
        assertSameValue($beforeArtifacts, cipuArtifactState($artifactRoot), "Unsafe artifact-root probe must be rejected before mutating private storage or the ambient filesystem decoy; probe=$probeName");
        assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), "Unsafe artifact-root probe must preserve the foreign photo verifier filesystem namespace; probe=$probeName");
        assertSameValue([], cipuOwnedArtifactState($artifactRoot, $probe['token']), "Unsafe artifact-root probe must not create its owned filesystem namespace; probe=$probeName");
    }

    $environment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $runTokens['first'];
    $first = cipuRun($root, $environment);
    $afterFirstTables = cipuOwnedTables($db, $runTokens['first'], $discoveredTables);
    $afterFirstDb = cipuTableState($db, $decoyTable);
    $afterFirstArtifacts = cipuArtifactState($artifactRoot);
    $afterFirstPrivateArtifacts = cipuOwnedArtifactState($artifactRoot, $runTokens['first']);
    assertSameValue([], $afterFirstTables, 'First verifier run must remove every table in its exact owned SQL namespace');
    assertSameValue([], $afterFirstPrivateArtifacts, 'First verifier run must remove its exact owned filesystem namespace');
    assertSameValue($beforeDb, $afterFirstDb, 'First verifier run must preserve the ambient SQL decoy byte-for-byte');
    assertSameValue($beforeArtifacts, $afterFirstArtifacts, 'First verifier run must preserve only the ambient filesystem decoy');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'First verifier run must preserve the foreign photo verifier SQL namespace');
    assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), 'First verifier run must preserve the foreign photo verifier filesystem namespace');
    $environment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $runTokens['second'];
    $second = cipuRun($root, $environment);
    $afterSecondTables = cipuOwnedTables($db, $runTokens['second'], $discoveredTables);
    $afterSecondDb = cipuTableState($db, $decoyTable);
    $afterSecondArtifacts = cipuArtifactState($artifactRoot);
    $afterSecondPrivateArtifacts = cipuOwnedArtifactState($artifactRoot, $runTokens['second']);
    assertSameValue([], $afterSecondTables, 'Repeated verifier run must remove every table in its exact owned SQL namespace');
    assertSameValue([], $afterSecondPrivateArtifacts, 'Repeated verifier run must remove its exact owned filesystem namespace');
    assertSameValue($beforeDb, $afterSecondDb, 'Repeated verifier run must preserve the ambient SQL decoy byte-for-byte');
    assertSameValue($beforeArtifacts, $afterSecondArtifacts, 'Repeated verifier run must preserve only the ambient filesystem decoy');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'Repeated verifier run must preserve the foreign photo verifier SQL namespace');
    assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), 'Repeated verifier run must preserve the foreign photo verifier filesystem namespace');

    $milestones =
        "PHOTO_UPLOAD accepted revision=1 active=1 blob_sha256=431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460\n"
        . "PHOTO_UPLOAD replay duplicate revision=1 active=1\n"
        . "PHOTO_UPLOAD storage-unavailable retryable revision=0 active=0\n";
    $expectedStdout = $milestones
        . "CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 transcript_sha256=7603606ad948a2bf464ccb02ee5e797c4daeb6580ca9b0fd85e07fb102d5067d\n";
    $evidence = json_encode([
        'first'=>['status'=>$first['status'], 'stdout'=>$first['stdout'], 'stderr'=>$first['stderr']],
        'second'=>['status'=>$second['status'], 'stdout'=>$second['stdout'], 'stderr'=>$second['stderr']],
        'privateTables'=>['first'=>$afterFirstTables, 'second'=>$afterSecondTables],
        'artifactState'=>['before'=>$beforeArtifacts, 'first'=>$afterFirstArtifacts, 'second'=>$afterSecondArtifacts],
        'ownedArtifacts'=>['first'=>$afterFirstPrivateArtifacts, 'second'=>$afterSecondPrivateArtifacts],
        'foreignArtifacts'=>$beforeForeignArtifacts,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue(0, $first['status'], "RED_ASSERTION: missing public photo-upload characterization verifier must become a successful first run; evidence=$evidence");
    assertSameValue('', $first['stderr'], "First verifier run must keep stderr empty; evidence=$evidence");
    assertSameValue($expectedStdout, $first['stdout'], "First verifier run must emit only the exact ordered milestone transcript and its independently fixed terminal hash; evidence=$evidence");
    assertSameValue(0, $second['status'], "Repeated public photo-upload characterization verifier run must succeed; evidence=$evidence");
    assertSameValue('', $second['stderr'], "Repeated verifier run must keep stderr empty; evidence=$evidence");
    assertSameValue($first['stdout'], $second['stdout'], "Repeated verifier runs must emit byte-identical stdout; evidence=$evidence");
    assertSameValue($expectedStdout, $second['stdout'], "Repeated verifier run must retain the exact ordered milestone transcript; evidence=$evidence");
    $unavailableEnvironment = $environment;
    $unavailableEnvironment['FMONITOR_PHOTO_VERIFY_RUN_TOKEN'] = $runTokens['unavailable'];
    $unavailableEnvironment['FMONITOR_VERIFY_DB_HOST'] = '127.0.0.1';
    $unavailableEnvironment['FMONITOR_VERIFY_DB_PORT'] = '1';
    $unavailable = cipuRun($root, $unavailableEnvironment);
    $categoryEvidence = json_encode($unavailable, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(2, $unavailable['status'], "Unavailable disposable DB must use setup exit 2; evidence=$categoryEvidence");
    assertSameValue('', $unavailable['stdout'], "Setup failure must not emit a success transcript; evidence=$categoryEvidence");
    assertSameValue(1, preg_match('/\\ASETUP_FAILURE: [^\\r\\n]+\\n\\z/D', $unavailable['stderr']), "Unavailable disposable DB must emit one SETUP_FAILURE line; evidence=$categoryEvidence");
    assertSameValue([], cipuOwnedTables($db, $runTokens['unavailable'], $discoveredTables), 'Setup-failure probe must not leave its owned SQL tables');
    assertSameValue($beforeDb, cipuTableState($db, $decoyTable), 'Setup-failure probe must preserve the ambient SQL decoy');
    assertSameValue($beforeArtifacts, cipuArtifactState($artifactRoot), 'Setup-failure probe must preserve the ambient filesystem decoy and leave no private storage');
    $afterUnavailablePrivateArtifacts = cipuOwnedArtifactState($artifactRoot, $runTokens['unavailable']);
    assertSameValue([], $afterUnavailablePrivateArtifacts, 'Setup-failure probe must leave no artifact in its exact owned namespace');
    assertSameValue($beforeForeignDb, cipuTableState($db, $foreignTable), 'Setup-failure probe must preserve the foreign photo verifier SQL namespace');
    assertSameValue($beforeForeignArtifacts, cipuArtifactState($foreignArtifactRoot), 'Setup-failure probe must preserve the foreign photo verifier filesystem namespace');

    echo "ok - CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 public oracle is deterministic, isolated, and correctly classified\n";
} catch (TestFailure $exception) {
    $failureMessage = $exception->getMessage();
    $exitStatus = str_starts_with($failureMessage, 'SETUP_FAILURE:') ? 2 : 1;
} catch (Throwable $exception) {
    $failureMessage = 'REGRESSION_FAILURE: ' . $exception->getMessage();
    $exitStatus = 1;
} finally {
    if ($db instanceof mysqli) {
        foreach ($runTokens as $token) {
            try {
                cipuOwnedTables($db, $token, $discoveredTables);
            } catch (Throwable) {
            }
        }
        cipuDropDiscoveredTables($db, $discoveredTables);
        if ($decoyCreated) {
            $db->query("DROP TABLE IF EXISTS `$decoyTable`");
        }
        if ($foreignCreated) {
            $db->query("DROP TABLE IF EXISTS `$foreignTable`");
        }
        $db->close();
    }
    cipuRemoveOwnedArtifacts($artifactRoot, array_values($runTokens));
    if (is_string($pathSafetySymlink) && is_link($pathSafetySymlink)) {
        unlink($pathSafetySymlink);
    }
    cipuRemoveTree($artifactRoot);
}

if ($failureMessage !== null) {
    fwrite(STDERR, $failureMessage . "\n");
    exit($exitStatus);
}
