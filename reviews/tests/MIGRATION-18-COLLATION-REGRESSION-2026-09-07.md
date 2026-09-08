# Migration 18 binary-collation regression evidence

Scope: the public `AssignmentOrderSelectionUnknownEmploymentSchemaMigration` seam on
fresh canonical predecessors, with no demo bootstrap or stand mutation.

## RED

Command:

```text
python3 ~/.local/state/fmonitor2/manual-pilot-20260907/runtime/run-diagnostic.py tests/InstallationProcess/assignment_order_unknown_employment_schema_collation_001_test.php
```

Before the fix, `utf8mb4_general_ci` completed and the following `utf8mb4_bin`
case threw `Assignment order unknown-employment schema unavailable` from migration
18. The catalog replaced an actual `utf8mb4_bin` value with `@collation` whenever
it equalled the database default, including the `full_snapshot_json` column whose
contract requires literal `utf8mb4_bin`.

## GREEN

The catalog now applies `@collation` normalization only where the expected column
declares that placeholder.

```text
ASSIGNMENT_ORDER_UNKNOWN_EMPLOYMENT_SCHEMA_COLLATION_001_OK
selection_unknown_employment_manual_pilot_test: PASS
```

The original fresh-demo diagnostic subsequently reported both `V18
{"applied":true}` and `V19 {"applied":true,...}`. That private diagnostic intentionally replaces the canonical result with an empty array
after printing each migration, so its subsequent generic exception is a diagnostic
artifact. The unmodified provisioning path then passed on the binary-collation
fixture (`PROVISION_PASS 1.6s`). This does not claim the whole demo bootstrap is green.
