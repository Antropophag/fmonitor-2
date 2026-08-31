<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;
use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

require_once __DIR__ . '/Otiz.php';

$root = dirname(__DIR__);
$home = getenv('HOME');
if (!is_string($home) || $home === '') throw new RuntimeException('Home directory unavailable');

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'FMonitor2\\InstallationProcess\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = $root . '/app/InstallationProcess/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require_once $path;
});

$fingerprint = substr(hash('sha256', (string) realpath($root)), 0, 8);
$stateRoot = $home . '/.local/state/fmonitor2/pilot-demo/' . $fingerprint;
$manifestPath = $stateRoot . '/active.json';

$generation = 1;
$processPrefix = 'fm2d_' . $fingerprint . '_g' . $generation . '_';
$legacyPrefix = 'fm2l_' . $fingerprint . '_g' . $generation . '_';
$generationRoot = $stateRoot . '/generations/' . $generation;
$artifactRoot = $generationRoot . '/artifacts';
$manifestNonce = bin2hex(random_bytes(32));
$fixtureMode = getenv('FMONITOR_PILOT_FIXTURE_MODE');
if ($fixtureMode === false) $fixtureMode = '';
if (!in_array($fixtureMode, ['', 'test-fixtures'], true)) throw new RuntimeException('Invalid fixture mode');
$withTestFixtures = $fixtureMode === 'test-fixtures';
if (!is_dir($artifactRoot) && !mkdir($artifactRoot, 0755, true)) throw new RuntimeException('State directory unavailable');

$db = new mysqli('127.0.0.1', 'fmonitor2_demo', 'fmonitor2_demo_local', 'fmonitor2_demo', 23306);
$db->set_charset('utf8mb4');
$serverIdentity=(string)$db->query('SELECT @@hostname AS identity')->fetch_assoc()['identity'];
try {
    $db->query("CREATE TABLE IF NOT EXISTS `{$legacyPrefix}fm_maintable` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40),responsstroicontrol VARCHAR(80)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$legacyPrefix}users_roles` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$legacyPrefix}users` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,email VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ($withTestFixtures) {
        $db->query("INSERT IGNORE INTO `{$legacyPrefix}users_roles` VALUES(5,'ФКР',1),(8,'Строительный контроль',1)");
        $db->query("INSERT IGNORE INTO `{$legacyPrefix}users` VALUES(18,'Сидоров Сергей Сергеевич','sidorov@shlz.ru',5,1),(73,'Анна Волкова','volkova@shlz.ru',8,1)");
        $db->query("INSERT IGNORE INTO `{$legacyPrefix}fm_maintable` VALUES(4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-20',NULL,NULL,NULL,'73'),(4999,'Москва, ул. Непилотная, д. 1','1','77-000999','2026-09-30','2026-12-01',NULL,NULL,NULL,'73')");
    }

    foreach ([ProductionProcessSchemaMigration::class] as $migration) {
        $result = $migration::apply($db, $processPrefix);
        if (isset($result['reason'])) throw new RuntimeException('Schema migration failed');
    }
    $catalogName = $db->real_escape_string($processPrefix . 'fm2_workforce_catalog');
    $catalogExists = (int) $db->query("SELECT COUNT(*) AS count FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$catalogName}'")->fetch_assoc()['count'] === 1;
    if (!$catalogExists) {
        $result = WorkforceCatalogSchemaMigration::apply($db, $processPrefix);
        if (isset($result['reason'])) throw new RuntimeException('Workforce schema migration failed');
    }
    foreach ([BitrixWorkforceHistorySchemaMigration::class, ProcessUserCapabilitiesSchemaMigration::class, ProcessCommandCapabilitiesSchemaMigration::class] as $migration) {
        $result = $migration::apply($db, $processPrefix);
        if (isset($result['reason'])) throw new RuntimeException('Schema migration failed');
    }
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_generation_sentinel`(singleton_id TINYINT UNSIGNED PRIMARY KEY,generation INT UNSIGNED NOT NULL,fingerprint CHAR(8) NOT NULL,manifest_nonce CHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $sentinel=$db->prepare("INSERT INTO `{$processPrefix}fm2_pilot_generation_sentinel` VALUES(1,?,?,?) ON DUPLICATE KEY UPDATE generation=VALUES(generation),fingerprint=VALUES(fingerprint),manifest_nonce=VALUES(manifest_nonce)");$sentinel->bind_param('iss',$generation,$fingerprint,$manifestNonce);$sentinel->execute();
    if ($withTestFixtures) $db->query("INSERT IGNORE INTO `{$processPrefix}fm2_process_user_capabilities` VALUES(18,'assignment_order.prepare',NULL),(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(73,'construction_control_engineer','Инженер строительного контроля')");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_users`(user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,full_name VARCHAR(300) NOT NULL,email VARCHAR(254) NOT NULL,phone VARCHAR(100) NOT NULL,status TINYINT(1) NOT NULL,source_updated_at VARCHAR(40) NOT NULL,KEY(status,full_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_roles`(role_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status TINYINT(1) NOT NULL,source_updated_at VARCHAR(40) NOT NULL,KEY(status,name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,origin VARCHAR(40) NOT NULL,assigned_at VARCHAR(40) NOT NULL,assigned_by_user_id BIGINT UNSIGNED NULL,PRIMARY KEY(user_id,role_id),KEY(role_id,user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_user_role_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,action VARCHAR(40) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,KEY(user_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_auth_credentials`(user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,email_normalized VARCHAR(254) NOT NULL,password_hash VARCHAR(255) NULL,password_set_at VARCHAR(40) NULL,updated_at VARCHAR(40) NOT NULL,UNIQUE KEY(email_normalized)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_auth_attempts`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,email_normalized VARCHAR(254) NOT NULL,succeeded TINYINT(1) NOT NULL,attempted_at DATETIME(6) NOT NULL,KEY(email_normalized,attempted_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $sourceUpdatedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('Y-m-d\\TH:i:sP');
    $sourceUpdatedAt = $db->real_escape_string($sourceUpdatedAt);
    $db->query("INSERT INTO `{$processPrefix}fm2_pilot_roles`(role_id,name,status,source_updated_at) SELECT id,name,status,'{$sourceUpdatedAt}' FROM `{$legacyPrefix}users_roles` ON DUPLICATE KEY UPDATE name=VALUES(name),status=VALUES(status),source_updated_at=VALUES(source_updated_at)");
    $db->query("INSERT INTO `{$processPrefix}fm2_pilot_users`(user_id,full_name,email,phone,status,source_updated_at) SELECT id,name,email,'',status,'{$sourceUpdatedAt}' FROM `{$legacyPrefix}users` ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),email=VALUES(email),status=VALUES(status),source_updated_at=VALUES(source_updated_at)");
    $db->query("INSERT INTO `{$processPrefix}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at) SELECT id,LOWER(TRIM(email)),NULL,NULL,'{$sourceUpdatedAt}' FROM `{$legacyPrefix}users` WHERE LOWER(TRIM(email)) REGEXP '^[^@[:space:]]+@shlz\\.ru$' ON DUPLICATE KEY UPDATE email_normalized=VALUES(email_normalized),updated_at=VALUES(updated_at)");
    $db->query("INSERT INTO `{$processPrefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) SELECT id,role_id,'legacy_primary','{$sourceUpdatedAt}',NULL FROM `{$legacyPrefix}users` ON DUPLICATE KEY UPDATE origin=origin");
    if ($withTestFixtures) {
        $db->query("INSERT INTO `{$processPrefix}fm2_pilot_roles`(role_id,name,status,source_updated_at) VALUES(9001,'ОТиЗ',1,'{$sourceUpdatedAt}') ON DUPLICATE KEY UPDATE name=VALUES(name),status=VALUES(status),source_updated_at=VALUES(source_updated_at)");
        $db->query("INSERT INTO `{$processPrefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(18,9001,'rapid_pilot','{$sourceUpdatedAt}',NULL) ON DUPLICATE KEY UPDATE origin=origin");
    }
    RapidPilotOtiz::bootstrap($db, $processPrefix);
    require_once __DIR__ . '/InspectionSchedule.php';
    RapidPilotInspectionSchedule::ensureSchema($db, $processPrefix);
    if ($withTestFixtures) (new PilotCaseImporter($db, $processPrefix, $legacyPrefix))->import([4512], '2026-08-29T12:00:00+03:00');
} finally {
    $db->close();
}

$manifest = json_encode([
    'fingerprint' => $fingerprint,
    'generation' => $generation,
    'processPrefix' => $processPrefix,
    'legacyPrefix' => $legacyPrefix,
    'port' => 8092,
    'state' => 'ready',
    'mode' => $withTestFixtures ? 'test-fixtures' : 'native-only',
    'manifestNonce' => $manifestNonce,
    'dbEndpoint' => ['host'=>'127.0.0.1','port'=>23306,'name'=>'fmonitor2_demo'],
    'dbServerIdentity' => $serverIdentity,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
$temporaryManifest = $manifestPath . '.new';
if (file_put_contents($temporaryManifest, $manifest, LOCK_EX) === false || !rename($temporaryManifest, $manifestPath)) {
    throw new RuntimeException('Manifest unavailable');
}
