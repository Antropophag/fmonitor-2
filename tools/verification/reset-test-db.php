<?php

declare(strict_types=1);

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: '23306');
$adminUser = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$adminPassword = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = getenv('FMONITOR_TEST_DB_NAME') ?: 'fmonitor2_test';
$user = getenv('FMONITOR_TEST_DB_USER') ?: 'fmonitor2_test';
$password = getenv('FMONITOR_TEST_DB_PASSWORD') ?: 'fmonitor2_test_local';

if (preg_match('/^[A-Za-z0-9_]+$/D', $database) !== 1
    || preg_match('/^[A-Za-z0-9_]+$/D', $user) !== 1
) {
    fwrite(STDERR, "SETUP_FAILURE: invalid test database identity\n");
    exit(64);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli($host, $adminUser, $adminPassword, null, $port);
    $connection->set_charset('utf8mb4');
    $quotedPassword = "'" . $connection->real_escape_string($password) . "'";
    $connection->query("DROP DATABASE IF EXISTS `{$database}`");
    $connection->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $connection->query("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY {$quotedPassword}");
    $connection->query("ALTER USER '{$user}'@'%' IDENTIFIED BY {$quotedPassword}");
    $connection->query("GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$user}'@'%'");
    $connection->close();
} catch (Throwable) {
    fwrite(STDERR, "SETUP_FAILURE: unable to reset test database\n");
    exit(69);
}

echo "TEST_DB_RESET_OK\n";
