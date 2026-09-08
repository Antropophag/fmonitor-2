<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as Verification;
use FMonitor2\InstallationProcess\DatabaseUnavailable;

final class SelectionSchemaAssertions
{
    private static int $failed = 0;
    private static int $passed = 0;

    public static function run(string $name, callable $scenario, string $prefix = ''): void
    {
        $fixture = null; $errors = [];
        try { $fixture = new SelectionSchemaTestDatabase($prefix); $scenario($fixture); }
        catch (\Throwable $error) { $errors[] = get_class($error) . ': ' . $error->getMessage(); }
        if ($fixture !== null) {
            try { $fixture->close(); } catch (\Throwable $error) { $errors[] = 'CLEANUP: ' . $error->getMessage(); }
        }
        if ($errors === []) { self::$passed++; echo "PASS $name\n"; }
        else { self::$failed++; echo "FAIL $name: " . implode(' | ', $errors) . "\n"; }
    }

    public static function missing(): void
    {
        \assertSameValue(true, is_callable([Migration::class, 'apply']), 'RED_ASSERTION: selection schema public engine missing after native fixture setup');
    }

    public static function conflict(SelectionSchemaTestDatabase $f): void
    {
        $before = $f->state(); self::missing();
        \assertSameValue(['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT'], Migration::apply($f->db, $f->prefix), 'exact conflict');
        \assertSameValue(false, Migration::isReady($f->db, $f->prefix), 'conflict not ready');
        self::unavailable(fn () => Verification::snapshot($f->db, $f->prefix));
        \assertSameValue($before, $f->state(), 'conflict/read paths preserve all existing schema rows and counters');
    }

    public static function unavailable(callable $action): void
    {
        try { $action(); throw new \TestFailure('Expected fixed unavailable'); }
        catch (DatabaseUnavailable $error) {
            \assertSameValue(['Assignment order selection schema unavailable.', 0, null],
                [$error->getMessage(),$error->getCode(),$error->getPrevious()], 'fixed unavailable without native exception');
        }
    }

    public static function finish(): never
    {
        echo 'SELECTION_SCHEMA_MATRIX passed=' . self::$passed . ' failed=' . self::$failed . "\n";
        exit(self::$failed === 0 ? 0 : 1);
    }
}
