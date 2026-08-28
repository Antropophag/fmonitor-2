# Code review: PERSISTENCE-REGISTRATION-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved test, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production scope: registration persistence branch and registered-order hydration changes in `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php`; prior persistence code inspected only for transaction/regression context
- Specification: [`specs/PERSISTENCE-REGISTRATION-001.md`](../../specs/PERSISTENCE-REGISTRATION-001.md), version `0.2`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PERSISTENCE-REGISTRATION-001.md`](../tests/PERSISTENCE-REGISTRATION-001.md), current v0.2 verdict `APPROVED`
- Inherited persistence: `PERSISTENCE-PREPARE-001` v0.3, Gate 5 `APPROVED`
- Verdict: `APPROVED`

## Standards

`APPROVED`. Under one transaction, the adapter locks the case and latest exact order, checks the expected revision and `prepared → registered` shape, and executes one constrained physical `UPDATE` against the existing order row. It never deletes/reinserts the order and never writes installer or artifact rows. The registration event and case timestamp/revision update occur before the same commit; an affected-row mismatch or other pre-commit error fails closed through rollback.

All SQL values are bound and table-prefix use remains within the established validated adapter boundary. The branch exits before preparation insertion logic and introduces no external/legacy/catalog write. Registered hydration is conditional and preserves the prepared projection. Keeping the two write modes in the generic revision replacement method is cohesive enough for the current two real transitions; extraction would be optional refactoring, not a blocking Divergent Change smell. No security, atomicity, history, or maintainability finding was found.

## Spec

`APPROVED`. Code inspection confirms the physical append-only invariant required by sections 5 and 9:

- case row is locked and its exact expected revision checked;
- the latest stored order is locked and must be the same version, stored `prepared`, with requested domain state `registered` and a final registration event;
- a single `UPDATE ... WHERE id=? AND installation_case_id=? AND version_no=? AND status='prepared'` changes only status/registration fields, with exactly one affected row required;
- no order/installer/artifact delete or reinsertion occurs and no version 2 is created;
- one registration event insert and only case `process_state`, `updated_at`, and incremented revision are committed atomically with the order update;
- exceptions do not produce business success; detailed rollback/unknown-COMMIT semantics remain explicitly outside v0.2.

Hydration adds all registration metadata only for `registered` rows, keeps prepared rows compatible, casts the manual actor ID back to the public integer, and loads all children/events from their original rows. The public integration test proves durable same-version registration, full immutable projection, exact two-event history, unchanged state/tasks/gates, fresh-connection reload without external calls, and no writes to external current facts. No missing, extra, or incorrect behavior remains.

## Verification evidence

Commands run independently:

```text
php tests/InstallationProcess/persistence_prepare_001_test.php
php tests/InstallationProcess/persistence_registration_001_test.php
# all 29 InstallationProcess tests started concurrently in isolated processes; every log reported PASS
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
git diff --check -- app/InstallationProcess/MariaDbInstallationProcessEnvironment.php tests/InstallationProcess/persistence_registration_001_test.php specs/PERSISTENCE-REGISTRATION-001.md reviews/tests/PERSISTENCE-REGISTRATION-001.md
```

Results: both persistence-focused tests passed; all 29 tests passed concurrently and sequentially; all scoped PHP syntax checks and scoped `git diff --check` passed. Short-lived parallel logs were removed after inspection.

## Findings

None.

## Required changes

None. Gate 5 is `APPROVED`; `PERSISTENCE-REGISTRATION-001` v0.2 is complete.
