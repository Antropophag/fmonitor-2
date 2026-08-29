# Code review: PILOT-SHLZ-ASSETS-001 v0.2

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked Codex agent `/root/shlz_assets_code_review_end`
- Verdict: `APPROVED`
- Specification: `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Approved test review: `a1ad44b539a9bbf5463aedce2332c7e2292b4583`
- Reviewed implementation HEAD: `df765325fe1a3823dcffc534a7fc05b376328657`
- Review date: 2026-08-29

## Verdict

`APPROVED`. No blocking Standards or Spec finding remains. The implementation now performs one-open, same-descriptor whole-graph capture/revalidation/close and serves only captured bytes. Trusted owner modes, parser/routes/security behavior, cleanup, the approved behavioral test and exact-head browser evidence conform to the approved v0.2 specification.

## Standards

No finding.

- `ShlzCssManifest::walk()` opens each unique member once and retains that exact resource in the request-owned handle map. `revalidate()` checks the still-open descriptor and path identity, rewinds and rereads that same descriptor, and verifies its captured SHA-256 before any response is selected.
- Constructor cleanup is attempt-all across retained handles and preserves the first capture/revalidation failure over a later close failure. Successfully captured bytes are exposed through `CapturedCssAsset`; response construction does not reopen a filesystem path.
- No response-timing delay or router flush hack exists. No process-global cache, watcher, subprocess, filesystem manifest/lock/sentinel, APCu or SysV coordination was introduced.
- The security-sensitive code remains compact, but the phases and ownership are explicit enough to audit in this narrow module. No actionable smell or unrelated refactor was found in the reviewed implementation delta.

## Spec

No finding.

- Dist-root ownership accepts exactly root UID or application eUID; every graph directory/member must retain the same trusted UID. Directories require owner read/search and reject group/other write; files require owner read, reject group/other write and executable bits.
- Root/member/directory identity includes device, inode, type/mode, owner, size where applicable and mtime. Whole-graph path and same-descriptor revalidation occurs before route selection, with captured byte/hash equality and fail-closed cleanup.
- Approved public tests cover the fixed split manifest, recursive grammar, manifest-only routes, GET/HEAD parity, route/method priority, owner/mode cases, symlink/traversal and drift rejection, graph bounds, legitimate between-request replacement, redaction, no mutation and cleanup.
- Prior parser, route, auth, Host, no-cookie/no-session and pilot.css ownership contracts remain green in the complete InstallationProcess suite.

## Browser evidence

Exact-head evidence is recorded at `/home/antropophag/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-df76532/`.

- Evidence metadata is bound to `df765325fe1a3823dcffc534a7fc05b376328657`.
- `shlz.css` and `pilot.css` requests are `200` with `text/css; charset=UTF-8`.
- CSSOM provenance identifies a public `--shlz-*` property and `.shlz-button` in `/pilot/assets/shlz.css`, plus `.fm2-shell` in `/pilot/assets/pilot.css`.
- Queue/card checks at 1440, 768 and 320 report no horizontal overflow, visible focus and empty page/console/CSS error arrays. The two general journey `ERR_ABORTED` entries are the browser navigation signals for the two successful artifact downloads, not stylesheet or application failures; both downloaded files are present in the evidence set.

## Verification evidence

- Exact refs: HEAD `df765325fe1a3823dcffc534a7fc05b376328657`; spec `331b8ac9616b99162fe75b7bc501e1dc223a9d73`; approved test review `a1ad44b539a9bbf5463aedce2332c7e2292b4583`.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS in three consecutive runs.
- `php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php` — PASS.
- All 49 `tests/InstallationProcess/*_test.php`, sequentially — PASS.
- PHP lint for production asset/application entrypoints and the focused test — PASS.
- `git diff --check a1ad44b..df76532` — PASS.
- `ipcs -m`; `ipcs -s` — no shared-memory segments or semaphore arrays.
- Residue scan for `psa-*`, lock and manifest-cache artifacts — empty; worktree clean before this review record.

Gate 5 is approved for `PILOT-SHLZ-ASSETS-001 v0.2` at exact implementation HEAD `df765325fe1a3823dcffc534a7fc05b376328657`.
