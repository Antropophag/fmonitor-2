<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;
use FMonitor2\InstallationProcess\WorkforceHistorySchemaReadiness;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;

require_once __DIR__ . '/Otiz.php';
require_once __DIR__ . '/IdentityBootstrap.php';

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
    if ($withTestFixtures) {
        $db->query("INSERT IGNORE INTO `{$legacyPrefix}fm_maintable` VALUES(4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-20',NULL,NULL,NULL,'73'),(4999,'Москва, ул. Непилотная, д. 1','1','77-000999','2026-09-30','2026-12-01',NULL,NULL,NULL,'73')");
    }

    foreach ([ProductionProcessSchemaMigration::class] as $migration) {
        $result = $migration::apply($db, $processPrefix);
        if (isset($result['reason'])) throw new RuntimeException('Schema migration failed');
    }
    WorkforceHistorySchemaReadiness::assertReady($db, $processPrefix);
    $db->query("CREATE TABLE IF NOT EXISTS `{$processPrefix}fm2_pilot_generation_sentinel`(singleton_id TINYINT UNSIGNED PRIMARY KEY,generation INT UNSIGNED NOT NULL,fingerprint CHAR(8) NOT NULL,manifest_nonce CHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $sentinel=$db->prepare("INSERT INTO `{$processPrefix}fm2_pilot_generation_sentinel` VALUES(1,?,?,?) ON DUPLICATE KEY UPDATE generation=VALUES(generation),fingerprint=VALUES(fingerprint),manifest_nonce=VALUES(manifest_nonce)");$sentinel->bind_param('iss',$generation,$fingerprint,$manifestNonce);$sentinel->execute();
    $bootstrapEmails=(string)(getenv('FMONITOR_BOOTSTRAP_SUPERADMIN_EMAILS')?:'');
    $bootstrapPassword=(string)(getenv('FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD')?:'');
    RapidPilotIdentityBootstrap::apply($db,$processPrefix,$bootstrapEmails,$bootstrapPassword);
    $db->query("DROP TABLE IF EXISTS `{$processPrefix}fm2_process_user_capabilities`");
    $db->query("DROP TABLE IF EXISTS `{$legacyPrefix}users`,`{$legacyPrefix}users_roles`");
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
