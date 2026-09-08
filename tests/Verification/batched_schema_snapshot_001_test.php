<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\BatchedSchemaSnapshot;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!class_exists(BatchedSchemaSnapshot::class)) {
    throw new TestFailure('INTENDED_RED: batched schema snapshot test-support helper is absent.');
}

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_batched_schema_' . bin2hex(random_bytes(6));
$quote = static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`';
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');
$created = false;

try {
    assertSameValue(1, preg_match('/^t_batched_schema_[0-9a-f]{12}$/D', $database), 'Cleanup target is independently bounded.');
    $admin->query('CREATE DATABASE ' . $quote($database) . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;

    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    $db->query("CREATE TABLE `parent` (
        tenant_id BIGINT UNSIGNED NOT NULL,
        code VARCHAR(20) NOT NULL,
        nullable_value VARCHAR(30) NULL DEFAULT NULL,
        literal_null VARCHAR(30) NULL DEFAULT 'NULL',
        PRIMARY KEY (tenant_id, code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE `child` (
        id BIGINT UNSIGNED NOT NULL,
        tenant_id BIGINT UNSIGNED NOT NULL,
        parent_code VARCHAR(20) NOT NULL,
        score INT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY `uq_child_parent` (tenant_id, parent_code),
        KEY `ix_child_score_parent` (score, parent_code),
        CONSTRAINT `fk_child_parent` FOREIGN KEY (tenant_id, parent_code)
            REFERENCES `parent` (tenant_id, code) ON UPDATE RESTRICT ON DELETE RESTRICT,
        CONSTRAINT `ck_child_score` CHECK (score >= 0),
        CONSTRAINT `ck_child_code` CHECK (CHAR_LENGTH(parent_code) > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $normalizedClauses = [];
    $normalizeCheck = static function (string $clause) use (&$normalizedClauses): string {
        $normalizedClauses[] = $clause;
        $lower = strtolower($clause);
        if (str_contains($lower, 'char_length') && str_contains($lower, 'parent_code')) return 'parent-code-nonempty';
        if (str_contains($lower, 'score')) return 'score-nonnegative';
        throw new TestFailure('Unexpected CHECK clause supplied to the caller-owned normalizer: ' . $clause);
    };

    $snapshot = BatchedSchemaSnapshot::read($db, $normalizeCheck);
    assertSameValue(
        [
            'child' => [
                'table' => ['InnoDB', 'utf8mb4_unicode_ci'],
                'columns' => [
                    ['id', 'bigint(20) unsigned', 'NO', null, null, null, ''],
                    ['tenant_id', 'bigint(20) unsigned', 'NO', null, null, null, ''],
                    ['parent_code', 'varchar(20)', 'NO', 'utf8mb4', 'utf8mb4_unicode_ci', null, ''],
                    ['score', 'int(11)', 'YES', null, null, null, ''],
                ],
                'keys' => [
                    ['INDEX', 'score,parent_code'],
                    ['PRIMARY', 'id'],
                    ['UNIQUE', 'tenant_id,parent_code'],
                ],
                'foreignKeys' => [
                    ['parent_code', 'parent', 'code', 'RESTRICT/RESTRICT'],
                    ['tenant_id', 'parent', 'tenant_id', 'RESTRICT/RESTRICT'],
                ],
                'checks' => ['parent-code-nonempty', 'score-nonnegative'],
            ],
            'parent' => [
                'table' => ['InnoDB', 'utf8mb4_unicode_ci'],
                'columns' => [
                    ['tenant_id', 'bigint(20) unsigned', 'NO', null, null, null, ''],
                    ['code', 'varchar(20)', 'NO', 'utf8mb4', 'utf8mb4_unicode_ci', null, ''],
                    ['nullable_value', 'varchar(30)', 'YES', 'utf8mb4', 'utf8mb4_unicode_ci', null, ''],
                    ['literal_null', 'varchar(30)', 'YES', 'utf8mb4', 'utf8mb4_unicode_ci', "'NULL'", ''],
                ],
                'keys' => [['PRIMARY', 'tenant_id,code']],
                'foreignKeys' => [],
                'checks' => [],
            ],
        ],
        $snapshot,
        'Batched reader reproduces the complete legacy snapshot structure and normalization.',
    );
    assertSameValue(2, count($normalizedClauses), 'Every CHECK expression is delegated to the caller-owned normalizer.');

    $db->query('CREATE TABLE `added_after_first_read` (id INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $afterTableAdd = BatchedSchemaSnapshot::read($db, $normalizeCheck);
    assertSameValue(true, array_key_exists('added_after_first_read', $afterTableAdd), 'A later table addition is visible.');

    $db->query('ALTER TABLE `child` ADD COLUMN note VARCHAR(40) NULL DEFAULT NULL, ADD KEY `ix_child_note` (note), DROP FOREIGN KEY `fk_child_parent`, DROP CONSTRAINT `ck_child_score`');
    $afterAlter = BatchedSchemaSnapshot::read($db, $normalizeCheck);
    assertSameValue('note', $afterAlter['child']['columns'][4][0], 'A later column addition is visible in ordinal order.');
    assertSameValue(true, in_array(['INDEX', 'note'], $afterAlter['child']['keys'], true), 'A later index addition is visible.');
    assertSameValue([], $afterAlter['child']['foreignKeys'], 'A later foreign-key removal is visible.');
    assertSameValue(['parent-code-nonempty'], $afterAlter['child']['checks'], 'A later CHECK removal is visible.');

    $db->query('ALTER TABLE `child` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
    $afterCollation = BatchedSchemaSnapshot::read($db, $normalizeCheck);
    assertSameValue(['InnoDB', 'utf8mb4_bin'], $afterCollation['child']['table'], 'A changed table collation is read freshly.');
    assertSameValue('utf8mb4_bin', $afterCollation['child']['columns'][2][4], 'Converted column collation is read freshly.');
    assertSameValue(['InnoDB', 'utf8mb4_unicode_ci'], $afterCollation['parent']['table'], 'Unchanged table properties stay associated with their own table.');

    $db->query('DROP TABLE `added_after_first_read`');
    $afterTableDrop = BatchedSchemaSnapshot::read($db, $normalizeCheck);
    assertSameValue(false, array_key_exists('added_after_first_read', $afterTableDrop), 'A later table removal is visible.');
    fwrite(STDOUT, "BATCHED_SCHEMA_SNAPSHOT_001_OK\n");
} finally {
    try {
        if (isset($db)) $db->close();
    } finally {
        try {
            if ($created) $admin->query('DROP DATABASE ' . $quote($database));
        } finally {
            $admin->close();
        }
    }
}
