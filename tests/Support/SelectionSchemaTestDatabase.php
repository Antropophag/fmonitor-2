<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration;

/** Literal Gate1 fixtures own setup only; target actions use the selection public facade. */
final class SelectionSchemaTestDatabase
{
    public readonly IdentityRegistryTestDatabase $source;
    public readonly \mysqli $db;
    public readonly array $manifest;
    public readonly array $example;

    public function __construct(public readonly string $prefix = '')
    {
        $this->source = new IdentityRegistryTestDatabase($prefix);
        $this->db = $this->source->connection;
        try {
            $this->db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->manifest = json_decode(file_get_contents(dirname(__DIR__, 2) . '/specs/fixtures/assignment-order-selection-schema-v1.json'), true, 512, JSON_THROW_ON_ERROR)['tables'];
            $this->example = json_decode(file_get_contents(dirname(__DIR__, 2) . '/specs/fixtures/assignment-order-selection-example-v1.json'), true, 512, JSON_THROW_ON_ERROR);
            $this->db->query("INSERT INTO `{$prefix}fm2_installation_cases` (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
            \assertSameValue(['applied' => true], AssignmentOrderIdentityRegistryMigration::apply($this->db, $prefix), 'approved registry setup');
            \assertSameValue(true, AssignmentOrderIdentityRegistryMigration::isBackfillComplete($this->db, $prefix), 'registry setup complete');
        } catch (\Throwable $failure) {
            try { $this->source->close(); } catch (\Throwable $cleanup) {
                throw new \TestFailure($failure->getMessage() . ' | ' . $cleanup->getMessage());
            }
            throw $failure;
        }
    }

    public function name(int $index): string
    {
        return str_replace('@prefix', $this->prefix, $this->manifest[$index]['name']);
    }

    public function create(int $count = 5): void
    {
        for ($i = 0; $i < $count; $i++) { $this->createTable($i); }
    }

    public function createTable(int $index): void
    {
        $table = $this->manifest[$index]; $definitions = [];
        foreach ($table['columns'] as $column) {
            $sql = '`' . $column['name'] . '` ' . $column['type'];
            if ($column['charset'] !== null) {
                $sql .= ' CHARACTER SET ' . $column['charset'] . ' COLLATE ' . str_replace('@collation', 'utf8mb4_unicode_ci', $column['collation']);
            }
            $definitions[] = $sql . ($column['nullable'] ? ' NULL' : ' NOT NULL') . ' ' . $column['extra'];
        }
        foreach ($table['indexes'] as $key) {
            $head = $key['name'] === 'PRIMARY' ? 'PRIMARY KEY' : ($key['unique'] ? 'UNIQUE KEY ' : 'KEY ') . '`' . str_replace('@prefix', $this->prefix, $key['name']) . '`';
            $definitions[] = $head . ' (' . self::columns($key['columns']) . ')';
        }
        foreach ($table['foreignKeys'] as $key) {
            $definitions[] = 'CONSTRAINT `' . str_replace('@prefix', $this->prefix, $key['name']) . '` FOREIGN KEY (' . self::columns($key['columns']) . ') REFERENCES `' . str_replace('@prefix', $this->prefix, $key['table']) . '` (' . self::columns($key['target']) . ') ON UPDATE ' . $key['update'] . ' ON DELETE ' . $key['delete'];
        }
        foreach ($table['checks'] as $check) {
            $definitions[] = 'CONSTRAINT `' . str_replace('@prefix', $this->prefix, $check['name']) . '` CHECK (' . $check['expression'] . ')';
        }
        $this->db->query('CREATE TABLE `' . $this->name($index) . '` (' . implode(',', $definitions) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private static function columns(array $names): string
    {
        return implode(',', array_map(static fn ($n) => '`' . $n . '`', $names));
    }

    public function insert(string $table, array $row): void
    {
        $sql = 'INSERT INTO `' . $table . '` (' . self::columns(array_keys($row)) . ') VALUES (' . implode(',', array_fill(0, count($row), '?')) . ')';
        $statement = $this->db->prepare($sql);
        try { $statement->execute(array_values($row)); } finally { $statement->close(); }
    }

    public function populate(int $count = 5): void
    {
        foreach ($this->example['registryAdditions'] as $row) {
            $this->insert($this->prefix . 'fm2_assignment_order_identities', $row);
        }
        for ($i = 0; $i < $count; $i++) {
            $base = str_replace('@prefix', '', $this->manifest[$i]['name']);
            foreach ($this->example['rows'][$base] as $row) { $this->insert($this->prefix . $base, $row); }
        }
        foreach ($this->example['nextIds'] as $base => $next) {
            $table = $this->prefix . ($base === 'registry' ? 'fm2_assignment_order_identities' : $base);
            if (in_array($table, $this->source->tables(), true)) { $this->db->query("ALTER TABLE `$table` AUTO_INCREMENT=$next"); }
        }
    }

    public function state(): array { return $this->source->allState(); }
    public function close(): void { $this->source->close(); }
}
