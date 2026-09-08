# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log — Gate 2 fixture correction v59

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Exact pre-GREEN baseline: `bbb6298cce223ec451d8030f62965fa6bc9d4ed5`
- Current production candidate: `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`
- Maintenance test SHA-256: `954e5efc1e97ea61eff4a70f972a335b6c4be30bfa19521521d4702a0cf959a5`
- Lease-race test SHA-256: `cdec5a0acfc63f74f82b2f4ebea7ec8990ed184be1ae51592f5d09d15bf6cf43`

## Correction scope

Only the stale production-config constructions in
`assignment_order_original_maintenance_001_test.php` and
`assignment_order_original_lease_race_001_test.php` changed. Each test already
created its own random task-root safe-log file and cleaned it in `finally`; the
correction passes that exact file as mandatory `safeLogFile`. Before use, each
test now proves that the configured path exists, is canonical, regular,
non-symlink, owned by the effective user, and exact mode `0600`. Reflection
pins the three exact constructor fields, so the pre-GREEN two-field contract
cannot silently accept an ignored extra PHP argument. All prior maintenance,
lease, persistence, replay, evidence, and cleanup assertions remain intact.

## Exact-baseline RED

A detached temporary worktree was created at exact SHA
`bbb6298cce223ec451d8030f62965fa6bc9d4ed5`. The uncommitted diff of only these
two tests was piped directly to `git apply` in that worktree. Both tests exited
`255` for their intended contract reason:

```text
INTENDED_RED: maintenance fixture requires the mandatory third production safeLogFile contract.
Expected: ['privateStorageRoot', 'tablePrefix', 'safeLogFile']
Actual:   ['privateStorageRoot', 'tablePrefix']

INTENDED_RED: lease-race fixture requires the mandatory third production safeLogFile contract.
Expected: ['privateStorageRoot', 'tablePrefix', 'safeLogFile']
Actual:   ['privateStorageRoot', 'tablePrefix']
```

The fixture ownership/mode assertions ran first, so neither failure was setup.
The detached worktree and its temporary output files were removed afterward;
`git worktree list` contains only the primary worktree and no
`aoou-safe-log-red-*` directory remains.

## Current candidate GREEN

At exact production candidate `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
$ php tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
$ php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
$ git diff --check
(no output; exit 0)
```

This current GREEN is candidate evidence only. This commit is a Gate 2 test
fixture correction and does not review or approve production; a fresh
independent Gate 3 review remains required.
