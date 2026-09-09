<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/jobs_schema_assertions.php';
use FMonitor2\InstallationProcess\JobsSchemaMigration;

mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',
    getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
$name='t_job_literal_'.bin2hex(random_bytes(6));$prefix='literal_';
$db->query("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$db->select_db($name);
try {
    JobsSchemaMigration::apply($db,$prefix);
    jobsAssertSchema($db,$prefix);
    $checks=$db->query("SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='literal_fm2_jobs'")->fetch_all(MYSQLI_ASSOC);
    $status=array_values(array_filter($checks,static fn(array $row): bool=>str_contains($row['CHECK_CLAUSE'],'status')));
    assertSameValue(1,count($status),'one normative status check found');
    $constraint=str_replace('`','``',$status[0]['CONSTRAINT_NAME']);
    $db->query("ALTER TABLE literal_fm2_jobs DROP CONSTRAINT `{$constraint}`, ADD CONSTRAINT changed_status_literals CHECK(status IN ('READY','LEASED','COMPLETED','DEAD'))");
    assertSameValue(0,(int)$db->query("SELECT _ascii'ready' COLLATE ascii_bin IN ('READY','LEASED','COMPLETED','DEAD')")->fetch_column(),'changed literal case changes binary status semantics');
    assertSameValue(false,JobsSchemaMigration::isReady($db,$prefix),'INTENTIONAL_RED: readiness preserves literal case in CHECK semantics');
    $oracleRejected=false;
    try { jobsAssertSchema($db,$prefix); } catch(TestFailure) { $oracleRejected=true; }
    assertSameValue(true,$oracleRejected,'independent physical oracle rejects changed literal case');
    $snapshot=static function()use($db): array {
        $out=[];foreach($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM) as [$table])
            $out[$table]=[$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_row()[1],$db->query('SELECT * FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC)];
        return $out;
    };
    $before=$snapshot();$rejected=false;
    try { JobsSchemaMigration::apply($db,$prefix); } catch(RuntimeException) { $rejected=true; }
    assertSameValue(true,$rejected,'deployment rejects altered CHECK before mutation');
    assertSameValue($before,$snapshot(),'literal conflict preserves all six tables and rows');
    echo "PASS: DURABLE-JOBS-SCHEMA-001 literal-preserving CHECK fingerprint\n";
} finally { $db->query("DROP DATABASE `{$name}`");$db->close(); }
