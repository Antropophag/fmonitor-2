<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/jobs_schema_assertions.php';
use FMonitor2\InstallationProcess\JobsSchemaMigration;
use FMonitor2\Jobs\MariaDbJobQueue;
use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

assertSameValue(true, class_exists(JobsSchemaMigration::class), 'INTENTIONAL_RED: deployment-owned Jobs schema seam exists');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$adminUser = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$adminPassword = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$token = bin2hex(random_bytes(6));
$database = 't_jobs_schema_'.$token;
$badDatabase = 't_jobs_bad_'.$token;
$account = 'jq_'.$token;
$password = bin2hex(random_bytes(24));
$prefix = 'jobs_';
$admin = new mysqli($host, $adminUser, $adminPassword, '', $port);
$db = $bad = $runtime = null;
$ownedDatabases = [];
$accountOwned = false;
$tables = static fn(mysqli $c): array => array_column($c->query('SHOW TABLES')->fetch_all(MYSQLI_NUM), 0);
$rows = static fn(mysqli $c): array => [
    $c->query('SELECT * FROM jobs_fm2_jobs ORDER BY job_id')->fetch_all(MYSQLI_ASSOC),
    $c->query('SELECT * FROM jobs_fm2_job_events ORDER BY event_id')->fetch_all(MYSQLI_ASSOC),
];
try {
    foreach ([$database, $badDatabase] as $name) {
        $admin->query("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $ownedDatabases[] = $name;
    }
    $db = new mysqli($host, $adminUser, $adminPassword, $database, $port);
    $bad = new mysqli($host, $adminUser, $adminPassword, $badDatabase, $port);
    assertSameValue(false, JobsSchemaMigration::isReady($db, $prefix), 'absent schema is not ready');
    assertSameValue([], $tables($db), 'readiness does not create tables');
    $result = CanonicalMigrationApplication::run($db, $prefix, ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0, true, 24, range(1, 24)], [
        $result['exitCode'], $result['result']['ok'] ?? null,
        $result['result']['schemaVersion'] ?? null, $result['result']['appliedVersions'] ?? null,
    ], 'canonical runner installs the new Jobs frontier');
    assertSameValue(true, JobsSchemaMigration::isReady($db, $prefix), 'deployed Jobs schema ready');
    jobsAssertSchema($db, $prefix);
    assertSameValue(true, JobsSchemaMigration::apply($db, 'other_')['applied'], 'second prefix installs independently');
    jobsAssertSchema($db, 'other_');
    assertSameValue(['other_fm2_job_events','other_fm2_jobs','other_fm2_outbox_attempt_events','other_fm2_outbox_intents','other_fm2_scheduler_slots','other_fm2_worker_heartbeats'], array_values(array_filter($tables($db),
        static fn(string $name): bool=>str_starts_with($name,'other_'))), 'literal complete composed v23 table inventory');

    $admin->query("CREATE USER '{$account}'@'%' IDENTIFIED BY '{$password}'");
    $accountOwned = true;
    $admin->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$database}`.* TO '{$account}'@'%'");
    $runtime = new mysqli($host, $account, $password, $database, $port);
    $queue = new MariaDbJobQueue($runtime, $prefix,
        static fn(): string => '2026-09-09T10:00:00.000000Z',
        static fn(): string => str_repeat('a', 64), ['test.echo'=>[1]]);
    $job = $queue->enqueue([
        'jobType'=>'test.echo', 'payloadVersion'=>1, 'payload'=>['value'=>'preserved'],
        'availableAtUtc'=>'2026-09-09T10:00:00.000000Z',
        'idempotencyKey'=>'11111111-1111-4111-8111-111111111111',
        'actor'=>['type'=>'system', 'id'=>'schema-test'],
    ]);
    assertSameValue('created', $job['status'], 'DML-only enqueue');
    $claim = $queue->claim(['workerId'=>'schema-worker', 'batch'=>1]);
    assertSameValue((int)$job['jobId'], (int)$claim[0]['jobId'], 'DML-only claim');
    assertSameValue('completed', $queue->complete([
        'jobId'=>$job['jobId'], 'leaseToken'=>$claim[0]['leaseToken'], 'result'=>['ok'=>true],
    ])['status'], 'DML-only completion');
    $denied = false;
    try { $runtime->query('CREATE TABLE forbidden_runtime_ddl(id INT)'); }
    catch (mysqli_sql_exception $error) { $denied = $error->getCode() === 1142; }
    assertSameValue(true, $denied, 'real MariaDB denies CREATE to runtime principal');
    $before = [$tables($db), $rows($db)];
    assertSameValue(false, JobsSchemaMigration::apply($db, 'other_')['applied'], 'other prefix repeat is no-op');
    assertSameValue($before, [$tables($db), $rows($db)], 'other prefix repeat preserves populated first family');
    assertSameValue(false, JobsSchemaMigration::apply($db, $prefix)['applied'], 'populated migration repeat is no-op');
    $repeat = CanonicalMigrationApplication::run($db, $prefix, ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0, 24, []], [$repeat['exitCode'], $repeat['result']['schemaVersion'] ?? null,
        $repeat['result']['appliedVersions'] ?? null], 'canonical repeat applies no version');
    assertSameValue($before, [$tables($db), $rows($db)], 'repeat preserves exact rows, history and table inventory');

    $bad->query('CREATE TABLE jobs_fm2_jobs(sentinel INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $bad->query('INSERT INTO jobs_fm2_jobs VALUES(731)');
    $bad->query('CREATE TABLE ambient_evidence(id INT PRIMARY KEY) ENGINE=InnoDB');
    $bad->query('INSERT INTO ambient_evidence VALUES(927)');
    $badBefore = [$tables($bad), $bad->query('SELECT * FROM jobs_fm2_jobs')->fetch_all(MYSQLI_ASSOC),
        $bad->query('SELECT * FROM ambient_evidence')->fetch_all(MYSQLI_ASSOC)];
    assertSameValue(false, JobsSchemaMigration::isReady($bad, $prefix), 'incompatible schema not ready');
    $rejected = false;
    try { JobsSchemaMigration::apply($bad, $prefix); }
    catch (RuntimeException) { $rejected = true; }
    assertSameValue(true, $rejected, 'incompatible owned table fails deployment preflight');
    assertSameValue($badBefore, [$tables($bad), $bad->query('SELECT * FROM jobs_fm2_jobs')->fetch_all(MYSQLI_ASSOC),
        $bad->query('SELECT * FROM ambient_evidence')->fetch_all(MYSQLI_ASSOC)], 'failure creates no partial schema and preserves ambient rows');
    $bad->query('DROP TABLE jobs_fm2_jobs');
    $bad->query('CREATE TABLE jobs_fm2_job_events(sentinel INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $bad->query('INSERT INTO jobs_fm2_job_events VALUES(538)');
    $inverseBefore=[$tables($bad),$bad->query('SELECT * FROM jobs_fm2_job_events')->fetch_all(MYSQLI_ASSOC),
        $bad->query('SELECT * FROM ambient_evidence')->fetch_all(MYSQLI_ASSOC)];
    $rejected=false;
    try { JobsSchemaMigration::apply($bad,$prefix); }
    catch (RuntimeException) { $rejected=true; }
    assertSameValue(true,$rejected,'incompatible second owned table rejects before first table creation');
    assertSameValue($inverseBefore,[$tables($bad),$bad->query('SELECT * FROM jobs_fm2_job_events')->fetch_all(MYSQLI_ASSOC),
        $bad->query('SELECT * FROM ambient_evidence')->fetch_all(MYSQLI_ASSOC)],'whole-family preflight leaves missing first table absent and second/ambient unchanged');
    assertSameValue($before, [$tables($db), $rows($db)], 'other database remains unchanged');
    echo "PASS: DURABLE-JOBS-SCHEMA-001 canonical, preflight and DML boundary\n";
} finally {
    foreach ([$runtime, $bad, $db] as $connection) if ($connection instanceof mysqli) $connection->close();
    if ($accountOwned) $admin->query("DROP USER '{$account}'@'%'");
    foreach ($ownedDatabases as $name) $admin->query("DROP DATABASE `{$name}`");
    $admin->close();
}
