# Code review: PERSISTENCE-PREPARE-001

- Reviewer: `Codex agent /root/persistence_code_review` (independent v0.3 re-review; did not author the specification, approved test, documentation, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PERSISTENCE-PREPARE-001.md`](../../specs/PERSISTENCE-PREPARE-001.md), version `0.3`, `APPROVED`
- Approved test review: [`reviews/tests/PERSISTENCE-PREPARE-001.md`](../tests/PERSISTENCE-PREPARE-001.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/MariaDbInstallationProcessEnvironment.php`](../../app/InstallationProcess/MariaDbInstallationProcessEnvironment.php)
- Test: [`tests/InstallationProcess/persistence_prepare_001_test.php`](../../tests/InstallationProcess/persistence_prepare_001_test.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/persistence_prepare_001_test.php
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file"; done
php -l app/InstallationProcess/MariaDbInstallationProcessEnvironment.php
git diff --check -- app/InstallationProcess/MariaDbInstallationProcessEnvironment.php tests/InstallationProcess/persistence_prepare_001_test.php specs/PERSISTENCE-PREPARE-001.md docs/fmonitor-2-pilot-data-model.md reviews/code/PERSISTENCE-PREPARE-001.md
```

Observed result: focused MariaDB test printed `PASS PERSISTENCE-PREPARE-001 MariaDB tracer bullet`; the complete `tests/InstallationProcess` suite printed 23 PASS lines; syntax and scoped whitespace checks exited `0`.

## Standards

No documented-standard or blocking integration-boundary violation remains.

- The approved v0.3 specification and `docs/fmonitor-2-pilot-data-model.md` are the normative process-schema contract for this SSD/TDD slice. `README.md`, the header of `app/demo/schema.sql`, and `docs/fmonitor-2-session-handoff.md` explicitly classify `app/demo/` as a throwaway copied migration baseline, not behavior or schema that has passed the required gates. Its differing legacy table shape therefore does not override the newly approved process schema and is not a production-adapter defect in this slice.
- SQL is confined to the documented, safely prefixed `fm2_installation_cases`, `fm2_assignment_orders`, `fm2_order_installers`, `fm2_order_artifacts`, and `fm2_process_events` tables. Legacy-object, workforce, user-directory, authorization, clock, and renderer facts are delegated through the composed environment boundary.
- `COMMIT` is outside the transaction-body catch. Revision mismatch and infrastructure failures fail closed with generic out-of-slice exceptions; they do not expose the typed retryable concurrency, confirmed-rollback, or unknown-commit outcomes deferred by v0.3. No operation ID or reconciliation result is persisted, and lookup returns `null`.

The compact one-line mappings and names such as `$s`, `$i`, `$a`, and `$at` at `MariaDbInstallationProcessEnvironment.php:116-131` are a possible low-severity `Mysterious Name` readability smell. It is localized and does not make this narrow adapter behavior ambiguous enough to block the slice.

## Specification

- The command persists the approved first prepared version in one MariaDB transaction: case state, immutable object and engineer snapshots, installer snapshot, two artifact metadata rows, and exactly one success event. It does not update legacy `installator*` fields.
- Observation through a new adapter, module instance, and DB connection reconstructs the exact approved `ORDER-PREPARE-002` result from process tables. The reload delegate throws for every external-fact call, proving historical snapshots and artifact metadata are not rehydrated from legacy/workforce/user/renderer state.
- The adapter's normalized columns and tables match the approved v0.3 pilot data-model contract. Test setup creates only uniquely prefixed instances of those documented process tables and uses no SQL assertions.
- External production integrations are correctly left to separate delegates and are explicitly outside this slice. Optimistic-concurrency outcomes, confirmed rollback, unknown commit, persisted `preparationOperationId`, and reconciliation remain without public production behavior in this adapter.
- The engineer's persisted identity, name, and position are the snapshot fields defined by the approved data model. `active = true` and the fixed `construction_control_engineer` role in the projection are derivable consequences of the eligibility invariant required before the order can be persisted, not omitted mutable snapshot fields.

## Test sensitivity

The approved test fails for missing durable writes, partial or external rehydration, duplicate/missing order facts, incorrect snapshots, artifact metadata or hashes, assignments, process state, work/checklist gates, or success audit. Fixed facts and renderer bytes keep business expectations deterministic; randomness affects only the isolated technical table prefix.

## Required changes

None for `PERSISTENCE-PREPARE-001` version `0.3`.

Gate 5 is approved. The slice is complete with the independently reproduced green focused test and relevant regression suite.
