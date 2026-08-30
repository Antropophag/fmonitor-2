<?php

declare(strict_types=1);

function initializationEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') throw new RuntimeException("Missing {$name}");
    return $value;
}

function initializationRun(string $script, array $arguments = []): array
{
    $pipes = [];
    $process = proc_open([PHP_BINARY, __DIR__ . '/' . $script, ...$arguments], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__));
    if (!is_resource($process)) throw new RuntimeException('NATIVE_INITIALIZATION_PROCESS_UNAVAILABLE');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException('NATIVE_INITIALIZATION_STEP_FAILED:' . $script);
    $result = json_decode(trim($stdout), true, flags: JSON_THROW_ON_ERROR);
    if (!is_array($result) || ($result['ok'] ?? true) !== true) throw new RuntimeException('NATIVE_INITIALIZATION_STEP_REJECTED:' . $script);
    return $result;
}

$options = getopt('', ['cutoff:', 'batch-size::']);
$cutoff = (string) ($options['cutoff'] ?? '');
$batchSize = filter_var($options['batch-size'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
$parsedCutoff = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $cutoff, new DateTimeZone('Europe/Moscow'));
if (!$parsedCutoff || $parsedCutoff->format('Y-m-d H:i:s') !== $cutoff || $batchSize === false) throw new InvalidArgumentException('Usage: --cutoff="Y-m-d H:i:s" [--batch-size=100]');
foreach (['FMONITOR_PILOT_ACTIVE_MANIFEST', 'FMONITOR_SOURCE_USER', 'FMONITOR_SOURCE_PASSWORD', 'FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD'] as $name) initializationEnv($name);
$manifest = json_decode((string) file_get_contents(initializationEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')), true, flags: JSON_THROW_ON_ERROR);
if (($manifest['mode'] ?? null) !== 'native-only') throw new DomainException('GENERATION_NOT_NATIVE_ONLY');

initializationRun('verify-native-only-generation.php', ['--expect-empty-cases']);
$users = initializationRun('import-production-users.php');
$workforce = initializationRun('import-production-installers.php');
$template = initializationRun('import-checklist-template.php', ['--captured-at=' . $cutoff, '--apply']);
$templateId = filter_var($template['snapshotId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($templateId === false) throw new RuntimeException('NATIVE_TEMPLATE_UNAVAILABLE');

$after = 0; $imported = 0; $eligible = 0;
do {
    $batch = initializationRun('batch-import-native-candidates.php', ['--cutoff=' . $cutoff, '--after-id=' . $after, '--batch-size=' . $batchSize, '--apply']);
    $imported += (int) (($batch['stats']['created'] ?? 0));
    $eligible += (int) (($batch['stats']['eligible'] ?? 0));
    $next = (int) ($batch['nextAfterId'] ?? 0);
    if (($batch['complete'] ?? false) !== true && $next <= $after) throw new RuntimeException('NATIVE_IMPORT_CHECKPOINT_STALLED');
    $after = $next;
} while (($batch['complete'] ?? false) !== true);

$capturedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('Y-m-d\TH:i:sP');
putenv('FMONITOR_LOCAL_PILOT_ACK=local-pilot-only');
$_ENV['FMONITOR_LOCAL_PILOT_ACK'] = 'local-pilot-only';
$details = initializationRun('import-production-object-details.php', ['--captured-at=' . $capturedAt, '--apply']);

$prefix = (string) ($manifest['processPrefix'] ?? '');
if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new RuntimeException('INVALID_GENERATION_PREFIX');
$db = new mysqli(initializationEnv('FMONITOR_DB_HOST'), initializationEnv('FMONITOR_DB_USER'), initializationEnv('FMONITOR_DB_PASSWORD'), initializationEnv('FMONITOR_DB_NAME'), (int) initializationEnv('FMONITOR_DB_PORT'));
$db->set_charset('utf8mb4');
$ids = array_map('intval', array_column($db->query("SELECT legacy_installation_object_id FROM `{$prefix}fm2_installation_cases` ORDER BY id")->fetch_all(MYSQLI_ASSOC), 'legacy_installation_object_id'));
$db->close();
foreach ($ids as $id) initializationRun('link-operational-case-template.php', ['--object-id=' . $id, '--template-snapshot-id=' . $templateId, '--effective-at=' . $cutoff, '--apply']);
$proof = initializationRun('verify-native-only-generation.php');

echo json_encode(['ok' => true, 'mode' => 'native-only-initialization', 'users' => (int) ($users['users'] ?? 0), 'workforce' => (int) ($workforce['delivered'] ?? 0), 'eligibleCandidates' => $eligible, 'createdCases' => $imported, 'caseCount' => (int) ($proof['cases'] ?? 0), 'details' => (int) ($details['sourceRows'] ?? 0), 'templateLinkedCases' => count($ids), 'identifiersExposed' => false], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
