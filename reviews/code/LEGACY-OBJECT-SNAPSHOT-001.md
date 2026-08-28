# Code review: LEGACY-OBJECT-SNAPSHOT-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved test, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production diff: `git diff --no-index /dev/null app/InstallationProcess/MariaDbLegacyInstallationObject.php` (the intentionally dirty handoff outside this exact file was excluded)
- Specification: [`specs/LEGACY-OBJECT-SNAPSHOT-001.md`](../../specs/LEGACY-OBJECT-SNAPSHOT-001.md), version `0.2`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/LEGACY-OBJECT-SNAPSHOT-001.md`](../tests/LEGACY-OBJECT-SNAPSHOT-001.md), current v0.2 verdict `APPROVED`
- Inherited contracts: `PERSISTENCE-PREPARE-001` v0.3 and `MIGRATION-PROCESS-001` v0.1
- Verdict: `APPROVED`

## Standards

`APPROVED`. The adapter is a cohesive private MariaDB boundary: one parameterized read against fixed identifiers, no legacy DDL/DML, and no raw SQL or legacy values exposed through the domain/audit interface. Missing rows and invalid dates fail closed. Query, mapping, adjusted-plan fallback, and shared date normalization are compact and domain-named. No documented-standard breach, security/integration issue, or material Fowler smell was found.

## Spec

`APPROVED`. The production adapter:

- performs one bound integer equality lookup by `fm_maintable.id` and selects exactly identity plus the seven approved snapshot inputs;
- never reads or substitutes `workdatefinish`;
- trims only the three textual field edges;
- gives a nonzero `workdateendadjusted` priority over `plan_finish_date`;
- maps null, empty/whitespace, numeric/string repeated zeros, and zero-date prefixes to absence;
- extracts leading calendar components without timezone conversion, validates Gregorian dates, and fails closed on unrecognized nonempty input;
- performs no legacy write and returns only the established internal snapshot shape.

The public-seam test sensitively covers both approved examples, complete inherited command/projection behavior, adjusted/base-plan priority, actual-finish exclusion, zero normalization, source immutability, and reload through a new connection without another legacy read. Nonzero PТО mapping is intentionally not a command-reachable executable example under specification section 5 and does not block this slice. No authorization, audit/history, durability, SQL-injection, date-semantic, or scope-creep deviation was found.

## Verification evidence

Commands run independently:

```text
php -l app/InstallationProcess/MariaDbLegacyInstallationObject.php
php tests/InstallationProcess/legacy_object_snapshot_001_test.php
for run in 1 2 3; do php tests/InstallationProcess/legacy_object_snapshot_001_test.php >"/tmp/los001-parallel-$run.log" 2>&1 & done; wait
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
git diff --check -- app/InstallationProcess/MariaDbLegacyInstallationObject.php specs/LEGACY-OBJECT-SNAPSHOT-001.md tests/InstallationProcess/legacy_object_snapshot_001_test.php reviews/tests/LEGACY-OBJECT-SNAPSHOT-001.md
```

Results: focused test passed; three simultaneous focused runs all passed; all 25 InstallationProcess tests passed sequentially; all scoped PHP syntax checks and scoped `git diff --check` passed. Temporary parallel logs were removed after inspection.

## Findings

None.

## Required changes

None. Gate 5 is `APPROVED`; `LEGACY-OBJECT-SNAPSHOT-001` v0.2 is complete.
