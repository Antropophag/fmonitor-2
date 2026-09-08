# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — worker post-finalize negative RED

- Date: `2026-09-05`
- RED author: `Codex agent /root/command_gate5_red_domain`
- Supersedes the finding-6 coverage claim in the earlier evidence record; that
  append-only record remains unchanged.
- Gate 3 finding: `4d88ec79d3dde4d1976455a453493e3ea9762e1c`
- Gate 5 finding source: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Production behavior under test: `6c4fb5b70065cabb19adab23f6004714c1f0699a`

## Executable identity

```text
49ca817e40793aee0530ce8aff7040fb23eb2a39dfbbdfeaa66628f80873e5b0  tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
```

The test uses the real verification worker/bootstrap, five-FD protocol, clean
MariaDB migrations and approved Example A seed. Its command has valid framing,
PDF prefilter inputs and `after_private_finalize_before_commit`, but actor `17`
does not hold the exact upload capability. Therefore the real application must
return its authorization rejection before stream, stage, finalize or lifecycle
callback, and the READY channel must remain empty.

No test callback writes storage state or acquires a lock. If the broken worker
emits READY, the parent writes the matching RELEASE solely to guarantee bounded
process cleanup; that release cannot create a lifecycle event or turn a
non-empty barrier into the required empty channel. The test also requires the
private root to contain no fabricated metadata, content or lock.

## Demonstrated intended RED

Command:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
```

Exact first mismatch:

```text
Unauthorized command emits no post-finalize READY because no actual finalize lifecycle event occurred.
Expected: ''
Actual:   'READY 00000000-0000-4000-8000-000000000560\n'
```

The command exits nonzero after successful database/schema/fixture and worker
construction. PHP lint and `git diff --check` pass. Cleanup targets are random,
regex-bounded names; the exact database is dropped and the exact temporary tree
is removed in `finally`, including on this intended failure.

This is Gate 2 evidence only. A fresh independent Gate 3 review of the exact
test bytes is required before correcting production worker behavior. The RED
author is ineligible to provide that review or later Gate 5 approval.
