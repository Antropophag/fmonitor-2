# Code review: PROCESS-USER-DIRECTORY-COMPAT-001

- Reviewer: `Codex agent /root/process_directory_compat_review` (independent Gate 5 reviewer; did not author the implementation)
- Reviewed commit: working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Reviewed production diff: `app/InstallationProcess/MariaDbProcessUserDirectory.php` against HEAD
- Normative specifications: [`PROCESS-USER-DIRECTORY-001`](../../specs/PROCESS-USER-DIRECTORY-001.md) v0.1 and [`PROCESS-COMMAND-AUTHORIZATION-001`](../../specs/PROCESS-COMMAND-AUTHORIZATION-001.md) v0.2, both `APPROVED 2026-08-28`
- Approved tests: [`reviews/tests/PROCESS-USER-DIRECTORY-001.md`](../tests/PROCESS-USER-DIRECTORY-001.md) and [`reviews/tests/PROCESS-COMMAND-AUTHORIZATION-001.md`](../tests/PROCESS-COMMAND-AUTHORIZATION-001.md)
- Verdict: `APPROVED`

## Standards

`APPROVED`. The compatibility decision is made from the existence of the exact prefixed local user table, using a parameterized `information_schema` lookup after validating both identifier prefixes. Once that table exists, every command and artifact permission uses only active local users, active local roles, and the exact requested local permission. Engineer identity likewise requires the exact active local engineer role. A missing local user, role, grant, or engineer assignment denies locally; none of those denials can fall through to legacy authorization.

Only when the local user table is absent does the directory use the approved rollout source. Prepare, registration confirmation, and opening each require the exact process capability plus an active legacy user and active joined legacy role. Engineer selection independently requires `construction_control_engineer`, a nonblank configured position, and the same active identity conjunction. Queries are parameterized, `users_rights2roles` remains unused, and the adapter performs no writes. The artifact-read compatibility behavior remains the inherited active-user/active-role rule and does not weaken any reviewed command seam.

The change stays within the MariaDB directory boundary and introduces no HTTP/UI ownership, DDL, mutable process facts, or reverse application dependency. Formatting is substantially clearer than the previous compressed implementation. No blocking security, integration, or maintainability issue was found.

## Spec

`APPROVED`. With no local user table, both approved executable suites exercise the legacy identity/role plus process-capability path and pass their inactive-user, inactive-role, missing/exact-capability, engineer-separation, public-command-chain, fresh-reload, prefix, and external-immutability cases. This preserves the approved PROCESS-USER-DIRECTORY-001 and PROCESS-COMMAND-AUTHORIZATION-001 rollout contract.

The code-level local-path review confirms the complementary fail-closed property required by the current local-RBAC composition: table presence selects local ownership once, exact local grants are required, and legacy rows cannot rescue a local denial. Thus the dual directory is a deployment compatibility boundary rather than a union of authorities.

## Verification evidence

Executed against the disposable Makefile MariaDB after `make test-db-reset`:

```text
FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
php tests/InstallationProcess/process_user_directory_001_test.php
PASS: PROCESS-USER-DIRECTORY-001 production user directory

FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

git diff --check -- app/InstallationProcess/MariaDbProcessUserDirectory.php
# PASS

php -l app/InstallationProcess/MariaDbProcessUserDirectory.php
No syntax errors detected in app/InstallationProcess/MariaDbProcessUserDirectory.php
```

Reviewed artifact hashes:

```text
40629b6f083dfad29cb414a935eab7128eee10627dfcc3da2f3baad27b139cc0  specs/PROCESS-USER-DIRECTORY-001.md
496bef81d706fc49c10012fe6092b34418048e225c96c1ecb05387e9e0d30a48  specs/PROCESS-COMMAND-AUTHORIZATION-001.md
1230764b4d171fd9ba4a4d7b959a5b36dbe3e081373d4eba4e0e65c8cb978b0f  tests/InstallationProcess/process_user_directory_001_test.php
2c72061dcc0fc8d401f0287628f20ddb9ed30a8ac0c7d33c0e3d69461ab50a8d  tests/InstallationProcess/process_command_authorization_001_test.php
16ac1f793a29a9b90585e2ffdfa5db5fd3c009734bd60e9ffdc88aec4ac048a2  app/InstallationProcess/MariaDbProcessUserDirectory.php
```

## Findings

None.

## Required changes

None. Gate 5 for the dual-directory compatibility fix is `APPROVED`.
