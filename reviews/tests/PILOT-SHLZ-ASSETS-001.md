# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_assets_v2_test_approve`
- Test author: separately tasked Gate 2 agents; reviewed corrective commit `c2e378d`
- Reviewed commit: `c2e378d7a65f11576a9d78cfdd66d38481adbb26`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED, exit `255`; overlapped identity/mode drift returned `200` with a 6 MiB body instead of required redacted `503`; cleanup also observed ten new current-EUID SysV shared-memory segments.
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. Filesystem residue oracle still misses plausible forbidden residue

Sections 3 and 7 prohibit any filesystem manifest/cache/lock/temp/sentinel residue. `psaRuntimeState()` inventories broad runtime roots, but `psaRuntimeResidueProbe()` reports a changed path only when it is below the three test-owned environment directories, contains the configured fixture-root string in a readable file of at most 1 MiB, or happens to be open at one of two `/proc/<pid>/fd` samples. A plausible implementation that writes and closes `/tmp/fmonitor-shlz.cache`, `/dev/shm/shlz.lock`, or a repository cache with opaque/binary content does not meet any of those attribution predicates and passes. Files over 1 MiB and deleted-but-still-open files are similarly invisible. The retained four exact cache paths do not close this gap for arbitrary allowed names.

Make the runtime namespace attributable before executing the request rather than filtering unexplained changes afterward. For example, use task-owned effective HOME/TMP/cache/repository runtime roots plus an observation mechanism that captures every path created/opened by the asset-server process during the probe. The oracle must detect a closed opaque residue file without treating unrelated concurrent changes elsewhere in `/tmp`, `/dev/shm`, or the repository as product behavior.

### 2. SysV oracle is comprehensive but not attributable or deterministic

The corrected `psaSysv()` value includes full metadata and therefore detects new, removed, and metadata-mutated current-EUID IPC objects, including reuse of a pre-existing object. However, the snapshots bracket the complete multi-second test and compare every SysV object belonging to the UID. Any unrelated concurrent process that creates, removes, attaches to, detaches from, or otherwise changes such an object causes this test to fail. Nothing in the resulting key/id diff ties the object to the asset-server child. This violates Gate 2 determinism and creates a material false-positive path in the repository's parallel-agent/test environment.

Use a task-owned IPC object/dependency probe or attribute changed objects to the spawned asset server (including its relevant creator/last-operation PIDs) within a bounded request window. Preserve the useful sensitivity to an implementation that consumes or mutates a pre-existing segment, semaphore, or queue, while excluding IPC activity with no causal relation to this fixture.

## Non-blocking checks passed

- The overlap fixture proves at least 20 mutations after request transmission and before the first response byte, keeps the worker alive through response completion, and therefore supplies adequate real-socket evidence for the pre-header capture boundary.
- The focused run is honestly RED for missing v0.2 behavior: the public seam returned exact `200` with 6,291,456 body bytes where the specification requires redacted `503`. The ten new SysV segments reported during cleanup were also created by the current production behavior, not by fixture setup.
- Full-metadata SysV comparison fixes the earlier membership-only blind spot, and cache-root presence now distinguishes absent from empty.
- Prior graph, grammar, routing, security, bounds, owner/mode, between-request replacement, GET/HEAD, and HTML-order assertions remain traceable to v0.2.
- The review run left no `psa-*` fixture, worker, or server process. The ten exact shared-memory ids reported by this run were removed and verified absent.

## Required changes

1. Make filesystem residue detection sensitive to opaque, closed, arbitrarily named residue while causally attributing observations to the asset process.
2. Scope or attribute SysV observations so unrelated current-UID IPC activity cannot fail the test, without losing sensitivity to pre-existing-object use or mutation.
3. Re-run and capture the focused RED, then obtain a fresh independent Gate 3 review.

Gate 4 remains closed.

## Verification evidence

- `git diff f4dbe62..c2e378d -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — reviewed corrective delta.
- `git diff --check 331b8ac^..c2e378d -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — intended RED, exit `255`: overlap expected `503`, actual `200`, body bytes `6291456`; cleanup reported ten newly created current-EUID SysV shared-memory ids.
- Post-run inspection — no task fixture paths or worker/server processes remained; reported ids `65646`–`65655` were removed and verified absent.
