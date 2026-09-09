# OTIZ-SETTLEMENT-001 — RED evidence

## Verification plan

- Input: `openspec/changes/otiz-settlement-owner/verification-input.json`.
- Generated ignored plan: `.local/verification/otiz-settlement-owner-plan.json`.
- Command: `python3 tools/delivery/change-verification.py check --plan .local/verification/otiz-settlement-owner-plan.json`.
- Result before the RED runs below: exit `0`, literal `CHANGE_VERIFICATION_OK`.

## Concurrent exact reversal replay

- Command: `php tests/Otiz/settlement_concurrency_001_test.php`.
- Result: exit `255` on `INTENDED_RED: concurrent exact reversal replay returns the same successful outcome`.
- Expected: `[true, true]`; actual on two consecutive runs: `[false, true]` and `[true, false]`.
- A third test connection holds a write table lock on the operation receipts.
  After releasing both workers, the coordinator queries `PROCESSLIST` until both
  database connections are observed waiting in their receipt lookup, then releases
  the table lock. Both commands therefore observe the same missing-receipt start;
  exactly one currently returns a refusal after the financial-object lock.

## Canonical settlement schema

- Command: `php tests/InstallationProcess/otiz_settlement_schema_001_test.php`.
- Result: exit `255` on `INTENDED_RED: OTIZ settlement schema is canonical successor v24`.
- Expected: `ProductionPilotMigrationCatalogue::migrations()[24]` equals
  `OtizSettlementSchemaMigration::class`; actual: `NULL`.
- Setup completed against a disposable MariaDB database; the failure is the
  missing canonical registration, not a database or fixture failure.

This record captures author evidence for independent Gate 3. It does not approve
the tests or authorize implementation.
