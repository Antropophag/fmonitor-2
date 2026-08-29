# Code review: PILOT-SHLZ-ASSETS-001 v0.2

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked Codex agent `/root/shlz_assets_v2_code_review`
- Verdict: `CHANGES_REQUESTED`
- Specification: `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Approved test review: `3f1d67165e3e178e6c39eb3721f2cb8aee2e8342`
- Reviewed implementation HEAD: `545fdfa935e15d8dabc68922637537cebe5d9bf7`
- Review date: 2026-08-29

## Verdict

`CHANGES_REQUESTED`. The focused test passes three consecutive runs, the bootstrap contract and all 49 InstallationProcess tests pass, PHP lint/diff checks pass, no SysV residue remains, and exact-head Chromium evidence supplies the required CSSOM provenance and responsive/focus records. Gate 5 remains closed because the implementation does not perform the specified one-open, whole-graph atomic capture and rejects/accepts owner modes differently from the approved contract. The reviewed test does not expose these plausible regressions, so correction returns through Gate 2 and fresh independent Gate 3 approval.

## Standards

### Blocking — response is not produced from one atomic captured graph

`app/PilotHttp/PilotHttp.php:93-97` opens and closes each member during `walk()`, then opens and closes every member again in the constructor's validation loop. `asset()` subsequently creates another `ManifestCssAsset`, and the selected route is opened a third time when its body is read. Captured member bytes are never retained in the manifest.

This contradicts specification section 3's `open once`, keep-descriptors-open, rewind/re-read, close-once and “response from captured bytes” algorithm. It also leaves a graph-consistency window after the constructor-wide validation: another member or directory can change before the selected route is read, while only the selected file's identity/hash is checked again. The response therefore is not proven to belong to the already validated whole graph. Store request-local captured bytes and lifecycle-owned descriptors, perform the attempt-all final graph revalidation on those same descriptors, close them exactly once, and select the route only from the captured mapping.

### Medium — transport timing hack changes unrelated failures

`public/router.php:18` flushes headers and sleeps for every CLI-server `503`, including non-asset failures. This unexplained magic delay is outside the asset boundary and conflicts with Gate 4 minimality; it is also a judgment-call Divergent Change/Shotgun Surgery smell. Remove it and make the capture itself establish the pre-header result deterministically.

### Medium — security boundary is compressed beyond auditability

`app/PilotHttp/PilotHttp.php:83,93,97-103` compresses trust establishment, ownership/mode checks, traversal, parsing, hashing and revalidation into single-line methods. This is a judgment-call Divergent Change/Mysterious Name smell. Split and format the security-sensitive capture phases so their ordering and cleanup can be audited.

## Spec

### Blocking — trusted root owner mode is narrower than approved

Specification section 2 permits one trusted owner whose UID is either application eUID or `0`. `app/PilotHttp/PilotHttp.php:93` instead requires root UID to equal eUID and assigns only eUID as trusted. A valid root-owned export is therefore incorrectly returned as `503` when the application is non-root.

### Blocking — owner directory permissions are not enforced

Specification section 2 requires every directory to have owner read/search permission. `captureDirectory()` at `app/PilotHttp/PilotHttp.php:98` rejects group/other write but never requires owner `r+x` bits. `validateComponents()` checks effective `is_readable`/`is_executable`, which may succeed via group/other permissions; such a directory violates the exact owner-mode contract but can be accepted. Require owner mode `0500 == 0500` for every captured directory, including the dist root.

### Verified conformance and evidence

- Parser, route/method priority, manifest-only selection, public/no-identity behavior, GET/HEAD parity, graph limits, symlink rejection and no external cache/shared-memory implementation are exercised and pass at the public seam.
- Exact-head evidence at `/home/antropophag/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-545fdfa/` records Chromium journey screenshots, 1440/768/320 responsive layouts without overflow, visible keyboard focus, empty per-page errors, successful CSS responses, and CSSOM ownership for a `--shlz-*` custom property, `.shlz-button`, and `.fm2-shell`.
- No committed/generated manifest, cache, lock, temp, sentinel, subprocess watcher, SysV segment or semaphore dependency was found.

## Verification evidence

- `git rev-parse HEAD 545fdfa 331b8ac 3f1d671` — exact refs resolve; HEAD is `545fdfa935e15d8dabc68922637537cebe5d9bf7`.
- `git diff --check 3f1d671..545fdfa` — PASS.
- `php -l app/PilotHttp/PilotHttp.php`; `php -l bin/fmonitor2-pilot-demo.php`; `php -l public/router.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS in three consecutive runs.
- `php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php` — PASS.
- All 49 `tests/InstallationProcess/*_test.php`, sequentially — PASS.
- `ipcs -m`; `ipcs -s` — no segments or semaphore arrays.
- Residue scan for `psa-*`, lock and sentinel artifacts — empty.

Gate 5 remains closed for `PILOT-SHLZ-ASSETS-001 v0.2` at `545fdfa`.
