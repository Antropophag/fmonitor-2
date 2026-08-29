# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 5 — fresh independent final production review
- Reviewer: separately tasked agent `/root/bootstrap_v2_code_review_end`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Approved test review: `2098869`
- Exact reviewed implementation HEAD: `545fdfa935e15d8dabc68922637537cebe5d9bf7`
- Implementation commits: `d421a09`, `2186132`, `545fdfa`
- Browser evidence: `~/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-545fdfa/`
- Review date: 2026-08-29
- Verdict: `CHANGES_REQUESTED`

## Standards

### Blocking — bootstrap preflight depends on a non-atomic SHLZ graph capture

`bin/fmonitor2-pilot-demo.php` correctly uses the production `ShlzCssManifest` for preflight and classifies a post-bind graph mismatch as redacted `SHLZ_ASSETS_UNAVAILABLE`. However, the exact production manifest at `app/PilotHttp/PilotHttp.php:93-97` does not implement its approved capture contract: each member is opened and closed during `walk()`, opened and closed again during constructor-wide revalidation, then opened a third time by `asset()` for the response. Descriptors are not retained through global revalidation and the response is read from the path again rather than emitted from captured bytes.

That creates an intra-request TOCTOU window in both HTTP serving and bootstrap preflight. A replacement can occur after constructor revalidation but before `asset()->readBytes()`; per-file identity/hash checks cannot prove that the returned member belongs to the same atomically captured graph. This violates `PILOT-SHLZ-ASSETS-001 v0.2` sections 3.56–64 and makes bootstrap's complete-graph safety claim false. Retain every descriptor, captured identity/hash/bytes through attempt-all global revalidation, choose the route only afterward, and build the response from captured bytes.

### Blocking — trusted-owner and directory permission rules are narrower than the approved contract

`ShlzCssManifest` accepts only `rootStat['uid'] === effective UID` (`PilotHttp.php:93`), although section 2.38 explicitly trusts either effective UID **or UID 0**. `captureDirectory()` checks type, owner, and group/other-write bits but does not require owner read/search bits as specified. Valid root-owned public exports can therefore fail, while a directory without the explicit owner permission contract is not rejected for the stated reason.

### Non-blocking — timing workaround in the global router

`public/router.php:18` adds `flush(); usleep(1000)` for every CLI-server 503. This is an unexplained global timing workaround outside asset/bootstrap composition and is not required by the approved behavior. Remove it or replace it with a production-semantic mechanism justified by an executable spec.

### Judgment call — Divergent Change

`ShlzCssManifest` still combines filesystem trust, graph traversal, CSS parsing, resource limits, descriptor lifetime, and response selection in dense single-line methods. This is not a separate rejection, but it obscures the security-critical lifecycle defect above.

## Specification

Bootstrap-specific behavior otherwise matches the approved v0.2 contract. Preflight validates the complete graph; post-bind byte/MIME/length/HEAD/unknown-route mismatches stop the spawned server, do not activate the generation, and are classified `SHLZ_ASSETS_UNAVAILABLE`. No SysV IPC residue remains at the exact head. The supplied exact-head Chromium evidence contains the complete journey and responsive final queue/card records.

The inherited `PILOT-SHLZ-ASSETS-001 v0.2` predecessor remains mandatory for bootstrap readiness, so the atomic-capture and permission defects above block this Gate 5 approval even though all executable tests pass.

## Verification

```text
php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php   PASS
php tests/InstallationProcess/pilot_shlz_assets_001_test.php     PASS
tests/InstallationProcess/*_test.php                             49/49 PASS
PHP lint: app bin public tests                                  PASS
git diff --check bf1aeb9...545fdfa                              PASS
demo/router process residue                                     none
SysV shared-memory residue                                      none
exact browser evidence final-pilot-acceptance-545fdfa           present
```

Gate 5 remains closed for `PILOT-DEMO-BOOTSTRAP-001 v0.2` at exact reviewed commit `545fdfa935e15d8dabc68922637537cebe5d9bf7`.
