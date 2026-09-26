<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV36
{
    public const VERSION = 36;
    public const DEFERRED = RuntimeRecoverySchemaV35::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV35::tables(''), 'fm2_otiz_admission_inputs', 'fm2_otiz_calculation_revisions', 'fm2_otiz_deductions', 'fm2_otiz_entitlement_claims', 'fm2_otiz_payment_decisions', 'fm2_otiz_payment_facts', 'fm2_otiz_payment_reversals', 'fm2_otiz_recipient_obligations', 'fm2_otiz_unlinked_legacy_totals', 'fm2_otiz_v2_events', 'fm2_otiz_v2_operations'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix . $table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV35::autoIncrement(''), 'fm2_otiz_calculation_revisions', 'fm2_otiz_deductions', 'fm2_otiz_entitlement_claims', 'fm2_otiz_payment_decisions', 'fm2_otiz_payment_facts', 'fm2_otiz_payment_reversals', 'fm2_otiz_recipient_obligations', 'fm2_otiz_v2_events'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix . $table, $tables);
    }
}