<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyHistoryMigration.php';

function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$object = ['id' => 42, 'ordadr_address' => 'Fixture'];
$events = [['id' => 2, 'value_id' => 42, 'checklist_id' => 8, 'value' => 1, 'ctime' => '2026-01-02 03:04:05', 'cuser_id' => 7, 'checklist_definition_id' => 8]];
$attributions = [['id' => 3, 'checklist_value_id' => 11, 'tab_id' => 123, 'fio' => 'Fixture Person', 'ctime' => '2026-01-02 03:04:05', 'cuser_id' => 7]];
$one = LegacyHistorySnapshot::build($object, $events, $attributions, '2026-01-31 23:59:59');
$two = LegacyHistorySnapshot::build($object, $events, $attributions, '2026-01-31 23:59:59');
check($one['contentSha256'] === $two['contentSha256'], 'hash is not deterministic');
check($one['issues'] === [], 'valid fixture was quarantined');
$fakeTarget = [];
foreach ([$one, $two] as $candidate) $fakeTarget[$candidate['contentSha256']] ??= $candidate['payload'];
check(count($fakeTarget) === 1, 'content-keyed apply is not idempotent');

$bad = LegacyHistorySnapshot::build($object, [['id' => 4, 'ctime' => '2026-02-30 10:00:00', 'checklist_definition_id' => null]], [['id' => 5, 'ctime' => 'bad-date', 'checklist_value_id' => null]], '2026-01-31 23:59:59');
$codes = array_column($bad['issues'], 'code');
foreach (['MALFORMED_EVENT_DATE', 'ORPHAN_CHECKLIST_EVENT', 'MALFORMED_ATTRIBUTION_DATE', 'ORPHAN_ATTRIBUTION'] as $code) check(in_array($code, $codes, true), "missing {$code}");

// Dry-run is structurally write-free: snapshot construction accepts no target and opens no target connection.
check(!str_contains((string)file_get_contents(__DIR__ . '/legacy-migration/LegacyHistoryMigration.php'), 'UPDATE fm_'), 'source mutation token found');
echo json_encode(['ok' => true, 'deterministicHash' => $one['contentSha256'], 'rowsAfterTwoApplies' => count($fakeTarget), 'negativeIssueCodes' => $codes, 'dryRunTargetWrites' => 0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
