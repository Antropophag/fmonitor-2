<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 v0.1, Gate 2 RED. */

function ciprConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1', 'PORT'=>'23306', 'NAME'=>'fmonitor2_test', 'USER'=>'fmonitor2_test', 'PASSWORD'=>'fmonitor2_test_local'] as $suffix => $default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = $verify !== false && $verify !== '' ? $verify : ($test !== false && $test !== '' ? $test : $default);
    }
    return $config;
}

function ciprDb(array $config): mysqli
{
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int) $config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $exception) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage());
    }
}

function ciprAdminDb(array $config): mysqli
{
    $user = getenv('FMONITOR_VERIFY_DB_ADMIN_USER');
    $password = getenv('FMONITOR_VERIFY_DB_ADMIN_PASSWORD');
    $user = $user !== false && $user !== '' ? $user : 'root';
    $password = $password !== false && $password !== '' ? $password : 'fmonitor2_test_root_local';
    try {
        $db = new mysqli($config['HOST'], $user, $password, $config['NAME'], (int) $config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $exception) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database admin is unavailable: ' . $exception->getMessage());
    }
}

function ciprRun(string $root, array $environment): array
{
    $process = proc_open(
        [PHP_BINARY, 'rapid-pilot/verify-checklist-photo-rejections.php'],
        [0=>['pipe', 'r'], 1=>['pipe', 'w'], 2=>['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: photo-rejection verifier process did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['status'=>proc_close($process), 'stdout'=>$stdout, 'stderr'=>$stderr];
}

function ciprToken(string $token): string
{
    if (preg_match('/\A[a-f0-9]{12}\z/D', $token) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe photo-rejection verifier run token');
    }
    return $token;
}

function ciprOwnedTables(mysqli $db, string $token, array &$discovered): array
{
    $prefix = 'pj_' . ciprToken($token) . '_';
    $escaped = str_replace(['\\', '_', '%'], ['\\\\', '\_', '\%'], $prefix);
    $statement = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' ORDER BY TABLE_NAME");
    $statement->bind_param('s', $escaped);
    $statement->execute();
    $tables = array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    foreach ($tables as $table) {
        if (!is_string($table) || preg_match('/\Apj_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D', $table) !== 1) {
            throw new TestFailure('REGRESSION_FAILURE: unsafe owned photo-rejection table discovered');
        }
        $discovered[$table] = true;
    }
    return $tables;
}

function ciprTableState(mysqli $db, string $table): array
{
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    return [
        'definition'=>$db->query("SHOW CREATE TABLE $quoted")->fetch_row()[1],
        'rows'=>$db->query("SELECT decoy_key,decoy_value FROM $quoted ORDER BY decoy_key")->fetch_all(MYSQLI_ASSOC),
    ];
}

function ciprArtifactState(string $root): array
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

function ciprRemoveTree(string $root): void
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

$root = dirname(__DIR__, 2);
$config = ciprConfig();
$artifactRoot = $root . '/.local/test-artifacts/characterize-photo-rejections-' . bin2hex(random_bytes(8));
$tokens = [
    'first'=>bin2hex(random_bytes(6)),
    'second'=>bin2hex(random_bytes(6)),
    'sql-collision'=>bin2hex(random_bytes(6)),
    'storage-collision'=>bin2hex(random_bytes(6)),
    'unsafe-tmp'=>bin2hex(random_bytes(6)),
    'unavailable'=>bin2hex(random_bytes(6)),
    'ddl-denied'=>bin2hex(random_bytes(6)),
];
$foreignToken = bin2hex(random_bytes(6));
$decoyTable = 'characterize_photo_rejections_decoy_' . bin2hex(random_bytes(8));
$foreignTable = 'pj_' . $foreignToken . '_foreign_decoy';
$foreignArtifacts = $artifactRoot . '/photo-reject-' . $foreignToken;
$db = null;
$adminDb = null;
$ddlDeniedUser = null;
$ddlDeniedUserCreated = false;
$discovered = [];
$failureMessage = null;
$exitStatus = 0;

try {
    if (!mkdir($artifactRoot, 0700, true) || !mkdir($foreignArtifacts, 0700)) {
        throw new TestFailure('SETUP_FAILURE: cannot create private artifact fixtures');
    }
    $decoyBytes = "CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001\0ambient\xffdecoy";
    if (file_put_contents($artifactRoot . '/ambient-decoy.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)
        || file_put_contents($foreignArtifacts . '/foreign.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create filesystem decoys');
    }

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    assertSameValue(true, is_string($png), 'Approved PNG fixture must be strict base64');
    assertSameValue(68, strlen($png), 'Approved PNG fixture must contain 68 bytes');
    assertSameValue('image/png', (new finfo(FILEINFO_MIME_TYPE))->buffer($png), 'Approved PNG fixture must be detected as image/png');
    assertSameValue('431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460', hash('sha256', $png), 'Approved PNG fixture SHA-256 must remain fixed');

    $db = ciprDb($config);
    foreach ([$decoyTable, $foreignTable] as $table) {
        $db->query("CREATE TABLE `$table`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
        $statement = $db->prepare("INSERT INTO `$table`(decoy_key,decoy_value) VALUES(?,?)");
        $key = 'ambient-state';
        $statement->bind_param('ss', $key, $decoyBytes);
        $statement->execute();
    }
    $beforeDb = ciprTableState($db, $decoyTable);
    $beforeForeignDb = ciprTableState($db, $foreignTable);
    $beforeArtifacts = ciprArtifactState($artifactRoot);
    $beforeForeignArtifacts = ciprArtifactState($foreignArtifacts);

    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    foreach ($config as $suffix => $value) {
        $environment["FMONITOR_VERIFY_DB_$suffix"] = (string) $value;
    }
    $environment['FMONITOR_PHOTO_REJECTION_VERIFY_ARTIFACT_ROOT'] = $artifactRoot;

    $runs = [];
    foreach (['first', 'second'] as $name) {
        $ownedStorageRoot = $artifactRoot . '/photo-reject-' . $tokens[$name];
        assertSameValue([], ciprOwnedTables($db, $tokens[$name], $discovered), 'SETUP_FAILURE: verifier SQL namespace must initially be empty');
        assertSameValue(false, file_exists($ownedStorageRoot), 'SETUP_FAILURE: verifier storage namespace must initially be absent');
        $environment['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $tokens[$name];
        $runs[$name] = ciprRun($root, $environment);
        assertSameValue([], ciprOwnedTables($db, $tokens[$name], $discovered), ucfirst($name) . ' run must remove its exact owned SQL namespace');
        assertSameValue([], ciprArtifactState($ownedStorageRoot), ucfirst($name) . ' run must leave no content in its exact owned storage namespace');
        assertSameValue(false, file_exists($ownedStorageRoot), ucfirst($name) . ' run must remove its exact owned storage namespace');
        assertSameValue($beforeDb, ciprTableState($db, $decoyTable), ucfirst($name) . ' run must preserve the ambient SQL decoy');
        assertSameValue($beforeForeignDb, ciprTableState($db, $foreignTable), ucfirst($name) . ' run must preserve the foreign-token SQL decoy');
        assertSameValue($beforeArtifacts, ciprArtifactState($artifactRoot), ucfirst($name) . ' run must preserve ambient and foreign-token storage');
        assertSameValue($beforeForeignArtifacts, ciprArtifactState($foreignArtifacts), ucfirst($name) . ' run must preserve foreign-token storage byte-for-byte');
    }

    $milestones =
        "PHOTO_REJECTION mime-content-mismatch rejected revision=0 active=0 blobs=0\n"
        . "PHOTO_REJECTION size-mismatch rejected revision=0 active=0 blobs=0\n"
        . "PHOTO_REJECTION hash-mismatch rejected revision=0 active=0 blobs=0\n"
        . "PHOTO_REJECTION invalid-name rejected revision=0 active=0 blobs=0\n";
    $expectedStdout = $milestones
        . "CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 transcript_sha256=d81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9\n";
    assertSameValue('d81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9', hash('sha256', $milestones), 'Specification milestone transcript hash must be independently reproducible');
    $evidence = json_encode($runs, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(0, $runs['first']['status'], "RED_ASSERTION: missing public photo-rejection characterization verifier must become a successful first run; evidence=$evidence");
    assertSameValue('', $runs['first']['stderr'], "First verifier run must keep stderr empty; evidence=$evidence");
    assertSameValue($expectedStdout, $runs['first']['stdout'], "First verifier run must emit the exact specification transcript; evidence=$evidence");
    assertSameValue(0, $runs['second']['status'], "Second verifier run must succeed; evidence=$evidence");
    assertSameValue('', $runs['second']['stderr'], "Second verifier run must keep stderr empty; evidence=$evidence");
    assertSameValue($runs['first']['stdout'], $runs['second']['stdout'], "Distinct-token runs must emit byte-identical stdout; evidence=$evidence");
    assertSameValue($expectedStdout, $runs['second']['stdout'], "Second verifier run must emit the exact specification transcript; evidence=$evidence");

    $sqlCollisionToken = $tokens['sql-collision'];
    $sqlCollisionTable = 'pj_' . $sqlCollisionToken . '_collision_decoy';
    $db->query("CREATE TABLE `$sqlCollisionTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $collisionStatement = $db->prepare("INSERT INTO `$sqlCollisionTable`(decoy_key,decoy_value) VALUES(?,?)");
    $collisionKey = 'occupied-state';
    $collisionStatement->bind_param('ss', $collisionKey, $decoyBytes);
    $collisionStatement->execute();
    $discovered[$sqlCollisionTable] = true;
    $beforeSqlCollision = ciprTableState($db, $sqlCollisionTable);
    $sqlCollisionEnvironment = $environment;
    $sqlCollisionEnvironment['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $sqlCollisionToken;
    $sqlCollision = ciprRun($root, $sqlCollisionEnvironment);
    assertSameValue(2, $sqlCollision['status'], 'Occupied SQL namespace must be classified as setup failure');
    assertSameValue('', $sqlCollision['stdout'], 'Occupied SQL namespace must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $sqlCollision['stderr']), 'Occupied SQL namespace must emit one SETUP_FAILURE line');
    assertSameValue([$sqlCollisionTable], ciprOwnedTables($db, $sqlCollisionToken, $discovered), 'Occupied SQL namespace must remain present and must not grow');
    assertSameValue($beforeSqlCollision, ciprTableState($db, $sqlCollisionTable), 'Occupied SQL namespace must remain byte-for-byte unchanged');
    assertSameValue([], ciprArtifactState($artifactRoot . '/photo-reject-' . $sqlCollisionToken), 'SQL collision must fail before creating owned storage');
    assertSameValue(false, file_exists($artifactRoot . '/photo-reject-' . $sqlCollisionToken), 'SQL collision must leave owned storage absent');

    $storageCollisionToken = $tokens['storage-collision'];
    $storageCollisionRoot = $artifactRoot . '/photo-reject-' . $storageCollisionToken;
    if (!mkdir($storageCollisionRoot, 0700)
        || file_put_contents($storageCollisionRoot . '/collision.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create occupied storage namespace fixture');
    }
    $beforeStorageCollision = ciprArtifactState($storageCollisionRoot);
    $storageCollisionEnvironment = $environment;
    $storageCollisionEnvironment['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $storageCollisionToken;
    $storageCollision = ciprRun($root, $storageCollisionEnvironment);
    assertSameValue(2, $storageCollision['status'], 'Occupied storage namespace must be classified as setup failure');
    assertSameValue('', $storageCollision['stdout'], 'Occupied storage namespace must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $storageCollision['stderr']), 'Occupied storage namespace must emit one SETUP_FAILURE line');
    assertSameValue([], ciprOwnedTables($db, $storageCollisionToken, $discovered), 'Storage collision must fail before creating owned SQL');
    assertSameValue($beforeStorageCollision, ciprArtifactState($storageCollisionRoot), 'Occupied storage namespace must remain byte-for-byte unchanged');

    $unsafeEnvironment = $environment;
    $unsafeEnvironment['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $tokens['unsafe-tmp'];
    $unsafeEnvironment['FMONITOR_PHOTO_REJECTION_VERIFY_ARTIFACT_ROOT'] = '/tmp';
    $unsafe = ciprRun($root, $unsafeEnvironment);
    assertSameValue(2, $unsafe['status'], '/tmp artifact root must be classified as setup failure');
    assertSameValue('', $unsafe['stdout'], 'Unsafe artifact root must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $unsafe['stderr']), 'Unsafe artifact root must emit one SETUP_FAILURE line');
    assertSameValue([], ciprOwnedTables($db, $tokens['unsafe-tmp'], $discovered), 'Unsafe artifact root must be rejected before creating owned SQL');
    assertSameValue(false, file_exists($artifactRoot . '/photo-reject-' . $tokens['unsafe-tmp']), 'Unsafe artifact root must leave the configured-root owned storage namespace absent');
    assertSameValue($beforeDb, ciprTableState($db, $decoyTable), 'Safety probes must preserve the ambient SQL decoy');
    assertSameValue($beforeForeignDb, ciprTableState($db, $foreignTable), 'Safety probes must preserve the foreign-token SQL decoy');
    assertSameValue($beforeForeignArtifacts, ciprArtifactState($foreignArtifacts), 'Safety probes must preserve foreign-token storage');

    $invalid = $environment;
    unset($invalid['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN']);
    $invalidResult = ciprRun($root, $invalid);
    assertSameValue(2, $invalidResult['status'], 'Missing run token must be classified as setup failure');
    assertSameValue('', $invalidResult['stdout'], 'Setup failure must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $invalidResult['stderr']), 'Setup failure must emit exactly one SETUP_FAILURE line');

    $unavailable = $environment;
    $unavailable['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $tokens['unavailable'];
    $unavailable['FMONITOR_VERIFY_DB_HOST'] = '127.0.0.1';
    $unavailable['FMONITOR_VERIFY_DB_PORT'] = '1';
    $unavailableResult = ciprRun($root, $unavailable);
    assertSameValue(2, $unavailableResult['status'], 'Unavailable disposable DB must be classified as setup failure');
    assertSameValue('', $unavailableResult['stdout'], 'Unavailable DB must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $unavailableResult['stderr']), 'Unavailable DB must emit exactly one SETUP_FAILURE line');

    $adminDb = ciprAdminDb($config);
    $ddlDeniedUser = 'cipr_ddl_' . $tokens['ddl-denied'];
    if (preg_match('/\Acipr_ddl_[a-f0-9]{12}\z/D', $ddlDeniedUser) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe DDL-denial database user');
    }
    $ddlDeniedPassword = bin2hex(random_bytes(16));
    $adminDb->query("CREATE USER `$ddlDeniedUser`@'%' IDENTIFIED BY '$ddlDeniedPassword'");
    $ddlDeniedUserCreated = true;
    $databaseName = str_replace('`', '``', $config['NAME']);
    $adminDb->query("GRANT SELECT ON `$databaseName`.* TO `$ddlDeniedUser`@'%'");

    $beforeDdlDeniedDb = ciprTableState($db, $decoyTable);
    $beforeDdlDeniedForeignDb = ciprTableState($db, $foreignTable);
    $beforeDdlDeniedArtifacts = ciprArtifactState($artifactRoot);
    $beforeDdlDeniedForeignArtifacts = ciprArtifactState($foreignArtifacts);
    $ddlDeniedEnvironment = $environment;
    $ddlDeniedEnvironment['FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN'] = $tokens['ddl-denied'];
    $ddlDeniedEnvironment['FMONITOR_VERIFY_DB_USER'] = $ddlDeniedUser;
    $ddlDeniedEnvironment['FMONITOR_VERIFY_DB_PASSWORD'] = $ddlDeniedPassword;
    $ddlDenied = ciprRun($root, $ddlDeniedEnvironment);
    assertSameValue([], ciprOwnedTables($db, $tokens['ddl-denied'], $discovered), 'Verification fixture DDL permission denial must leave no owned SQL namespace');
    assertSameValue(false, file_exists($artifactRoot . '/photo-reject-' . $tokens['ddl-denied']), 'Verification fixture DDL permission denial must leave no owned storage namespace');
    assertSameValue($beforeDdlDeniedDb, ciprTableState($db, $decoyTable), 'Verification fixture DDL permission denial must preserve the ambient SQL decoy');
    assertSameValue($beforeDdlDeniedForeignDb, ciprTableState($db, $foreignTable), 'Verification fixture DDL permission denial must preserve the foreign-token SQL decoy');
    assertSameValue($beforeDdlDeniedArtifacts, ciprArtifactState($artifactRoot), 'Verification fixture DDL permission denial must preserve ambient and occupied storage');
    assertSameValue($beforeDdlDeniedForeignArtifacts, ciprArtifactState($foreignArtifacts), 'Verification fixture DDL permission denial must preserve foreign-token storage byte-for-byte');
    assertSameValue(2, $ddlDenied['status'], 'Verification fixture DDL permission denial must be classified as setup failure; evidence=' . json_encode($ddlDenied, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    assertSameValue('', $ddlDenied['stdout'], 'Verification fixture DDL permission denial must not emit normalized stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $ddlDenied['stderr']), 'Verification fixture DDL permission denial must emit exactly one SETUP_FAILURE line');

    echo "ok - CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 public oracle is deterministic, isolated, and correctly classified\n";
} catch (TestFailure $exception) {
    $failureMessage = $exception->getMessage();
    $exitStatus = str_starts_with($failureMessage, 'SETUP_FAILURE:') ? 2 : 1;
} catch (Throwable $exception) {
    $failureMessage = 'REGRESSION_FAILURE: ' . $exception->getMessage();
    $exitStatus = 1;
} finally {
    if ($adminDb instanceof mysqli) {
        if ($ddlDeniedUserCreated && is_string($ddlDeniedUser) && preg_match('/\Acipr_ddl_[a-f0-9]{12}\z/D', $ddlDeniedUser) === 1) {
            $adminDb->query("DROP USER IF EXISTS `$ddlDeniedUser`@'%'");
        }
        $adminDb->close();
    }
    if ($db instanceof mysqli) {
        foreach (array_keys($discovered) as $table) {
            if (preg_match('/\Apj_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D', $table) === 1) {
                $db->query("DROP TABLE IF EXISTS `$table`");
            }
        }
        $db->query("DROP TABLE IF EXISTS `$decoyTable`");
        $db->query("DROP TABLE IF EXISTS `$foreignTable`");
        $db->close();
    }
    foreach ($tokens as $token) {
        ciprRemoveTree($artifactRoot . '/photo-reject-' . $token);
    }
    ciprRemoveTree($artifactRoot);
}

if ($failureMessage !== null) {
    fwrite(STDERR, $failureMessage . "\n");
    exit($exitStatus);
}
