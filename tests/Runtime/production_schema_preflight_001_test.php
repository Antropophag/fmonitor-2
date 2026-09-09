<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\PilotLegacyObjectSchemaMigration;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_runtime_preflight_' . bin2hex(random_bytes(5));
$admin = new mysqli($host, $user, $password, '', $port);
$db = null;

try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->query('CREATE TABLE ambient_keep(id INT NOT NULL PRIMARY KEY, marker VARCHAR(40) NOT NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO ambient_keep VALUES(1,'preserve ambient')");
    $columns = 'ordadr_address VARCHAR(500) NULL,entrance VARCHAR(80) NULL,regnumber VARCHAR(120) NULL,workdatestart VARCHAR(40) NULL,workdateendadjusted VARCHAR(40) NULL,plan_finish_date VARCHAR(40) NULL,workdatefinish VARCHAR(40) NULL,ptoactdate VARCHAR(40) NULL,responsstroicontrol VARCHAR(80) NULL';
    $cases = [
        'wrong original type' => ['id BIGINT UNSIGNED NOT NULL PRIMARY KEY,' . str_replace('ordadr_address VARCHAR(500)', 'ordadr_address VARCHAR(499)', $columns), 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
        'missing id primary key' => ['id BIGINT UNSIGNED NOT NULL,' . $columns, 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
        'wrong primary key' => ['id BIGINT UNSIGNED NOT NULL,' . str_replace('regnumber VARCHAR(120) NULL', 'regnumber VARCHAR(120) NOT NULL PRIMARY KEY', $columns), 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
        'wrong engine and charset' => ['id BIGINT UNSIGNED NOT NULL PRIMARY KEY,' . $columns, 'ENGINE=MyISAM DEFAULT CHARSET=latin1'],
    ];
    foreach ($cases as $label => [$definition, $tableOptions]) {
        $prefix = 'drift_' . substr(hash('sha256', $label), 0, 8) . '_';
        $table = $prefix . 'fm_maintable';
        $db->query("CREATE TABLE `{$table}`({$definition}) {$tableOptions}");
        $db->query("INSERT INTO `{$table}` VALUES(1450,'preserve address','1','77-PRESERVE','2026-09-01','2026-10-01','2026-10-02',NULL,NULL,'73')");
        $before = [
            'ddl' => $db->query("SHOW CREATE TABLE `{$table}`")->fetch_row()[1],
            'rows' => $db->query("SELECT * FROM `{$table}` ORDER BY id")->fetch_all(MYSQLI_ASSOC),
            'ambientDdl' => $db->query('SHOW CREATE TABLE ambient_keep')->fetch_row()[1],
            'ambientRows' => $db->query('SELECT * FROM ambient_keep ORDER BY id')->fetch_all(MYSQLI_ASSOC),
        ];
        $result = PilotLegacyObjectSchemaMigration::apply($db, $prefix);
        assertSameValue(
            ['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT'],
            $result,
            "INTENTIONAL_RED: {$label} predecessor conflicts before ALTER",
        );
        $after = [
            'ddl' => $db->query("SHOW CREATE TABLE `{$table}`")->fetch_row()[1],
            'rows' => $db->query("SELECT * FROM `{$table}` ORDER BY id")->fetch_all(MYSQLI_ASSOC),
            'ambientDdl' => $db->query('SHOW CREATE TABLE ambient_keep')->fetch_row()[1],
            'ambientRows' => $db->query('SELECT * FROM ambient_keep ORDER BY id')->fetch_all(MYSQLI_ASSOC),
        ];
        assertSameValue($before, $after, "{$label} rejection preserves exact schema, rows and ambient objects");
    }

    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 v22 validates predecessor types before mutation\n";
} finally {
    if ($db instanceof mysqli) $db->close();
    try {
        $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    } finally {
        $admin->close();
    }
}
