<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;

/** Task-owned synthetic source fixture for ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001. */
final class IdentityRegistryTestDatabase
{
    public readonly \mysqli $connection;
    public readonly string $name;
    private \mysqli $admin;
    private array $users = [];

    public function __construct(public readonly string $prefix = '')
    {
        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->admin = $this->connect();
        $this->name = 't_aoir_matrix_' . bin2hex(random_bytes(6));
        $this->admin->query("CREATE DATABASE `{$this->name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            $this->connection = $this->connect($this->name);
            ProductionProcessSchemaMigration::apply($this->connection, $prefix);
            $this->connection->query("ALTER TABLE `{$prefix}fm2_assignment_orders` AUTO_INCREMENT=81");
            $this->connection->query("CREATE TABLE other_prefix_marker (id INT PRIMARY KEY, value VARCHAR(30))");
            $this->connection->query("INSERT INTO other_prefix_marker VALUES(1,'preserve')");
        } catch (\Throwable $error) {
            if (isset($this->connection)) {
                $this->connection->close();
            }
            $this->admin->query("DROP DATABASE `{$this->name}`");
            $this->admin->close();
            throw $error;
        }
    }

    public function connect(?string $database = null): \mysqli
    {
        $db = new \mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
            getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
            getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
            $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
        $db->set_charset('utf8mb4');
        return $db;
    }

    public function seedOrder(): void
    {
        $p = $this->prefix;
        $this->connection->query("INSERT INTO `{$p}fm2_installation_cases` (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
        $this->connection->query("INSERT INTO `{$p}fm2_assignment_orders` (id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES (2,4512,1,'initial','prepared','2026-08-27',73,'Инженер теста','Инженер','individual','Адрес теста','2','77-000123','2026-10-05','2026-12-20','2026-08-27T12:30:00+03:00',18)");
    }

    public function tables(): array
    {
        return array_column($this->connection->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    }

    /** Standard catalog and rows are the approved migration observation seam. */
    public function allState(): array
    {
        $result = [];
        foreach ($this->tables() as $table) {
            $rows = $this->connection->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);
            usort($rows, static fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
            $result[$table] = [$this->connection->query("SHOW CREATE TABLE `$table`")->fetch_row()[1], $rows];
        }
        return $result;
    }

    public function restricted(string $grants): \mysqli
    {
        $user = 'aoir_ro_' . bin2hex(random_bytes(6));
        $this->admin->query("CREATE USER `$user`@`%` IDENTIFIED BY 'fixture-only'");
        $this->users[] = $user;
        $this->admin->query("GRANT $grants ON `{$this->name}`.* TO `$user`@`%`");
        $db = new \mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', $user,
            'fixture-only', $this->name, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
        $db->set_charset('utf8mb4');
        return $db;
    }

    public function close(): void
    {
        $errors = [];
        foreach ([fn () => $this->connection->rollback(), fn () => $this->connection->close(),
            fn () => $this->admin->query("DROP DATABASE `{$this->name}`")] as $cleanup) {
            try { $cleanup(); } catch (\Throwable $error) { $errors[] = $error->getMessage(); }
        }
        foreach ($this->users as $user) {
            try { $this->admin->query("DROP USER `$user`@`%`"); } catch (\Throwable $error) { $errors[] = $error->getMessage(); }
        }
        try {
            $statement = $this->admin->prepare('SELECT COUNT(*) n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
            $name = $this->name;
            $statement->bind_param('s', $name);
            $statement->execute();
            \assertSameValue('0', (string) $statement->get_result()->fetch_assoc()['n'], 'owned matrix schema absent');
            $statement->close();
        } catch (\Throwable $error) { $errors[] = $error->getMessage(); }
        try { $this->admin->close(); } catch (\Throwable $error) { $errors[] = $error->getMessage(); }
        if ($errors !== []) { throw new \TestFailure('Fixture cleanup failed: ' . implode(' | ', $errors)); }
    }
}
