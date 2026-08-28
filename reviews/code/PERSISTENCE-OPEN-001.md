# Code review: PERSISTENCE-OPEN-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved test, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production scope: opening delegates, the `registered → working` handler, shared transaction/event/case helpers, and opening hydration in `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php`; preparation and registration handlers inspected for refactor regression
- Specification: [`specs/PERSISTENCE-OPEN-001.md`](../../specs/PERSISTENCE-OPEN-001.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PERSISTENCE-OPEN-001.md`](../tests/PERSISTENCE-OPEN-001.md), current verdict `APPROVED`
- Inherited persistence: `PERSISTENCE-PREPARE-001`, `PERSISTENCE-REGISTRATION-001`, `OPEN-INSTALLATION-001 v0.2`, and `WORKFORCE-CATALOG-001`
- Previous Gate 5 verdict: `CHANGES_REQUESTED`
- Superseding verdict: `APPROVED`

## Standards

`APPROVED`. The replacement operation now has one explicit transaction owner: it locks the case and latest order, checks the revision, dispatches one transition-specific handler, performs the shared event append and case/revision update exactly once, rolls back every pre-commit error, commits once, and returns once. The private preparation, registration, and opening handlers each own their distinct persistence decisions, while common append/root mechanics are no longer duplicated. This resolves the prior Divergent Change without speculative abstraction.

The opening handler confirms physical composition under the same transaction before shared writes. SQL values are bound, the only interpolated identifier is the constructor-validated prefix, and no opening-path order/child/task or external write is introduced. The root helper preserves the inherited prepare/register update shape and adds opening columns only for `working`. Hydration remains compatible across prepared, registered, and working projections. Unknown commit reconciliation remains explicitly outside this slice.

## Spec

`APPROVED`. After the case lock and expected revision check, the adapter locks the latest exact order and requires the registered version/process/event shape. It then performs a bound `SELECT` with `FOR UPDATE` against `fm2_order_installers` and fails before event or case mutation when no physical composition exists. The retained row lock protects that confirmation until commit.

Only after the integrity guard does the shared flow append the single `installation_opened` event and physically `UPDATE` the existing case with exact opening fields, `working`, timestamp, and revision+1. There is no delete/reinsert or order, installer, artifact, task, Workforce, capability, legacy, or user write. Pre-commit failures roll back; a commit failure is surfaced as unknown. Fresh hydration returns all root opening fields and exact immutable registered history without external reads. The production Workforce delegate remains read-only and is observed exactly for `1042`.

## Verification evidence

Independent commands and results:

```text
php tests/InstallationProcess/persistence_prepare_001_test.php
php tests/InstallationProcess/persistence_registration_001_test.php
php tests/InstallationProcess/persistence_open_001_test.php
# all three persistence-focused tests PASS

for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
# all scoped PHP files passed syntax checks

# all 32 InstallationProcess tests started concurrently in isolated processes
# 32/32 PASS

for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
# 32/32 PASS sequentially

git diff --check -- app/InstallationProcess/MariaDbInstallationProcessEnvironment.php tests/InstallationProcess/persistence_open_001_test.php specs/PERSISTENCE-OPEN-001.md reviews/tests/PERSISTENCE-OPEN-001.md reviews/code/PERSISTENCE-OPEN-001.md
# PASS
```

The working tree remains an intentionally uncommitted handoff. Review was constrained to the stated current production scope and approved artifacts; unrelated existing changes were not reviewed. Short-lived parallel logs were removed after inspection.

## Findings

None. Both previous medium findings are resolved.

## Required changes

None. Gate 5 is `APPROVED`; `PERSISTENCE-OPEN-001` v0.1 is complete.
