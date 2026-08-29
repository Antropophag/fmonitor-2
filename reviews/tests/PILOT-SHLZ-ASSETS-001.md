# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_assets_v2_test_review`
- Test author: separately tasked Gate 2 agent; reviewed commit `0d59fbd`
- Reviewed commit: `0d59fbd`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED, exit `255`; after a completed official atomic replacement, `GET /pilot/assets/shlz.css` expected the new exact graph bytes but received exact redacted `503 Service unavailable.\n`.
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. Cross-request state/residue coverage is not sensitive to the full v0.2 prohibition

Specification section 3 forbids SysV/shared memory, APCu/opcache user cache, filesystem cache/lock/sentinel, daemon/guardian, subprocess, and any process-global cross-request coordination. The new source check reads only `app/PilotHttp/PilotHttp.php` and searches a short list that omits `apcu_*`, opcache user-cache calls, `static`/class-static manifest state, globals, and helper classes/files. The three repository-root filename probes likewise do not detect differently named or differently located cache/lock/temp/sentinel residue. An implementation can therefore retain a process-global manifest cache (even one invalidated by entrypoint mtime, so the old/new behavior passes) or move forbidden coordination into another production file while this test remains green.

Add a deterministic check that is sensitive to every explicitly prohibited coordination class across the production boundary, and fingerprint the task-owned runtime/repository locations in which the application could leave filesystem residue. Keep legitimate test-side `proc_open` excluded explicitly rather than limiting the scan to one implementation file.

### 2. In-flight replacement has no deterministic overlap witness

`psaInRequestMutation()` starts a client and immediately flips two members until the client exits, but it never observes that the server began the graph capture before mutation nor that at least one mutation occurred while that capture was in flight. Fixture size and process scheduling make overlap likely, not deterministic. A conforming implementation can occasionally finish capture before the parent receives CPU; the assertion then expects `503` although section 3 permits a complete old or new response when replacement is outside the capture interval. Conversely, a non-conforming implementation can return `503` for a transient missing path without proving sensitivity to an identity/mode change during an established capture.

Add an observable, public-seam-compatible synchronization/witness that proves mutation overlaps the request-owned capture, and assert the witness before asserting `503`. Do not use a production test hook or prohibited cross-request coordination.

## Coverage retained from v0.1

The unchanged reviewed coverage still traces import grammar, exact routes and bytes, GET/HEAD parity, security/error priority, deduplication/cycles, same-identity aliases, exact member/depth/size limits, invalid UTF-8, collision, symlink and filesystem boundaries. The v0.2 sequential replacement fixture correctly distinguishes the former process-lifetime immutable behavior, and owner/mode fixtures cover the primary trusted-root/member rejection cases. These strengths do not close the two sensitivity/determinism gaps above.

## Required changes

1. Expand the statelessness/residue oracle to cover all coordination mechanisms explicitly forbidden by v0.2 and production files beyond `PilotHttp.php`.
2. Make the in-flight mutation case deterministic by proving capture/mutation overlap at the public seam before requiring `503`.
3. Re-run and capture the focused RED after the corrections; the failure must still be for missing v0.2 behavior rather than setup or timing.

Gate 4 remains closed. Any corrected test requires a fresh independent Gate 3 review.

## Verification evidence

- `git diff 331b8ac^ 331b8ac -- specs/PILOT-SHLZ-ASSETS-001.md` — reviewed v0.2 normative delta.
- `git diff 0d59fbd^ 0d59fbd -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — reviewed Gate 2 delta.
- `git diff --check 331b8ac^..0d59fbd -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — intended RED, exit `255`, at completed atomic replacement: expected exact new root bytes, received exact redacted `503`.
