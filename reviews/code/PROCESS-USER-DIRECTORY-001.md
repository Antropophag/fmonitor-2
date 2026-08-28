# Code review: PROCESS-USER-DIRECTORY-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved tests, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production scope: `app/InstallationProcess/ProcessUserCapabilitiesSchemaMigration.php` and `MariaDbProcessUserDirectory.php`; shared inspector/current migrations inspected for regression context; unrelated dirty handoff files excluded
- Specification: [`specs/PROCESS-USER-DIRECTORY-001.md`](../../specs/PROCESS-USER-DIRECTORY-001.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PROCESS-USER-DIRECTORY-001.md`](../tests/PROCESS-USER-DIRECTORY-001.md), including spaced-position/blank-corruption restart verdict `APPROVED`
- Current verdict: `APPROVED`

## Superseding Gate 5 review

### Standards

`APPROVED`. The directory now uses `trim($position) === ''` only as a fail-closed eligibility predicate while returning the original nonblank configured value unchanged (`MariaDbProcessUserDirectory.php:60-68`). Null, non-string, empty, and whitespace-only corrupt facts are rejected, while authoritative valid configuration is preserved exactly before immutable persistence.

Both entry points still reject nonpositive IDs, interpolate only validated quoted prefixes, bind IDs, and require a single conjunction of active user, active linked role, and exact capability. SQL failures propagate; neither role names nor `users_rights2roles` participate. The v3 migration remains additive, uses the shared inspector, requires exact primary/index/CHECK shape, and creates no legacy FK or destructive path. No documented-standard breach, security/integration issue, or blocking Fowler smell remains.

### Spec

`APPROVED`. The approved public-command scenarios independently prove both sides of the position contract:

- corrupt `NULL` and whitespace-only positions are seeded only while MariaDB CHECK enforcement is temporarily disabled, enforcement is restored before execution, and each command must return exact `CONTROL_ENGINEER_NOT_ELIGIBLE` with inherited audit, no renderer/order, and complete database-state preservation (`process_user_directory_001_test.php:156-168`);
- the valid configured position contains independently chosen leading/trailing spaces, and the success/reconnect projection requires that exact value after current facts are changed/deleted (`process_user_directory_001_test.php:37-57,159-192`).

Capability separation, active user/role conjunctions, role-name and `users_rights2roles` independence, parameterized SQL, read-only catalogs, additive v3 schema/check canonicalization, safe repeat/conflict, authorization/audit ordering, and persisted snapshot immutability all remain conformant. No missing, extra, or incorrect behavior remains.

## Verification evidence

Commands run independently:

```text
php tests/InstallationProcess/process_user_directory_001_test.php
# all 27 InstallationProcess tests started concurrently in isolated processes; every log reported PASS
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
git diff --check -- app/InstallationProcess/ProcessUserCapabilitiesSchemaMigration.php app/InstallationProcess/MariaDbProcessUserDirectory.php tests/InstallationProcess/process_user_directory_001_test.php specs/PROCESS-USER-DIRECTORY-001.md reviews/tests/PROCESS-USER-DIRECTORY-001.md
```

Results: focused test passed; all 27 tests passed concurrently and sequentially; every scoped PHP syntax check and scoped `git diff --check` passed. Short-lived parallel logs were removed after inspection.

## Findings

None.

## Required changes

None. Gate 5 is `APPROVED`; `PROCESS-USER-DIRECTORY-001` is complete.

## Superseded review history

Earlier Gate 5 rounds found reliance on the schema CHECK for corrupt position data, incomplete NULL/blank sensitivity, and accidental trimming of valid configured position. Each test gap restarted Gate 2 and received fresh independent Gate 3 approval before Gate 4. The current implementation/tests resolve every finding; this verdict supersedes all prior `CHANGES_REQUESTED` reviews.
