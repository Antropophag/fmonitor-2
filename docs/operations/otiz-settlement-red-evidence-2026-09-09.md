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
- The test-only `osc_delay_reversal` trigger holds the winning reversal insert for
  400 ms. Both workers start from one barrier, so the losing command observes no
  receipt before waiting for the same financial-object lock. The side that wins
  scheduling may vary; exactly one command incorrectly returns a refusal.

## Canonical settlement schema

- Command: `php tests/InstallationProcess/otiz_settlement_schema_001_test.php`.
- Result: exit `255` on `INTENDED_RED: OTIZ settlement schema is canonical successor v24`.
- Expected: `ProductionPilotMigrationCatalogue::migrations()[24]` equals
  `OtizSettlementSchemaMigration::class`; actual: `NULL`.
- Setup completed against a disposable MariaDB database; the failure is the
  missing canonical registration, not a database or fixture failure.

This record captures author evidence for independent Gate 3. It does not approve
the tests or authorize implementation.
