# Code review: PROCESS-COMMAND-AUTHORIZATION-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved test, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production scope: current v4 `ProcessCommandCapabilitiesSchemaMigration.php`, related v3 migration, and `MariaDbProcessUserDirectory.php`
- Specification: [`specs/PROCESS-COMMAND-AUTHORIZATION-001.md`](../../specs/PROCESS-COMMAND-AUTHORIZATION-001.md), version `0.2`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PROCESS-COMMAND-AUTHORIZATION-001.md`](../tests/PROCESS-COMMAND-AUTHORIZATION-001.md), including all historical and adversarial restarts
- Previous Gate 5 verdict: `CHANGES_REQUESTED` for engineer Boolean grouping
- Superseding verdict: `APPROVED`

## Standards

`APPROVED`. Engineer-position recognition now preserves Boolean grammar. It removes only balanced parentheses enclosing the complete expression, then accepts exactly `capability <> engineer OR position IS NOT NULL AND TRIM(position) <> ''`, optionally grouping only the AND branch. The non-equivalent `(A OR B) AND C` adversary, altered grouping, extra operators/tokens, identifiers or literals all fail closed.

Capability parsing preserves literal bytes, accepts only harmless SQL token formatting and IN-value ordering, rejects malformed/escaped forms, duplicates and unknown/extra values, and compares exact v3/v4 sets. Every CHECK must classify as the unique capability or engineer candidate; unknown extras and ambiguity conflict before DDL. Exact v4 requires the normative capability name.

For v3, the catalog-derived DROP target must match `[A-Za-z0-9_$]{1,64}` and is backtick-quoted. The only ALTER drops that exact capability constraint and adds the normative v4 constraint. Engineer constraint/name, rows, indexes, collations and FKs remain untouched. Prefix validation, alternate utf8mb4 handling, historical names, safe repeat/conflict and query security remain sound. Authorization is exact per capability, parameterized, active-user/role constrained, read-only, engineer-separated, and has no `users_rights2roles` coupling. No blocking maintainability smell remains.

## Spec

`APPROVED`. The full v0.2 contract is met: generated/non-normative v3 names migrate by unique exact semantics; quoted-literal, grouping, wrong-name-v4, duplicate/unknown/extra and structural adversaries conflict before DDL; `$` and the 64-byte safe-name boundary work; completed normative v4 repeats without mutation; and alternate utf8mb4 collations are preserved.

The actual historical capability constraint alone is replaced under the normative name while the engineer CHECK retains its original name and clause. Configuration rows and all non-capability schema facts are preserved. The authorization matrix, external-table immutability, real prepare/confirm/open chain, fresh reload and absence of `users_rights2roles` remain exact.

## Verification evidence

```text
php tests/InstallationProcess/migration_process_001_test.php
php tests/InstallationProcess/process_user_directory_001_test.php
php tests/InstallationProcess/process_command_authorization_001_test.php
# all related tests PASS

for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
# syntax PASS

# all 34 InstallationProcess tests concurrently
# 34/34 PASS

for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
# 34/34 PASS sequentially

git diff --check -- app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php app/InstallationProcess/ProcessUserCapabilitiesSchemaMigration.php app/InstallationProcess/MariaDbProcessUserDirectory.php tests/InstallationProcess/process_command_authorization_001_test.php specs/PROCESS-COMMAND-AUTHORIZATION-001.md reviews/tests/PROCESS-COMMAND-AUTHORIZATION-001.md reviews/code/PROCESS-COMMAND-AUTHORIZATION-001.md
# PASS
```

The working tree remains an intentionally uncommitted handoff; unrelated changes were excluded. Temporary parallel logs were removed.

## Findings

None. All prior collation, historical-name, literal/identifier/completed-name, and Boolean-grouping findings are resolved.

## Required changes

None. Gate 5 is `APPROVED`; `PROCESS-COMMAND-AUTHORIZATION-001` v0.2 is complete.
