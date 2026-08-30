<?php

declare(strict_types=1);

$auth = file_get_contents(__DIR__ . '/LocalAuth.php');
$bootstrap = file_get_contents(__DIR__ . '/docker-bootstrap.php');
$launcher = file_get_contents(__DIR__ . '/start.php');
$queue = file_get_contents(__DIR__ . '/ObjectQueue.php');
if (!is_string($auth) || !is_string($bootstrap) || !is_string($launcher) || !is_string($queue)) throw new RuntimeException('Auth sources unavailable');

$fail = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$constructorStart = strpos($auth, 'public function __construct()');
$constructorEnd = strpos($auth, 'public function handle(', $constructorStart === false ? 0 : $constructorStart);
$fail($constructorStart !== false && $constructorEnd !== false, 'LocalAuth constructor unavailable');
$constructor = substr($auth, $constructorStart, $constructorEnd - $constructorStart);
$fail(!str_contains($constructor, 'CREATE TABLE'), 'request-time auth constructor contains DDL');
$fail(!str_contains($constructor, 'INSERT INTO'), 'request-time auth constructor contains bulk synchronization');
$fail(!str_contains($constructor, 'ensureSchema'), 'request-time auth constructor invokes schema bootstrap');
$fail(!str_contains($auth, 'private function ensureSchema'), 'request-time schema bootstrap remains reachable');

foreach (['fm2_pilot_auth_credentials', 'fm2_pilot_auth_attempts'] as $table) {
    $fail(str_contains($bootstrap, 'CREATE TABLE IF NOT EXISTS `{$processPrefix}' . $table . '`'), $table . ' is not initialized by generation bootstrap');
}
$fail(str_contains($bootstrap, 'INSERT INTO `{$processPrefix}fm2_pilot_auth_credentials`'), 'credential synchronization is not initialized by generation bootstrap');
$fail(str_contains($launcher, "putenv('PHP_CLI_SERVER_WORKERS=4')"), 'rapid-pilot HTTP runtime is not configured for concurrent requests');
$fail(str_contains($launcher, '-d post_max_size=28M'), 'rapid-pilot HTTP runtime cannot parse a 25 MiB multipart upload');
$fail(str_contains($launcher, '-d upload_max_filesize=25M'), 'rapid-pilot HTTP runtime upload limit differs from the form contract');
$fail(str_contains($launcher, '-d display_errors=0'), 'rapid-pilot HTTP runtime can leak startup warnings into responses');
$fail(!str_contains($queue, 'objectCards()->read'), 'rapid operational queue contains per-object card reads');
$fail(str_contains($queue, 'self::readObjects($db,$prefix,$legacy)'), 'rapid operational queue does not use bounded server-side projection');

echo "PASS auth hot path is schema-mutation free\n";
