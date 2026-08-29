# Code review: PILOT-SHLZ-ASSETS-001 v0.1

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked Codex agent `/root/shlz_assets_code_review`
- Verdict: `CHANGES_REQUESTED`
- Specification: `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Approved test review: `f56b1418ec4562357a61d15d7e105a9c7dd1ea5e`
- Reviewed implementation HEAD: `d421a095197c3c5dc5a56f14ca3fc9b9e013d5e9`
- Core implementation: `4ab55bdaeb7594a275533a11379d8ef25f49d865`
- Cumulative bootstrap implementations: `3afced4b8f49cb63553837b53dc2967049cfd96f`, `d421a095197c3c5dc5a56f14ca3fc9b9e013d5e9`
- Review date: 2026-08-29

## Verdict

`CHANGES_REQUESTED`. The deterministic asset contract and all 49 regression tests are green, but the exact reviewed head has no required real-Chromium evidence for this slice. In addition, the manifest parser accepts malformed trailing top-level CSS after a valid import prefix instead of failing the graph closed. Gate 5 therefore cannot approve this head.

## Spec findings

### Blocking — required exact-head browser acceptance is absent

Specification section 6 requires the implementation author to record Chromium evidence for the final queue and final object card at `1440×900`, `768×1024`, and `320×568`, tied to the exact Git HEAD. It must include the complete stylesheet request graph and MIME, zero console/import errors, computed ownership of `.shlz-*`, `--shlz-*`, and `.fm2-*` rules, overflow results, focus traversal, screenshots, and runtime/version metadata. Section 9 explicitly makes that evidence a Gate 5 requirement and says deterministic Gate 2 does not replace it.

No evidence referencing `d421a09` or `PILOT-SHLZ-ASSETS-001` exists in the repository or the established evidence location named by the prior UI review. Consequently the reviewer cannot establish that the split export is actually applied by Chromium or that all browser implications remain sound at this head.

### Blocking — malformed graph can be accepted

`app/PilotHttp/PilotHttp.php:98` stops parsing as soon as the next top-level token is neither whitespace/comment, accepted `@charset`/`@layer`, nor `@import`. This is correct after an ordinary style rule, but it also silently accepts malformed syntax after the import prefix, for example a valid import followed by an unterminated top-level comment. The import-rule suffix is also accepted by the broad `[^;{}]*` expression without validating the permitted layer/supports/media grammar.

That conflicts with sections 2, 3, and 5, which require a malformed graph to fail closed as exact `503`, and can surface as a Chromium CSS parse/import error forbidden by section 6. The approved test's generic malformed-import cases do not catch this plausible regression. Fixing the behavior needs a focused public-seam RED assertion and therefore restarts at Gate 2/test review under the mandatory process.

## Standards findings

### Blocking process finding in the requested cumulative head

The cumulative history places production commit `3afced4` before its executable bootstrap specification `9193839`. `docs/development-process.md` requires Gate 1 approval before the failing test and implementation, with each gate passed in order. This does not invalidate the correctly ordered core asset sequence (`dd22aa8 → 277e08f/0ed3874/9f87f1f → f56b141 → 4ab55bd`), but the requested cumulative implementation cannot be declared fully workflow-compliant without an explicit project-level resolution of that bootstrap slice.

### Non-blocking maintainability observations

- `bin/fmonitor2-pilot-demo.php:102-113` imports descriptor primitives and `ShlzCssManifest` from the monolithic HTTP implementation and independently enumerates/reads the graph. This creates Feature Envy and shotgun coupling between demo bootstrap and HTTP asset internals. A narrow graph-preflight service/value would preserve ownership without broad refactoring in this gate.
- `bin/fmonitor2-pilot-demo.php:212-219` is a deliberately small smoke client, but it overwrites repeated response headers and does not model transfer framing. That is acceptable for the current built-in-server CSS smoke, yet its limited contract should remain contained.

No separate traversal, route-priority, identity, MIME, HEAD-body, graph-limit, authorization, cookie/session, or database-access defect was found. Exact manifest membership prevents a syntactically valid route from selecting an arbitrary file; malformed routes and unsupported methods are rejected before identity, DB, graph, or body reads. Descriptor identity/size revalidation remains active for `GET` and `HEAD`.

## Verification evidence

- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — `PASS: PILOT-SHLZ-ASSETS-001 public CSS manifest`.
- All 49 `tests/InstallationProcess/*_test.php`, run sequentially — all `PASS`.
- `find app bin public tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l` — all files report no syntax errors.
- Working tree already contained untracked `.test-artifacts/`; this review did not modify or remove it.

Gate 5 remains closed for `PILOT-SHLZ-ASSETS-001 v0.1` at `d421a09`.
