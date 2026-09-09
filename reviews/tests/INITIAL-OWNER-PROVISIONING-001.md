# Independent Gate 3 review — INITIAL-OWNER-PROVISIONING-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Specification/test author: separately tasked agent `/root/runtime_plan`
- Verdict: **APPROVED (revised full matrix)**

## Exact reviewed artifacts

```text
52f78825d52a19fa30e9ef528be453632f83f5b577d4f19733459929f5bf736a  specs/INITIAL-OWNER-PROVISIONING-001.md
7c22dc7d30f441865dcf26065e68e3141a0226053a511f26bed2875560eaa866  openspec/changes/provision-initial-owner-admin/specs/operations/initial-owner-provisioning/spec.md
e084133a84c7a02521a1aac41fd5d4ca2835ba4f7d1634977c0ecd7a0a866ae2  tests/Runtime/initial_owner_provisioning_001_test.php
```

## Review

The executable uses the explicit CLI over a clean canonical-v22 database under a
DML-only principal. It requires mixed-case/trimmed corporate email normalization,
exactly one active user whose full name is the normalized email, an Argon2id hash
that verifies the injected password, exact bootstrap `user` and
`superadministrator` memberships with null actor, and the independently frozen
complete role/permission catalogue hash.

Exact replay and different-password conflict compare the full identity-family
snapshot. A separate invited-user fixture proves existing state is not promoted or
repaired. Missing/bad CLI arguments run with otherwise valid configuration, and
missing DB password is tested separately; all invalid outcomes must be stable,
secret-free and leave the full owner identity state unchanged.

Random databases/users and unconditional teardown isolate the test. Successful use
with SELECT/INSERT/UPDATE/DELETE privileges proves the reviewed path does not require
DDL.

## Demonstrated RED

```text
$ php tests/Runtime/initial_owner_provisioning_001_test.php

INTENTIONAL_RED: explicit initial-owner CLI exists
Expected: true
Actual: false
exit 255
```

The RED is the absent public seam, before database fixture setup. Syntax and diff
checks pass.

## Revised matrix and verdict

The revised reviewed test additionally covers partial role state with zero users,
missing schema without repair, an independently held provisioning lock, injected
late-grant failure with complete rollback and successful retry, and two concurrent
real CLI processes with bounded reaping. Its full nine-table identity snapshots
include invitations and all role/auth/status event families. The specification now
defines the exact replay user fields and empty auxiliary history.

**APPROVED.** Gate 4 may implement the full reviewed clean/replay/conflict/schema/
lock/rollback/concurrency matrix through the DML-only IdentityAccess owner and CLI.
