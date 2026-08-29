# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_assets_v2_test_final`
- Test author: separately tasked Gate 2 agents; final corrective commit `f4dbe62`
- Reviewed commit: `f4dbe62deb2b58b264b7a911160a7acd32557098`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED, exit `255`; overlapped identity/mode drift returned `200` with a 6 MiB body instead of required redacted `503`; cleanup then also reported nine newly created current-EUID SysV segments.
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. Filesystem residue snapshot remains incomplete and is insensitive to empty cache roots

Section 3 prohibits any filesystem manifest/cache/lock/sentinel and section 7 prohibits cache/build/temp residue. `psaCacheState()` observes only four hard-coded repository paths that are neither named by the specification nor derived from runtime configuration. A plausible implementation writing `/tmp/fmonitor-shlz.cache`, `/dev/shm/fmonitor-shlz`, `<repo>/var/tmp/shlz.lock`, or any differently named repository cache passes this oracle. Even at the four listed paths, a newly created empty root is represented as `[]`, exactly like an absent root, because root existence/type/metadata are not included. Empty directories and changed directory metadata are therefore invisible.

Replace this with before/after fingerprints of the bounded runtime and repository locations in which this application could place residue, recording root existence/type plus descendant path, type, identity/metadata, and content hash. Explicitly exclude the test-owned fixture and concurrency-safe shared test namespaces. If production configuration constrains writable cache/temp locations, derive the exact roots from that configuration rather than inventing names in the test.

### 2. SysV snapshot does not detect reuse or mutation of a pre-existing segment

`array_diff($sysvAfter, $sysvBefore)` detects only a new key/id. A cross-request implementation can attach to one stable segment that already exists before the test and mutate/use it on every request; before and after membership stays identical and the test passes, although sections 3 and 7 forbid the dependency itself. This is especially relevant after any earlier request has initialized a fixed-key cache.

Run the asset fixture in an isolated/known-clean IPC scope if the harness already provides one, or add black-box evidence that a pre-existing task-owned segment cannot be consumed/changed and that no segment is required. Do not fall back to the former broad lexical production-source ban.

## Non-blocking checks passed

- The corrected overlap fixture sends the request over the real socket, counts at least 20 mutations between transmission and the first response byte, verifies the worker is live at that boundary, and observes further mutations through response completion. Because the specification requires capture to finish before any response header byte, this is adequate first-byte overlap evidence without a production hook.
- The fixture restores the two mutated members, removes its counter/stop/temporary files, stops its worker/server in `finally`, and the review run left no `psa-*` fixture or worker/server process. The nine exact SysV ids created by the current production implementation during the review run were removed explicitly afterward.
- The focused test is honestly RED for missing v0.2 behavior: it reaches the overlap assertion and observes exact `200` with 6,291,456 body bytes instead of `503`. The additional SysV cleanup failure is also product residue, not setup failure.
- All prior v0.1 graph/routing/parser/security/limit/HTML-order coverage remains present; v0.2 additionally covers legitimate between-request replacement, owner/mode rejection, overlap, and residue membership.

## Required changes

1. Make the filesystem residue fingerprint sensitive to root creation and to plausible bounded runtime/repository residue locations independent of chosen cache filename.
2. Make the SysV oracle sensitive to reuse/mutation/dependency on a pre-existing task-owned segment, not only newly allocated ids.
3. Re-run and capture the focused RED after correction, then obtain a fresh independent Gate 3 review.

Gate 4 remains closed.

## Verification evidence

- `git diff f4dbe62^ f4dbe62 -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — reviewed corrective delta.
- `git diff --check 331b8ac^..f4dbe62 -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — intended RED, exit `255`: overlap expected `503`, actual `200`, body bytes `6291456`; final residue assertion listed nine new current-EUID SysV ids.
- Post-run inspection — no task fixture paths or worker/server processes remained; exact reported SysV ids `65617`–`65625` were removed and verified absent.
