# PRODUCTION-RUNTIME-SCHEMA-PREFLIGHT-001 — Gate 3 review

- Reviewer: `/root/runtime_tests`
- Test author: `/root/runtime_review`
- Reviewed artifact: `tests/Runtime/production_schema_preflight_001_test.php`
- SHA-256: `d92c90f7b64b4980272e5204dec9b080a1dca1002b67416c535f264717cb39f9`
- Verdict: **APPROVED**

The expanded test isolates four same-name, same-order ten-column near-predecessors:
wrong original type, missing `id` primary key, primary key on the wrong column, and
wrong engine plus charset. Every case preserves a populated row and the same
unrelated ambient table, requires the stable `SCHEMA_MIGRATION_CONFLICT` outcome,
and compares `SHOW CREATE TABLE` plus all rows before and after. It catches
column-name-only and partial metadata preflights, attempted additive repair, and
collateral mutation without prescribing the production implementation. The matrix
is focused on the contract's material transaction/identity properties; it does not
claim exhaustive default, secondary-index, or collation coverage.

Focused RED command:

```text
php tests/Runtime/production_schema_preflight_001_test.php
```

Observed exit: `255`.

Observed failure:

```text
Uncaught FMonitor2\InstallationProcess\DatabaseUnavailable:
Pilot legacy object schema is unavailable.
app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php:28

TestFailure: INTENTIONAL_RED: missing id primary key predecessor conflicts before ALTER
Expected: ['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT']
Actual: ['applied' => true, 'columnsAdded' => 7]
tests/Runtime/production_schema_preflight_001_test.php:43
```

The fixture completed healthy database setup before the failure. The wrong-type
case passes after the first production correction; the current migration still
accepts the missing-primary-key shape and performs the seven-column `ALTER`. This
is the intended production-owned RED for complete predecessor validation before
mutation.
