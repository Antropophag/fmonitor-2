# PRODUCTION-RUNTIME-RESTORE-001 — Gate 1 and Gate 3 review

- Reviewer: `/root/runtime_review`
- Test author: `/root/runtime_tests`
- Verdict: **APPROVED**

Reviewed artifacts:

```text
f338ed6c385f0c53f3bec7df7e1df961f705dd66cf909f10f7a3235f4c7f766e  specs/PRODUCTION-RUNTIME-RESTORE-001.md
c06944e7c1338065591ae9256dda5f3e794854ad0280e4795a2fa5b54f27421d  tests/Runtime/runtime_recovery_001_test.php
e460ae1752abac02e81afbe15a6ea33337c93615e9eb22a70b4eaa9a3be9cfdf  tests/Runtime/runtime_recovery_tar_fixture.py
4620d7c0a4599048336080c0fdb5272f3b5c4028282c07833b0326322d000b59  openspec/changes/restore-production-runtime-contour/design.md
```

The contract defines one explicit operator backup/restore CLI, stable redacted
outcomes, an attestation-only writer-quiescence boundary, a versioned exact bundle,
fresh private partial publication, complete restore preflight before target
mutation, and terminal runtime readiness. It explicitly defers #34 jobs/outbox and
policy values instead of claiming recovery for them.

The public test builds a populated canonical v22 source with identity, assignment,
opening, checklist attribution/photo, completion, original revision/history/audit,
session, PDF and safe-log evidence. It compares exact ordered rows and a state tree
containing bytes, modes and UID:GID. Bundle fields, inventories, ownership and modes
are independently asserted. Corrupt bytes, allowlist changes, missing/extra members,
top-level links, and hash-consistent internal tar traversal, symlink, hardlink,
duplicate and unsafe-mode cases all require stable rejection before DB or state
mutation. Occupied destinations and stale partial siblings are preserved.

Focused RED:

```text
php tests/Runtime/runtime_recovery_001_test.php
```

Observed exit `255` at the first assertion because
`bin/fmonitor2-runtime-recovery.php` is absent. The failure precedes database setup
and is caused by the missing public seam. PHP/Python syntax, strict OpenSpec
validation and `git diff --check` are reported clean.

Gate 4 may implement this bounded base recovery seam. Actual Compose restore,
existing-cookie/browser mutation, update/rollback drills, private operational
evidence and post-#34 worker/outbox recovery remain later gates.

## Renewed data-only standard-client Gate 3

After a same-version MariaDB schema roundtrip proved physically unstable, the
reviewed contract retained the standard client but changed the bundle to data-only.
The exact source-image canonical catalogue recreates schema; the manifest fixes the
v22/63-table frontier and every AUTO_INCREMENT table/value. The revised public test
proves exact rows and a deleted-high-id counter, rejects wrong schema/table/AI
metadata before DDL, and exercises causal no-clobber publication.

Current reviewed hashes are recorded in
`reviews/code/PRODUCTION-RUNTIME-RESTORE-001.md`. The renewed test is GREEN and
Gate 3 remains **APPROVED for exact v22 only**. A v23/post-#34 package requires new
review.

## Historical v22 executable binding Gate 5 — 2026-09-09

Verdict: **APPROVED** for preserving this exact v22 oracle after canonical v23.

The launcher archives exact commit
`f22d80a609d52a194c1fd68b1db7ab7273740f28`, builds its production runtime
Dockerfile with the same OCI revision label, and refuses to run unless image
inspection returns that literal revision. It mounts the archived commit's own
`tests/` directory read-only, so both the application and executable oracle come
from f22 rather than the current frontier. The test body after the launcher is
also byte-identical to f22: SHA-256
`190f199b615808c9ba4dd8dc67b404ed74a63e6ac6177a094d562ae761d1145b`.

The outer launcher captures the child status inside a closure. Its `finally`
therefore removes the task-owned image tag and archive directory before the outer
`exit`; this corrects the historical launcher's `exit`-inside-`try` cleanup leak.
Only the configured test DB host, port, admin user and password cross into the
container, and the DB host is explicitly mapped to `host.docker.internal`.

Focused evidence:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/Runtime/runtime_recovery_001_test.php
PASS: PRODUCTION-RUNTIME-RESTORE-001 backup/restore/corruption contract
exit 0
```

Evidence log: `/tmp/fmonitor-v22-historical-binding-exact-green.log`. After the
run, no `v22-oracle-*` image tag or `fmonitor-v22-oracle-*` control directory
remained. The current-frontier binding independently failed at expected schema22
versus23, demonstrating why the historical source binding is required. Current
v23 recovery remains a separate `PRODUCTION-JOBS-RECOVERY-001` gate.

```text
3ea32b704f29ecb06439f270fbfbf4acff32a23d260b9d2d3541262cfdc9b0fd  tests/Runtime/runtime_recovery_001_test.php
```
