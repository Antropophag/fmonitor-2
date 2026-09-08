<?php
declare(strict_types=1);
// FMONITOR_TEST_DB: isolated migration-10/v17 public-seam multi-prefix regression.
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationCompletionDetailsSchemaMigration as Migration17;
use FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration as Migration10;

function icmpQ(string $value): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/D', $value) !== 1) { throw new TestFailure('unsafe identifier'); }
    return '`' . $value . '`';
}

function icmpCreateHistorical(mysqli $db, string $prefix): void
{
    $root = icmpQ($prefix . 'fm2_pilot_completion_facts');
    $corrections = icmpQ($prefix . 'fm2_pilot_completion_fact_corrections');
    $db->query("CREATE TABLE $root(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,installation_case_id BIGINT UNSIGNED NOT NULL,fact_type ENUM('pto_act','declaration') NOT NULL,fact_date DATE NOT NULL,details VARCHAR(500) NOT NULL DEFAULT '',recorded_at VARCHAR(40) NOT NULL,recorded_by_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_case_fact(installation_case_id,fact_type),KEY installation_case_id(installation_case_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE $corrections(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,root_fact_id BIGINT UNSIGNED NOT NULL,version_no INT UNSIGNED NOT NULL,previous_correction_id BIGINT UNSIGNED DEFAULT NULL,previous_version_no INT UNSIGNED DEFAULT NULL,fact_date DATE NOT NULL,reason VARCHAR(1000) NOT NULL,recorded_at VARCHAR(40) NOT NULL,recorded_by_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_root_version(root_fact_id,version_no),UNIQUE KEY uq_previous_correction(previous_correction_id),UNIQUE KEY uq_correction_identity(id,root_fact_id,version_no),KEY root_history(root_fact_id,id),CONSTRAINT fk_completion_correction_root FOREIGN KEY(root_fact_id) REFERENCES $root(id),CONSTRAINT fk_completion_correction_previous FOREIGN KEY(previous_correction_id,root_fact_id,previous_version_no) REFERENCES $corrections(id,root_fact_id,version_no),CHECK(version_no>=1),CHECK((version_no=1 AND previous_correction_id IS NULL AND previous_version_no IS NULL) OR (version_no>1 AND previous_correction_id IS NOT NULL AND previous_version_no=version_no-1)),CHECK(char_length(trim(reason)) BETWEEN 1 AND 1000)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query('SET FOREIGN_KEY_CHECKS=0');
    try { $db->query("ALTER TABLE $corrections DROP INDEX fk_completion_correction_previous"); }
    finally { $db->query('SET FOREIGN_KEY_CHECKS=1'); }
}

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_completion_multi_' . bin2hex(random_bytes(5));
$admin = new mysqli($host, $user, $password, '', $port);

try {
    $admin->query('CREATE DATABASE ' . icmpQ($database) . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db = new mysqli($host, $user, $password, $database, $port);
    try {
        $historical = 'historical_';
        icmpCreateHistorical($db, $historical);
        $db->query("INSERT INTO {$historical}fm2_pilot_completion_facts VALUES(1,41,'pto_act','2026-09-07','retained root','2026-09-07T10:00:00+03:00',7)");
        $db->query("INSERT INTO {$historical}fm2_pilot_completion_fact_corrections VALUES(10,1,1,NULL,NULL,'2026-09-08','retained correction','2026-09-08T10:00:00+03:00',8)");
        assertSameValue(['applied'=>false,'schemaVersion'=>10,'tablesCreated'=>[]], Migration10::apply($db, $historical), 'historical fixed-symbol v10 is accepted');
        assertSameValue(['applied'=>true,'schemaVersion'=>17,'columnsAdded'=>[$historical.'fm2_pilot_completion_fact_corrections.details']], Migration17::apply($db, $historical), 'historical fixed-symbol v17 successor applies');
        assertSameValue(['applied'=>false,'schemaVersion'=>17,'columnsAdded'=>[]], Migration17::apply($db, $historical), 'historical fixed-symbol v17 repeat is a no-op');

        foreach (['generation_3_', str_repeat('p', 25)] as $prefix) {
            assertSameValue(['applied'=>true,'schemaVersion'=>10,'tablesCreated'=>[$prefix.'fm2_pilot_completion_fact_corrections',$prefix.'fm2_pilot_completion_facts']], Migration10::apply($db, $prefix), "$prefix v10 applies beside historical namespace");
            assertSameValue(['applied'=>false,'schemaVersion'=>10,'tablesCreated'=>[]], Migration10::apply($db, $prefix), "$prefix v10 repeat is a no-op");
            assertSameValue(['applied'=>true,'schemaVersion'=>17,'columnsAdded'=>[$prefix.'fm2_pilot_completion_fact_corrections.details']], Migration17::apply($db, $prefix), "$prefix v17 applies");
            assertSameValue(['applied'=>false,'schemaVersion'=>17,'columnsAdded'=>[]], Migration17::apply($db, $prefix), "$prefix v17 repeat is a no-op");
            $table = $db->real_escape_string($prefix . 'fm2_pilot_completion_fact_corrections');
            $names = array_column($db->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$table' ORDER BY BINARY CONSTRAINT_NAME")->fetch_all(MYSQLI_ASSOC), 'CONSTRAINT_NAME');
            assertSameValue([$prefix.'fk_completion_correction_previous',$prefix.'fk_completion_correction_root'], $names, "$prefix has exact scoped FK symbols");
            assertSameValue(true, max(array_map('strlen', $names)) <= 64, "$prefix FK symbols fit MariaDB limit");
        }

        $retained = $db->query("SELECT r.details,c.reason,c.details correction_details FROM {$historical}fm2_pilot_completion_facts r JOIN {$historical}fm2_pilot_completion_fact_corrections c ON c.root_fact_id=r.id")->fetch_assoc();
        assertSameValue(['details'=>'retained root','reason'=>'retained correction','correction_details'=>null], $retained, 'historical rows survive both new namespaces and v17');
    } finally { $db->close(); }
    echo "INSTALLATION_COMPLETION_MULTI_PREFIX_001_OK\n";
} finally {
    $admin->query('DROP DATABASE IF EXISTS ' . icmpQ($database));
    $admin->close();
}
