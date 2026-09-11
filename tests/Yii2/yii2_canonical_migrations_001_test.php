<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

/** YII2-CANONICAL-MIGRATIONS-001 A1/A2/A5: isolated CLI and single-owner boundary. */
$root = dirname(__DIR__, 2);
function ycmRun(array $arguments, array $environment, string $root, array $phpOptions = []): array
{
    $pipes = [];
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) { if (!is_string($name) || !is_string($value)) continue; $command[] = $name . '=' . $value; }
    $command = [...$command, PHP_BINARY, ...$phpOptions, $root . '/bin/yii', ...$arguments];
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: Yii process must start.');
    $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process), $stdout, $stderr];
}
$valid = array_replace(getenv(), ['FMONITOR_DB_HOST' => '127.0.0.1', 'FMONITOR_DB_PORT' => '1', 'FMONITOR_DB_NAME' => 'private_database_name', 'FMONITOR_DB_USER' => 'private_database_user', 'FMONITOR_DB_PASSWORD' => 'PRIVATE_PASSWORD_YCM', 'FMONITOR_PROCESS_TABLE_PREFIX' => 'private_prefix_']);
[$exit, $stdout, $stderr] = ycmRun(['schema-migrate/run', '--interactive=0'], $valid, $root);
assertSameValue([69, "{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n", ''], [$exit, $stdout, $stderr], 'INTENDED_RED: Yii command reaches the database boundary with the stable closed result.');
foreach (['PRIVATE_PASSWORD_YCM', 'private_database_name', 'private_database_user', 'private_prefix_'] as $secret) assertSameValue(false, str_contains($stdout . $stderr, $secret), 'Database inputs remain redacted.');
$invalidCases = [['FMONITOR_DB_HOST', ''], ['FMONITOR_DB_PORT', ''], ['FMONITOR_DB_PORT', '0'], ['FMONITOR_DB_PORT', '01'], ['FMONITOR_DB_PORT', '65536'], ['FMONITOR_PROCESS_TABLE_PREFIX', 'bad-prefix!'], ['FMONITOR_PROCESS_TABLE_PREFIX', str_repeat('a', 26)], ['FMONITOR_DB_NAME', ''], ['FMONITOR_DB_USER', '']];
foreach ($invalidCases as [$name, $value]) {
    $environment = $valid; $environment[$name] = $value;
    assertSameValue([64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ''], ycmRun(['schema-migrate/run', '--interactive=0'], $environment, $root), 'Invalid environment fails before database access: ' . $name);
}
foreach (['FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD', 'FMONITOR_PROCESS_TABLE_PREFIX'] as $name) { $missing = $valid; unset($missing[$name]); assertSameValue([64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ''], ycmRun(['schema-migrate/run', '--interactive=0'], $missing, $root), 'Missing required environment fails closed: ' . $name); }
$emptyPassword = $valid; $emptyPassword['FMONITOR_DB_PASSWORD'] = ''; assertSameValue([69, "{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n", ''], ycmRun(['schema-migrate/run', '--interactive=0'], $emptyPassword, $root), 'Explicit empty password passes validation and reaches DB.');
foreach ([['schema-migrate/run', 'extra', '--interactive=0'], ['schema-migrate/run', '--unknown=1', '--interactive=0'], ['schema-migrate/run', '--interactive=1'], ['--interactive=0', 'schema-migrate/run'], ['schema-migrate/run', '--interactive=0', '--interactive=0']] as $arguments) assertSameValue([64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ''], ycmRun($arguments, $valid, $root), 'Arguments and options are closed.');
$throwing = $root . '/tests/Support/YiiMigrationThrowingCatalogue.php'; $throwDatabase = 't_ycm_throw_' . bin2hex(random_bytes(5));
$throwHost = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1'; $throwPort = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306); $throwUser = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root'; $throwPassword = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local'; $throwAdmin = new mysqli($throwHost, $throwUser, $throwPassword, '', $throwPort);
try {
    $throwAdmin->query("CREATE DATABASE `{$throwDatabase}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $throwEnvironment = array_replace($valid, ['FMONITOR_DB_HOST'=>$throwHost,'FMONITOR_DB_PORT'=>(string)$throwPort,'FMONITOR_DB_NAME'=>$throwDatabase,'FMONITOR_DB_USER'=>$throwUser,'FMONITOR_DB_PASSWORD'=>$throwPassword,'FMONITOR_PROCESS_TABLE_PREFIX'=>'throw_']);
    [$exit, $stdout, $stderr] = ycmRun(['schema-migrate/run', '--interactive=0'], $throwEnvironment, $root, ['-d', 'auto_prepend_file=' . $throwing]);
    assertSameValue([70, "{\"ok\":false,\"reason\":\"SOFTWARE_ERROR\"}\n", ''], [$exit, $stdout, $stderr], 'Unexpected catalogue Throwable is mapped at the real Yii seam.');
    foreach (['PRIVATE_THROWABLE_SQL_SELECT_CANARY', 'RuntimeException', 'SELECT', $throwing, $throwDatabase] as $secret) assertSameValue(false, str_contains($stdout . $stderr, $secret), 'Unexpected failure details remain redacted.');
    assertSameValue([], $throwAdmin->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$throwDatabase}'")->fetch_all(MYSQLI_ASSOC), 'Unexpected pre-catalogue failure creates no schema facts.');
} finally { $throwAdmin->query("DROP DATABASE IF EXISTS `{$throwDatabase}`"); $throwAdmin->close(); }
$controller = (string) @file_get_contents($root . '/app/YiiRuntime/Commands/SchemaMigrateController.php'); $adapter = (string) @file_get_contents($root . '/app/YiiRuntime/CanonicalMigrationConsole.php'); $alias = (string) file_get_contents($root . '/bin/fmonitor2-migrate.php');
assertSameValue(true, $controller !== '' && $adapter !== '', 'INTENDED_RED: Yii migration composition exists.');
foreach ([$controller, $adapter] as $source) { assertSameValue(false, str_contains($source, 'rapid-pilot'), 'Yii migration composition excludes rapid-pilot.'); assertSameValue(false, str_contains($source, 'yii\\db\\Connection'), 'Migration does not create a second Yii DB boundary.'); }
foreach (['CanonicalMigrationApplication', 'ProductionPilotMigrationCatalogue', 'new mysqli', 'set_charset', 'CONFIGURATION_INVALID'] as $ownedLogic) assertSameValue(false, str_contains($alias, $ownedLogic), 'Compatibility alias contains no owned migration logic: ' . $ownedLogic);
assertSameValue(true, str_contains($alias, 'fmonitor2-yii.php') && str_contains($alias, 'schema-migrate/run'), 'INTENDED_RED: compatibility alias delegates to the canonical Yii route.');
echo "PASS: YII2-CANONICAL-MIGRATIONS-001 CLI boundary\n";
