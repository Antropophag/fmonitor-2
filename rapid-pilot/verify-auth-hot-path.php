<?php

declare(strict_types=1);

$auth = file_get_contents(__DIR__ . '/LocalAuth.php');
$bootstrap = file_get_contents(__DIR__ . '/docker-bootstrap.php');
$identityBootstrap = file_get_contents(__DIR__ . '/IdentityBootstrap.php');
$identityBootstrapApplication = file_get_contents(dirname(__DIR__) . '/app/PilotHttp/MariaDbIdentityBootstrapApplication.php');
$launcher = file_get_contents(__DIR__ . '/start.php');
$queue = file_get_contents(__DIR__ . '/ObjectQueue.php');
if (!is_string($auth) || !is_string($bootstrap) || !is_string($identityBootstrap) || !is_string($identityBootstrapApplication) || !is_string($launcher) || !is_string($queue)) throw new RuntimeException('Auth sources unavailable');

$fail = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$constructorBody = static function (string $source) use ($fail): string {
    $signature = '/public\s+function\s+__construct\(\?FMonitor\\\\IdentityAccess\\\\PilotSessionStorage\s+\$storage\s*=\s*null\)\s*\{/D';
    $matched = preg_match($signature, $source, $match, PREG_OFFSET_CAPTURE);
    $fail($matched === 1, 'LocalAuth constructor must keep the exact optional typed PilotSessionStorage signature');
    $constructorStart = $match[0][1];
    $bodyStart = $constructorStart + strlen($match[0][0]);
    $constructorEnd = strpos($source, 'public function handle(', $bodyStart);
    $fail($constructorEnd !== false, 'LocalAuth handle boundary unavailable after constructor');
    return substr($source, $bodyStart, $constructorEnd - $bodyStart);
};
$assertConstructorHotPath = static function (string $source) use ($constructorBody, $fail): void {
    $constructor = $constructorBody($source);
    $fail(!str_contains($constructor, 'CREATE TABLE'), 'request-time auth constructor contains DDL');
    $fail(!str_contains($constructor, 'INSERT INTO'), 'request-time auth constructor contains bulk synchronization');
    $fail(!str_contains($constructor, 'ensureSchema'), 'request-time auth constructor invokes schema bootstrap');
};
$assertConstructorHotPath($auth);
$constructorOpen = strpos($auth, '{', strpos($auth, 'public function __construct'));
$fail($constructorOpen !== false, 'LocalAuth constructor opening brace unavailable for sensitivity probe');
$mutatedAuth = substr_replace($auth, "\n        CREATE TABLE injected_constructor_ddl(id INT);", $constructorOpen + 1, 0);
$mutationRejected = false;
try { $assertConstructorHotPath($mutatedAuth); } catch (RuntimeException $error) { $mutationRejected = $error->getMessage() === 'request-time auth constructor contains DDL'; }
$fail($mutationRejected, 'constructor DDL sensitivity mutation must fail closed');
$fail(!str_contains($auth, 'private function ensureSchema'), 'request-time schema bootstrap remains reachable');

$fail(
    preg_match('/IdentityAccessSchemaMigration::apply\(\$db,\s*\$prefix\)/', $identityBootstrapApplication) === 1,
    'generation bootstrap does not require canonical identity/access schema readiness'
);
$fail(!str_contains($identityBootstrap, 'CREATE TABLE'), 'rapid-pilot identity adapter still owns schema DDL');
$fail(!str_contains($identityBootstrapApplication, 'CREATE TABLE'), 'normal identity bootstrap application contains schema DDL');
$fail(
    str_contains($identityBootstrapApplication, 'INSERT INTO `{$prefix}fm2_pilot_auth_credentials`'),
    'credential bootstrap is not initialized'
);
$fail(str_contains($identityBootstrapApplication, "'active'"), 'bootstrap superadministrator is not activated immediately');
$fail(str_contains($identityBootstrapApplication, 'PASSWORD_ARGON2ID'), 'bootstrap password is not hashed with Argon2id');
$fail(str_contains($bootstrap, 'FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD'), 'bootstrap password is not supplied by deployment configuration');
$fail(!str_contains($bootstrap, 'Bootstrap activation'), 'bootstrap superadministrator still uses invitation flow');
$fail(!str_contains($auth, 'provisionUser'), 'open self-registration remains reachable');
$fail(str_contains($auth, 'fm2_pilot_invitations'), 'invitation-only activation is unavailable');
$fail(str_contains($launcher, "putenv('PHP_CLI_SERVER_WORKERS=4')"), 'rapid-pilot HTTP runtime is not configured for concurrent requests');
$fail(str_contains($launcher, '-d post_max_size=28M'), 'rapid-pilot HTTP runtime cannot parse a 25 MiB multipart upload');
$fail(str_contains($launcher, '-d upload_max_filesize=25M'), 'rapid-pilot HTTP runtime upload limit differs from the form contract');
$fail(str_contains($launcher, '-d display_errors=0'), 'rapid-pilot HTTP runtime can leak startup warnings into responses');
$fail(!str_contains($queue, 'objectCards()->read'), 'rapid operational queue contains per-object card reads');
$fail(str_contains($queue, 'self::readObjects($db,$prefix,$legacy)'), 'rapid operational queue does not use bounded server-side projection');

echo "PASS auth hot path is schema-mutation free\n";
