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

## Revoked replay admission

- Command: `php tests/Otiz/settlement_owner_001_test.php`.
- Result against Gate 4 candidate `42001adc`: exit `255` at
  `revoked actor replayed prior success`.
- The fixture first records a successful operation, removes the actor's current
  exact `otiz.manage` permission, and retries the identical operation UUID and
  fingerprint. Returning the saved success is the missing-behavior failure;
  current authority requires `FORBIDDEN` before replay disclosure.

## Yii HTTP settlement route

- Command: `php tests/Yii2/yii2_otiz_settlement_001_test.php`.
- Result after completing the three-command/admission/parsing matrix: exit `255`
  at the first absent protected route.
- Expected for anonymous `GET /pilot/otiz/snapshots/301`: HTTP `303` to
  `/pilot/login`; actual: HTTP `404` with no `Location` because the Yii
  compatibility route/controller is absent.
- Setup completed canonical v24, authenticated an active actor with exact current
  `otiz.manage`, and submitted valid Yii CSRF plus the retained legacy form path
  `/pilot/otiz/snapshots/301/closures`. The remaining assertions cover the real
  authenticated snapshot/form/redirect flow, all three commands, current exact
  permission, CSRF, malformed inputs, exact receipt/closure values and ignored
  arbitrary paid/deadline fields.

The strengthened semantic GET parity test was written after an uncommitted
simplified GET prototype exposed the earlier oracle's weakness. Against that
prototype it exits `255`: expected the retained heading `Выплаты на 30.09.2026`,
actual `false`. The strengthened test also requires snapshot status, object and
money values, navigation/export, complete discipline fields, Yii CSRF and
operation IDs. The prototype is not claimed as test-first work or a candidate.

The corrected oracle scopes `_csrf`, `operationId` and business inputs to their
exact closure/complete forms, and proves the complete form excludes discipline
inputs. After the successful closure redirect it requires the retained flash,
object, amount, basis, artifact and a reverse form whose own action contains
CSRF, operation UUID and reversal basis. The fresh run remains exit `255` at the
earlier missing retained heading, before these downstream assertions.

## Root-authored GET form correction — 2026-09-10

Owner changed authorship: root now writes specifications/tests; separately tasked
agents retain implementation and independent review. Earlier authorship remains
historical. This correction addresses review `eda63661` without changing UI scope.

The exact-action DOM form must use POST and contain one enabled, hidden, nonempty
CSRF control and one canonical lowercase v4 operation UUID. Successful complete
and reverse requests now use the CSRF token from their own rendered form; an
unrelated token elsewhere cannot satisfy the public request. Discipline already
submits its own token. Visible artifact text is no longer required because the
legacy renderer does not show it; persisted artifact checks remain unchanged.

Command: `php tests/Yii2/yii2_otiz_settlement_001_test.php`.
Before run: regenerated required plan, `check` printed `CHANGE_VERIFICATION_OK`.
Actual run: exit `255` against the unchanged uncommitted prototype, at line 44:

```text
TestFailure: snapshot semantic parity preserves Выплаты на 30.09.2026
Expected: true
Actual: false
```

This remains intended missing retained-UI RED, with the downstream form assertions
reviewable in source. No production correction was made by the test author.
